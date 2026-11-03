import io, json, re, subprocess, tempfile, threading, zipfile
from datetime import datetime
from pathlib import Path
from flask import request, send_file

# Google Drive backups for the Backup card on the admin dashboard. The Drive calls follow Google's Python quickstart
# (token.json + InstalledAppFlow + build("drive", "v3") + MediaFileUpload), the same steps as the Medium article.

DRIVE_FOLDER_NAME = "SOPAgent Backups"                  # the folder in Google Drive the backups are uploaded into
SCOPES = ["https://www.googleapis.com/auth/drive.file"]   # Drive access limited to the files this app makes
CREDENTIALS_PATTERN = "client_secret*.json"             # the file downloaded from the Google Cloud console, in the project folder
TOKEN_FILE = "GoogleDrive/token.json"                   # written after the first sign in, so it only happens once
SIGN_IN_TIMEOUT = 120                                   # seconds to finish the Google sign in before it gives up and frees the port
sign_in_lock = threading.Lock()                         # only one sign in at a time, a second button press would fight over the port
SIGN_IN_PORT = 8765                                     # the sign in comes back to http://localhost:8765/ - add it to the client's Authorized redirect URIs (8080 is Apache)
MYSQLDUMP = r"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"   # same as ScheduleBackup.py
SKIP_FOLDERS = {".venv", ".git", "build", "dist", "Backups", "ReportData", "GoogleDrive", "vendor", "__pycache__"}   # left out of the system zip


def log(message):
    # One line in the server window, in the same style as the question log in LocalAIServ.py
    print(f"[{datetime.now():%H:%M:%S}] [backup] {message}", flush=True)


def get_credentials(project):
    # The google libraries are imported in here, so the server still starts without them
    from google.auth.exceptions import RefreshError
    from google.auth.transport.requests import Request
    from google.oauth2.credentials import Credentials
    from google_auth_oauthlib.flow import InstalledAppFlow

    token_path = project / TOKEN_FILE
    creds = Credentials.from_authorized_user_file(str(token_path), SCOPES) if token_path.exists() else None

    if creds and creds.valid:
        log("Google sign in found, still valid")
        return creds
    if creds and creds.expired and creds.refresh_token:
        log("Google sign in expired, refreshing it")
        try:
            creds.refresh(Request())
        except RefreshError as error:
            # Google no longer accepts the saved sign in (access removed, 7 day Testing limit, password changed...)
            log(f"Google refused the saved sign in ({error}), deleting {TOKEN_FILE} to sign in again")
            token_path.unlink(missing_ok=True)
            creds = None

    if not creds or not creds.valid:
        # First time, or the saved sign in was refused: a browser opens on this PC to sign in to Google
        secrets = list(project.glob(CREDENTIALS_PATTERN))
        if not secrets:
            raise RuntimeError(f"No Google client file ({CREDENTIALS_PATTERN}) found in {project}")
        log(f"No saved Google sign in, using {secrets[0].name}")
        flow = InstalledAppFlow.from_client_secrets_file(str(secrets[0]), SCOPES)
        if not sign_in_lock.acquire(blocking=False):
            raise RuntimeError("A Google sign in is already waiting. Finish it in the browser, or wait for it to time out, then try again")
        log(f"Opening the browser to sign in to Google, waiting up to {SIGN_IN_TIMEOUT} seconds on port {SIGN_IN_PORT}")
        try:
            creds = flow.run_local_server(port=SIGN_IN_PORT, timeout_seconds=SIGN_IN_TIMEOUT)
        finally:
            sign_in_lock.release()

    token_path.parent.mkdir(exist_ok=True)
    token_path.write_text(creds.to_json(), encoding="utf-8")
    log(f"Google sign in saved to {TOKEN_FILE}")
    return creds


def get_drive_service(project):
    from googleapiclient.discovery import build
    return build("drive", "v3", credentials=get_credentials(project))


def find_or_create_folder(service, name):
    found = service.files().list(
        q=f"name = '{name}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
        spaces="drive", fields="files(id)").execute().get("files", [])
    if found:
        log(f"Using the Drive folder '{name}'")
        return found[0]["id"]
    log(f"Creating the Drive folder '{name}'")
    folder = service.files().create(body={"name": name, "mimeType": "application/vnd.google-apps.folder"}, fields="id").execute()
    return folder["id"]


def upload_file(service, path, folder_id):
    from googleapiclient.http import MediaFileUpload
    path = Path(path)
    media = MediaFileUpload(str(path), chunksize=5 * 1024 * 1024, resumable=True)
    upload = service.files().create(body={"name": path.name, "parents": [folder_id]}, media_body=media, fields="id")
    log(f"Uploading {path.name} ({path.stat().st_size / 1024 / 1024:.1f} MB) to Drive")
    response = None
    while response is None:
        status, response = upload.next_chunk()
        if status:
            log(f"Uploaded {int(status.progress() * 100)}%")
    log("Upload finished")
    return response["id"]


