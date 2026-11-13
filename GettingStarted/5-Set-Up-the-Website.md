# 5. Set up the website

The website is the `FrontEnd` folder. It's a set of PHP pages run by XAMPP's Apache web server, with MySQL storing accounts, questions, answers, feedback and logins.

| Page | What it does |
|---|---|
| `login.php` | The login page. It's the default page, set in `.htaccess`. |
| `dataconn.php` | Checks logins. On the first login it creates the database, its tables and the two starting accounts. |
| `UserRequest.php` | The account request page. Anyone with an allowed email address can apply for an account. |
| `Agent.php` | The question page for normal users. It saves each question, sends it to `LocalAIServ.py`, saves the answer and lets the person rate it. Its ☰ menu has a **Log out** button. |
| `adminAgent.php` | The same for admins, plus a ☰ menu with a dashboard: usage numbers and a chart, **Add new user**, **Delete User**, active users, account **Applications**, **Backup** and the **RAG Pipeline Server** controls. |
| `reports.php` | The admin's **Generate report** page: a PDF of usage for the period you choose. |
| `settings.php` | The admin's **Settings** page: the answer server's settings from `config.json`. |
| `Inactive.php` | The "you were logged out" page, shown after a period of inactivity. |

Each page is a `.php` file that holds its own HTML and CSS and runs functions from a folder of the same name (`Agent/`, `adminAgent/`, `reports/`, `settings/`, ...). `common/` has what every page uses, and `js/` has the scripts. Copy all of these folders, not only the `.php` files.

The pages talk to the answer server on port 5000, so [step 4](4-Choose-Local-or-Colab.md) must work first.

## 5.1 Copy the website into XAMPP

Apache only serves files inside `C:\xampp\htdocs`.

