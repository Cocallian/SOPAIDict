from pathlib import Path

# Google Drive backups for the Backup card on the admin dashboard. Only the structure is here so far: every method
# below says what it will do and is still empty, and the routes answer "not implemented yet".

DRIVE_FOLDER_NAME = "SOPAgent Backups"                  # the folder in Google Drive the backups are uploaded into
SCOPES = ["https://www.googleapis.com/auth/drive.file"]   # Drive access limited to the files this app makes
CREDENTIALS_FILE = "GoogleDrive/credentials.json"       # downloaded from the Google Cloud console, next to config.json
TOKEN_FILE = "GoogleDrive/token.json"                   # written after the first sign in, so it only happens once


def get_credentials(project):
    # Returns valid Google credentials: loads TOKEN_FILE, refreshes it when it has expired, and runs the sign in with
    # CREDENTIALS_FILE the first time. The google libraries are imported in here, so the server still starts without them.
    pass


def get_drive_service(project):
    # Returns the Drive client built from get_credentials()
    pass


def find_or_create_folder(service, name):
    # Returns the id of the Drive folder called `name`, making it when it isn't there yet
    pass


def upload_file(service, path, folder_id):
    # Uploads the file at `path` into the Drive folder `folder_id`, returns the id Drive gave it
    pass


def backup_database_to_drive(project, passwords_file):
    # Dumps the SOPAgent database to a temporary .sql file, uploads it with upload_file() and deletes the temporary file.
    # Returns the name of the file in Drive.
    pass


def backup_system_to_drive(project):
    # Zips the system (code, config, website, documents), uploads the zip with upload_file() and deletes the temporary zip.
    # Returns the name of the file in Drive.
    pass


def register_backup_routes(app, project, passwords_file=None):
    passwords_file = Path(passwords_file) if passwords_file else project / "FrontEnd" / "passwords.txt"

    @app.post("/drive/backup-database")
    def drive_backup_database():
        # adminAgent.php posts here when "Database backup - Google Drive" is pressed
        return "Google Drive database backups are not implemented yet.", 501

    @app.post("/drive/backup-system")
    def drive_backup_system():
        # adminAgent.php posts here when "System backup - Google Drive" is pressed
        return "Google Drive system backups are not implemented yet.", 501