def backup_database_to_drive(project, config_file):
    # Same MySQL login and mysqldump options as ScheduleBackup.py
    with open(config_file, encoding="utf-8") as f:
        db = json.load(f)["database"]

    name = f"SOPagent{datetime.now():%Y-%m-%d_%H-%M}.sql"
    with tempfile.TemporaryDirectory() as temp:
        dump_file = Path(temp) / name
        command = [MYSQLDUMP, f"--host={db['db_host']}", f"--user={db['db_user']}",
                   "--single-transaction", "--databases", "SOPAgent", "--skip-extended-insert",
                   f"--result-file={dump_file}"]
        if db["db_pass"] != "":
            command.insert(3, f"--password={db['db_pass']}")
        log("Dumping the SOPAgent database with mysqldump")
        dump = subprocess.run(command, capture_output=True, text=True)
        if dump.returncode != 0:
            raise RuntimeError("mysqldump failed: " + dump.stderr.strip())
        log(f"Dump finished ({dump_file.stat().st_size / 1024:.0f} KB)")

        service = get_drive_service(project)
        upload_file(service, dump_file, find_or_create_folder(service, DRIVE_FOLDER_NAME))
    return name                                         # the temporary folder (and the .sql in it) is deleted here


def system_zip_name(project):
    # "<ORG_NAME>_<date and time>.zip", with the characters Windows doesn't allow in file names taken out of the organisation name
    with open(project / "config.json", encoding="utf-8") as f:
        org = json.load(f)["ORG_NAME"]
    org = re.sub(r'[\\/:*?"<>|]', "", org).strip()
    return f"{org}_{datetime.now():%Y-%m-%d_%H-%M-%S}.zip"


def make_system_zip(project, zip_file):
    # Zips everything in the project folder except the big/rebuildable folders in SKIP_FOLDERS, the Google sign in files and the zip itself
    log(f"Zipping the system into {zip_file.name}")
    count = 0
    with zipfile.ZipFile(zip_file, "w", zipfile.ZIP_DEFLATED) as archive:
        for path in project.rglob("*"):
            relative = path.relative_to(project)
            if path.is_dir() or path == zip_file or SKIP_FOLDERS & set(relative.parts) or path.match(CREDENTIALS_PATTERN):
                continue
            archive.write(path, relative)
            count += 1
    log(f"Zip finished: {count} files, {zip_file.stat().st_size / 1024 / 1024:.1f} MB")


def backup_system_to_drive(project):
    name = system_zip_name(project)
    with tempfile.TemporaryDirectory() as temp:
        zip_file = Path(temp) / name
        make_system_zip(project, zip_file)

        service = get_drive_service(project)
        upload_file(service, zip_file, find_or_create_folder(service, DRIVE_FOLDER_NAME))
    return name                                         # the temporary folder (and the zip in it) is deleted here


def register_backup_routes(app, project, config_file=None):
    config_file = Path(config_file) if config_file else project / "FrontEnd" / "FrontEndConfig.json"

    def run_backup(label, backup, *args):
        # Runs one backup method and answers with plain text: 200 + the file name in Drive, 500 + the reason when it fails
        log(f"{label} started")
        try:
            name = backup(*args)
        except Exception as error:
            log(f"{label} FAILED: {error}")
            return f"Backup failed: {error}", 500
        log(f"{label} finished: {name}")
        return f"Backup uploaded to Google Drive as {name}", 200

    @app.post("/local/backup-database")
    def local_backup_database():
        # ScheduleBackup.exe saves the dump into a temporary folder, then the file is sent back to whoever pressed the button
        exe = project / "ScheduleBackup.exe"
        log("Local database backup started")
        if not exe.exists():
            log(f"Local database backup FAILED: {exe.name} not found")
            return f"{exe.name} wasn't found in {project}.", 500

        with tempfile.TemporaryDirectory() as temp:
            log(f"Running {exe.name}")
            run = subprocess.run([str(exe), temp], capture_output=True, text=True, timeout=300)
            dumps = list(Path(temp).glob("*.sql"))
            if run.returncode != 0 or not dumps:
                log(f"Local database backup FAILED: {(run.stderr or run.stdout).strip()}")
                return (run.stderr or run.stdout).strip() or "Backup failed.", 500
            data = dumps[0].read_bytes()                # read before the temporary folder is deleted
            name = dumps[0].name
        log(f"Local database backup finished: {name}")
        return send_file(io.BytesIO(data), as_attachment=True, download_name=name, mimetype="application/sql")

    @app.post("/local/backup-system")
    def local_backup_system():
        # The whole system is zipped into a temporary folder, then the zip is sent back to whoever pressed the button
        log("Local system backup started")
        try:
            name = system_zip_name(project)
            with tempfile.TemporaryDirectory() as temp:
                zip_file = Path(temp) / name
                make_system_zip(project, zip_file)
                data = zip_file.read_bytes()            # read before the temporary folder is deleted
        except Exception as error:
            log(f"Local system backup FAILED: {error}")
            return f"Backup failed: {error}", 500
        log(f"Local system backup finished: {name}")
        return send_file(io.BytesIO(data), as_attachment=True, download_name=name, mimetype="application/zip")

    @app.post("/drive/backup-database")
    def drive_backup_database():
        # adminAgent.php posts here when "Database backup - Google Drive" is pressed: only the database
        return run_backup("Google Drive database backup", backup_database_to_drive, project, config_file)

    @app.post("/drive/backup-system")
    def drive_backup_system():
        # adminAgent.php posts here when "System backup - Google Drive" is pressed: the full system
        return run_backup("Google Drive system backup", backup_system_to_drive, project)