1. Create a folder such as `C:\xampp\htdocs\SOPAgent`.
2. Copy **everything** from the project's `FrontEnd` folder into it, including `.htaccess`.
3. Download the chart library into the same folder:

   ```powershell
   cd C:\xampp\htdocs\SOPAgent
   composer install
   ```

   This creates a `vendor` folder. Without it, the admin page shows an error about `vendor/autoload.php`. If Composer says the zip extension is missing, finish [step 1's XAMPP part](1-Install-Software.md#xampp-web-server-php-and-mysql).

The copy in `htdocs` is the one that runs. If you change a file in the project's `FrontEnd` folder later, copy it across again.

## 5.2 Fill in `FrontEndConfig.json`

The PHP pages read your MySQL login, the starting accounts and other website settings from `FrontEndConfig.json`. It's kept out of git, so a fresh copy of the project doesn't have one. You need it in **two** places:

- `C:\xampp\htdocs\SOPAgent\FrontEndConfig.json`, for the website
- `LLM\FrontEnd\FrontEndConfig.json` in the project, for the answer server's backups and reports, and `ScheduleBackup.exe`

Create it in Notepad. When saving, choose **Save as type: All files**, so it isn't saved as `FrontEndConfig.json.txt`. Paste this in and fill it out:

```json
{
    "database": {
        "db_host": "127.0.0.1",
        "db_user": "root",
        "db_pass": ""
    },
    "seed_accounts": {
        "admin_user": "admin",
        "admin_pass": "choose-a-strong-password",
        "user_user": "user",
        "user_pass": "choose-another-password"
    },
    "paths": {
        "server_path": ""
    },
    "inactivity": {
        "inactivity_timeout_minutes": 15
    },
    "org_names": ["@example.org"]
}
```

| Setting | What to put |
|---|---|
| `db_host`, `db_user` | Leave as they are for XAMPP. |
| `db_pass` | XAMPP's MySQL `root` user has **no password** out of the box, so leave it as `""`. If you've set one, put it here. |
| `seed_accounts` | The two starting accounts made on the first login: an admin and a normal user. **Choose your own passwords**, not a guessable one. The admin's username must start with `admin`, and the user's must not. They are removed automatically once a real admin has logged in (see [5.5](#55-managing-accounts)). |
| `server_path` | Where `LocalAIServ.py` is. Leave it empty: the **Start server** button on the admin page asks for the file the first time and saves it here. |
| `inactivity_timeout_minutes` | Minutes without activity before a person is logged out. 15 if you leave it out. |
| `org_names` | The email domains people may have accounts with, each starting with `@`. Usernames are email addresses ending in one of these. Admins can change this list later on the Settings page ([5.6](#56-settings-and-reports)). |

Leave the `seed_accounts` section in place until you've made your own admin: `dataconn.php` reads it at every login and only adds an account when it doesn't exist yet. (Changing the password here later doesn't change an existing account's password.) Once you've made your own admin and logged in as them, the website deletes both starting accounts and removes this section from the file (step 5.5). An older copy of this file may still have `answers` / `colab_url`; the website no longer reads it.

The file must be valid JSON: every text value in double quotes, a comma after every entry except the last, and each backslash in a Windows path written twice (or use `/` slashes). A missing comma stops the website reading its settings.

`.htaccess` stops browsers from opening `FrontEndConfig.json`, and the project's `.gitignore` keeps it out of git.

## 5.3 Start everything

1. In the **XAMPP Control Panel**, click **Start** next to **Apache** and **MySQL**. Both should turn green. Start them from the Control Panel rather than as Windows services (the "Svc" tick boxes).
2. Start `LocalAIServ.py` ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)), and leave its window open. After your first admin login you can also start and stop it with the **Start server** and **Stop server** buttons. Those buttons only work if the project has a `.venv` next to `LocalAIServ.py` ([step 1](1-Install-Software.md#make-the-projects-python-environment-and-install-the-packages)).

## 5.4 First login

1. Go to **http://localhost/SOPAgent/**. The login page should appear.
2. Log in with the admin username and password you put in `seed_accounts`. This first login creates the `SOPAgent` database, its tables and the two starting accounts.
3. You land on the admin question page. Type a question and press **Enter**. An animation runs while the answer is prepared; this can take a minute or more.
4. The answer appears with its sources, and you can rate it. The ☰ menu (top left) shows the dashboard.
5. In the dashboard's **RAG Pipeline Server** card, check it says **Server running**. If it says **Server not running**, press **Start server**. The first time, a file window opens **on the PC running XAMPP**: choose `LocalAIServ.py` in your project folder. The choice is saved as `server_path`. **Change server file** picks a different one.

## 5.5 Managing accounts

- **Admin or user is decided by the username.** A username starting with `admin` is checked against the `Admins` table and gets the admin page. Every other username is checked against `Users`.
- **Usernames and passwords have rules.** A username must be an email address ending in one of your `org_names` domains (for example `jane@example.org`, or `admin.sam@example.org` for an admin), and at most 50 characters. A password needs 8 or more characters, with a number, a lowercase letter, an uppercase letter and a special character.
- **People can ask for an account.** The login page links to `UserRequest.php`, where someone applies with their email address and a password. The request appears in the **Applications** card on the admin dashboard: press **Approve** to create the account, or **Reject**.
- **Replace the starting accounts as soon as you can.** Log in with the starting admin, use **Add new user** to make your own admin (a username starting with `admin`, such as `admin.sam@example.org`), log out and log in as that new admin. The moment an admin other than the starting one logs in, the website deletes the two starting accounts (and any questions they asked) and removes `seed_accounts`, with its plain text passwords, from `FrontEndConfig.json` in `htdocs`. A message confirms it, and they are never made again. Delete the section by hand from the project's own copy of the file too. If you ever need starting accounts again (for example on a new, empty database), put a `seed_accounts` section back.
- **Adding people yourself:** the **Add new user** card. Enter a username and password and choose **User** or **Admin**. A message confirms the account was added, or says why it wasn't.
- **Removing people:** the **Delete User** card. You can't delete the admin account you're logged in with. Their questions and answers are deleted with them.
- **One login at a time.** A person who is already logged in on another device is refused ("Already logged in on another device"). The **Active users** card lists who is logged in, with a **Log out** button to end a stuck session.
- **Inactivity.** After `inactivity_timeout_minutes` without activity the person is warned, then logged out and sent to `Inactive.php`. Logins, logouts and inactivity logouts are recorded in the `Logs` table, which the reports use.
- **Changing a password**: in **http://localhost/phpmyadmin**, pick the **SOPAgent** database, open the **SQL** tab and run, for example:

  ```sql
  UPDATE Admins SET PasswordHash = SHA2('new-password', 256) WHERE Username = 'admin.sam@example.org';
  UPDATE Users  SET PasswordHash = SHA2('new-password', 256) WHERE Username = 'jane@example.org';
  ```

- **Logging out:** the ☰ menu on every page has a **Log out** button, which ends the session and returns to the login page.

## 5.6 Settings and reports

Both pages are in the admin's ☰ menu. Reports and the settings below need `LocalAIServ.py` running, because the answer server does the work. The allowed email domains don't.

**Settings** shows the answer server's settings from `config.json`: organisation name, backup folder, context window, temperature, answer length, number of sources, parallel questions, model name and embedding model. The pages hold the allowed range for each; a value outside it is refused with a message. Saving writes `config.json`. The answer server reads it only when it starts, so **stop and start the server for changes to apply** ([step 4.1](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)). Changing the embedding model also means running `EmbedScript.py` again ([step 3.4](3-Embed-Your-SOPs.md#34-changing-the-embedding-model)).

**Allowed email domains** is a separate box on the same page. It lists every domain in `org_names` from `FrontEndConfig.json`, one per line. Edit the list and press **Save domains**: each entry must look like `example.org` (the `@` is optional, entries are made lower case and repeats are removed), and there must be at least one and at most 50. It's saved straight into `FrontEndConfig.json` and applies to the next account that is added or requested. It works even when the answer server isn't running. Accounts that already exist keep working if their domain is removed; they just can't be created again.

**Generate report** makes a PDF of how the assistant has been used. Choose a date range, a week or a month (set one or more; unset ones are ignored), switch on what to include (total responses, busiest days, most sourced documents, longest sessions) and press **Generate**. You're asked where to save the PDF. A copy is kept in the project's `Reports` folder. Nothing before the oldest entry in the database, or after today, can be chosen. Reports use the extra Python packages in `requirements.txt` (pandas, matplotlib, xhtml2pdf, pymysql), so the `.venv` must have been set up from it.

## 5.7 Letting other people use it

Anyone on the same network can open `http://YOUR-PC-IP/SOPAgent/` if Windows Firewall allows Apache. Run `ipconfig` to find your PC's IP address. Every question is answered by your PC, so it must stay on with XAMPP and `LocalAIServ.py` running.

This setup is meant for a trusted local network:
- Passwords are stored as unsalted SHA-256 hashes.
- The MySQL password and the starting account passwords are stored as plain text in `FrontEndConfig.json`.
- There's no HTTPS, and the pages don't have CSRF protection or security headers yet.
- PHP 8.2 has known security fixes in newer releases.

The README lists the planned security work. Don't expose it to the internet before that's done. If you do forward a port to it, forward only Apache's port. Never forward 3306 (MySQL) or 5000 (the answer server).

## 5.8 Backups

The **Backup** card on the admin dashboard has four buttons. Each asks for **your admin password** before it does anything.

| Button | What it makes | Where it ends up |
|---|---|---|
| Database backup - locally | `SOPagent<date>.sql`: all the database's tables | Downloads in the browser of whoever pressed the button |
| System backup - locally | `<ORG_NAME>_<date and time>.zip`: the project folder | Downloads in the browser |
| Database backup - Google Drive | The same `.sql` file | Your Google Drive, in a folder called `SOPAgent Backups` |
| System backup - Google Drive | The same `.zip` file | Your Google Drive, in `SOPAgent Backups` |

The **system backup** zips the project folder (scripts, `config.json`, passages and vectors, `FrontEnd` with `FrontEndConfig.json`, reports). It leaves out `.venv`, `.git`, `build`, `dist`, `vendor`, `Backups`, `ReportData`, `GoogleDrive`, caches and your Google client file, which can all be rebuilt. It still contains your SOP text and passwords, so keep the zips private.

All four buttons need the answer server running. The two **database** backups also need MySQL Server 8.0's `mysqldump` ([step 1](1-Install-Software.md#mysql-server-80-only-for-backups)); the system backups don't.

### Local database backup: build `ScheduleBackup.exe`

The **Database backup - locally** button runs `ScheduleBackup.exe` from the main `LLM` folder. That file isn't included, so build it once. From the main `LLM` folder:

```powershell
.venv\Scripts\python.exe -m PyInstaller --onefile Backup\ScheduleBackup.py
Move-Item dist\ScheduleBackup.exe . -Force
```

You can delete the `build` and `dist` folders and `ScheduleBackup.spec` afterwards. It reads the MySQL login from the project's `FrontEnd\FrontEndConfig.json`.

### Google Drive backups: one-time setup

The two Drive buttons need a Google sign in. You make a free "OAuth client" for your own Google account:

1. Go to [console.cloud.google.com](https://console.cloud.google.com) and create a project (for example `SOPAgent`).
2. **APIs & Services → Library**: find **Google Drive API** and click **Enable**.
3. **APIs & Services → OAuth consent screen**: choose **External**, fill in the app name and your email, and under **Test users** add the Google account you'll back up to.
4. **APIs & Services → Credentials → Create credentials → OAuth client ID**. Choose **Desktop app**, create it and **Download JSON**.
5. Put the downloaded file in the main `LLM` folder (next to `LocalAIServ.py`), keeping its `client_secret…json` name. If Google later complains about a redirect address, add `http://localhost:8765/` to the client's authorised redirect URIs.
6. Press a Drive backup button. A Google sign in opens **in the browser on the PC running the answer server**. Finish it within 2 minutes. It saves `GoogleDrive/token.json`, so you only sign in once.

The backup uploads to a `SOPAgent Backups` folder it creates in your Drive. The app can only see files it made itself. While the consent screen is in **Testing** mode, Google ends the sign in after about 7 days; the next backup just asks you to sign in again.

### Automatically: `ScheduleBackup.exe` and Windows Task Scheduler

`ScheduleBackup.exe` makes the same database backup without the website. Windows **Task Scheduler** can run it for you, for example every night.

**1. Tell it where to save backups.** Open `config.json` and set `BACKUP_PATH` to a folder. In JSON, every backslash is written twice (the Settings page does this for you):

```json
"BACKUP_PATH": "C:\\Backups\\SOPAgent"
```

If `BACKUP_PATH` is empty, `ScheduleBackup.exe` opens a folder window and waits for someone to choose. That's fine when you run it by hand, but a scheduled run would sit waiting, so always set it before scheduling.

**2. Test it by hand.** Double-click `ScheduleBackup.exe`. A new `SOPagent<date>.sql` should appear in your backup folder.

**3. Create the scheduled task.**

1. Open the Start menu, type **Task Scheduler** and open it.
2. In the right-hand panel, click **Create Basic Task…**.
3. **Name:** `SOPAgent backup`. Click **Next**.
4. **Trigger:** choose how often, for example **Daily**, then the time, for example 02:00. Choose a time when the PC is on and MySQL is running.
5. **Action:** choose **Start a program**.
6. **Program/script:** click **Browse…** and pick `ScheduleBackup.exe` in your `LLM` folder.
7. **Start in (optional):** enter the `LLM` folder itself, for example `C:\path\to\LLM`, with no quotes.
8. Click **Next**, then **Finish**.

**4. Check it works.** In Task Scheduler, click **Task Scheduler Library** on the left, right-click **SOPAgent backup** and choose **Run**. A new backup file should appear, and the **Last Run Result** column should say *The operation completed successfully. (0x0)*. Any other result means it failed; run `ScheduleBackup.exe` from a terminal to see its message.

**Optional settings.** Right-click the task, choose **Properties**, and on:
- the **General** tab, choose **Run whether user is logged on or not** so it runs without a console window popping up, and even when nobody is logged in. Windows asks for your password when you save.
- the **Settings** tab, tick **Run task as soon as possible after a scheduled start is missed**, so a backup still happens if the PC was off at the scheduled time.

Backups aren't deleted automatically. Clear out old ones from the backup folder (and your Drive) now and then.

### Restoring a backup

Restoring replaces the database's tables with their contents at the time of the backup. Anything saved after the backup is lost. In **http://localhost/phpmyadmin**, open the **Import** tab, choose the `.sql` file and click **Import**. To restore a system backup, unzip it into a new folder and follow this guide from [step 5](#5-set-up-the-website) (the `.venv` and `vendor` folders have to be made again).

---

Next: [6. Troubleshooting](6-Troubleshooting.md)
