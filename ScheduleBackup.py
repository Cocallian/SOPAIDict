import base64
import configparser
import json
import subprocess
import sys
from datetime import date
from pathlib import Path

MYSQLDUMP = r"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"
PROJECT = Path(sys.executable).parent if getattr(sys, "frozen", False) else Path(__file__).parent   # next to the .exe when built with PyInstaller

with open(PROJECT / "config.json", "r", encoding="utf-8") as file:
    data = json.load(file)

PATH_URL = data["BACKUP_PATH"]

# Only ask for a folder when config.json doesn't already say where backups go
if PATH_URL == "":
    pick = ('Add-Type -AssemblyName System.Windows.Forms; '
            '$f = New-Object System.Windows.Forms.FolderBrowserDialog; '
            '$f.Description = "Choose where to save the SOPAgent backup"; '
            'if($f.ShowDialog((New-Object System.Windows.Forms.Form -Property @{TopMost = $true})) -eq "OK") { $f.SelectedPath }')
    encoded = base64.b64encode(pick.encode("utf-16-le")).decode()   # -EncodedCommand expects UTF-16 text in base64
    result = subprocess.run(["powershell", "-NoProfile", "-STA", "-EncodedCommand", encoded], capture_output=True, text=True)
    PATH_URL = result.stdout.strip()
    if PATH_URL == "":
        sys.exit("No folder chosen, so no backup was made.")

# The MySQL login is the one the website uses
secrets = configparser.ConfigParser()
secrets.read(PROJECT / "FrontEnd" / "passwords.txt", encoding="utf-8")
db = {key: value.strip('"') for key, value in secrets["database"].items()}   # passwords.txt keeps values in double quotes

backup_file = Path(PATH_URL) / f"SOPagent{date.today()}.sql"
command = [MYSQLDUMP, f"--host={db['db_host']}", f"--user={db['db_user']}",
           "--single-transaction", "--databases", "SOPAgent", "--skip-extended-insert",
           f"--result-file={backup_file}"]
if db["db_pass"] != "":
    command.insert(3, f"--password={db['db_pass']}")

dump = subprocess.run(command, capture_output=True, text=True)
if dump.returncode != 0:
    sys.exit("Backup failed: " + dump.stderr.strip())
print("Database backed up to", backup_file)

# Remember the chosen folder, so later runs (and Task Scheduler) don't ask again
if data["BACKUP_PATH"] == "":
    data["BACKUP_PATH"] = PATH_URL
    with open(PROJECT / "config.json", "w", encoding="utf-8") as f:
        json.dump(data, f, indent=4)
