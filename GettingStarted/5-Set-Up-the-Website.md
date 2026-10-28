# 5. Set up the website

The website is the `FrontEnd` folder. It's a set of PHP pages run by XAMPP's Apache web server, with MySQL storing accounts, questions and answers.

| Page | What it does |
|---|---|
| `login.php` | The login page. It's the default page, set in `.htaccess`. |
| `dataconn.php` | Checks logins. On the first login it creates the database, its tables and two starting accounts. |
| `Agent.php` | The question page for normal users. It saves each question, sends it to `LocalAIServ.exe` and saves the answer. Its ☰ menu has a **Log out** button. |
| `adminAgent.php` | The same for admins. Its ☰ menu has a dashboard with usage numbers and a chart, an **Add new user** form, **Perform Backup** and **Log out**. |

## 5.1 Copy the website into XAMPP

Apache only serves files inside `C:\xampp\htdocs`.

1. Create a folder such as `C:\xampp\htdocs\SOPAgent`.
2. Copy **everything** from the project's `FrontEnd` folder into it, including `.htaccess`.
3. Download the chart library into the same folder:

   ```powershell
   cd C:\xampp\htdocs\SOPAgent
   composer install
   ```

   This creates a `vendor` folder. Without it, the admin page shows an error about `vendor/autoload.php`.

The copy in `htdocs` is the one that runs. If you change a file in the project's `FrontEnd` folder later, copy it across again.

## 5.2 Fill in `passwords.txt`

The PHP pages read your MySQL login from `passwords.txt`. It's kept out of git, so a fresh copy of the project doesn't have one. You need it in **two** places:

- `C:\xampp\htdocs\SOPAgent\passwords.txt`, for the website
- `LLM\FrontEnd\passwords.txt` in the project, for `ScheduleBackup.exe` (step 5.7)

Create it in Notepad. When saving, choose **Save as type: All files**, so it isn't saved as `passwords.txt.txt`. Paste this in and fill it out:

```ini
; Passwords the PHP pages and ScheduleBackup.exe read.
; Keep every value inside double quotes. Do not commit this file to git.

[database]
db_host = "127.0.0.1"
db_user = "root"
db_pass = ""
```

| Setting | What to put |
|---|---|
| `db_host`, `db_user` | Leave as they are for XAMPP. |
| `db_pass` | XAMPP's MySQL `root` user has **no password** out of the box, so leave it as `""`. If you've set one, put it here. |

Keep **every value in double quotes**, especially passwords containing `;`, `!`, `=` or `#`. Without quotes, PHP can misread them.

`.htaccess` stops browsers from opening `passwords.txt`, and the project's `.gitignore` keeps it out of git.

## 5.3 Start everything

1. In the **XAMPP Control Panel**, click **Start** next to **Apache** and **MySQL**. Both should turn green. Start them from the Control Panel, not as Windows services, so the **Perform Backup** button can show its folder window.
2. Check Ollama is running (llama icon in the system tray).
3. Start `LocalAIServ.exe` and leave its window open ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)).

## 5.4 First login

1. Go to **http://localhost/SOPAgent/**. The login page should appear.
2. Log in with username **`admin`** and password **`123`**. This first login creates the `SOPAgent` database, its four tables (`Admins`, `Users`, `Prompts`, `Responses`) and two starting accounts: `admin` / `123` and `user` / `123`.
3. You land on the admin question page. Type a question and press **Enter**. An animation runs while the answer is prepared; this can take a minute or more.
4. The answer appears with its sources. The ☰ menu (top left) shows the dashboard.
5. **Change both starting passwords straight away** (see 5.5). Everyone knows `123`.

## 5.5 Managing accounts

- **Admin or user is decided by the username.** A username starting with `admin` is checked against the `Admins` table and gets the admin page. Every other username is checked against `Users`.
- **Adding people:** open the ☰ menu on the admin page and use **Add new user**. Enter a username and password and choose **User** or **Admin**. An admin's username must start with `admin` (such as `admin.sam`), and a user's mustn't, otherwise they couldn't log in; the form refuses names that break this rule. A message confirms the account was added, or says the username already exists.
- **Changing a password**, including the two starting accounts: in **http://localhost/phpmyadmin**, pick the **SOPAgent** database, open the **SQL** tab and run, for example:

  ```sql
  UPDATE Admins SET PasswordHash = SHA2('new-password', 256) WHERE Username = 'admin';
  UPDATE Users  SET PasswordHash = SHA2('new-password', 256) WHERE Username = 'user';
  ```

  `dataconn.php` only adds `admin` and `user` when they don't exist yet, so a changed password stays changed.
- **Logging out:** the ☰ menu on both pages has a **Log out** button, which ends the session and returns to the login page.

## 5.6 Letting other people use it

Anyone on the same network can open `http://YOUR-PC-IP/SOPAgent/` if Windows Firewall allows Apache. Run `ipconfig` to find your PC's IP address. Every question is answered by your PC, so it must stay on with XAMPP, Ollama and `LocalAIServ.exe` running.

This setup is meant for a trusted local network:
- Passwords are stored as unsalted SHA-256 hashes.
- The MySQL password is stored as plain text in `passwords.txt`.
- There's no HTTPS.

Don't expose it to the internet without hardening those first. If you do forward a port to it, forward only Apache's port. Never forward 3306 (MySQL) or 5000 (the answer server).

## 5.7 Backups

A backup is a single `.sql` file named `SOPagent<date>.sql`, such as `SOPagent2026-10-28.sql`. It holds all four tables: accounts, questions and answers. Both ways of making one below use MySQL Server 8.0's `mysqldump`, at `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe`.

### By hand: the Perform Backup button

On the admin page, open the ☰ menu and click **Perform Backup**. A folder window opens **on the PC running XAMPP** (not in the browser of whoever clicked). Choose a folder, and a message confirms where the backup was saved.

### Automatically: `ScheduleBackup.exe` and Windows Task Scheduler

`ScheduleBackup.exe`, in the main `LLM` folder, makes the same backup without the website. Windows **Task Scheduler** can run it for you, for example every night.

**1. Tell it where to save backups.** Open `config.json` and set `BACKUP_PATH` to a folder. In JSON, every backslash is written twice:

```json
"BACKUP_PATH": "C:\\Backups\\SOPAgent"
```

If `BACKUP_PATH` is empty, `ScheduleBackup.exe` opens a folder window and waits for someone to choose. That's fine when you run it by hand, but a scheduled run would sit waiting, so always set it before scheduling.

**2. Test it by hand.** Double-click `ScheduleBackup.exe`. It reads the MySQL login from the project's `FrontEnd\passwords.txt` (step 5.2), and a new `SOPagent<date>.sql` should appear in your backup folder.

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

Backups aren't deleted automatically. Clear out old ones from the backup folder now and then.

### Restoring a backup

Restoring replaces the four tables with their contents at the time of the backup. Anything saved after the backup is lost. In **http://localhost/phpmyadmin**, open the **Import** tab, choose the `.sql` file and click **Import**.

---

Next: [6. Troubleshooting](6-Troubleshooting.md)
