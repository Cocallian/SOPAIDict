# 5. Set up the website

The website is the `FrontEnd` folder. It's a set of PHP pages run by XAMPP's Apache web server, with XAMPP's MySQL storing accounts, questions and answers.

| Page | What it does |
|---|---|
| `login.php` | The login page. It's the default page, set in `.htaccess`. |
| `dataconn.php` | Checks logins. On the first login it creates the database, its tables and two starting accounts. |
| `Agent.php` | The question page for normal users. It saves each question, runs `LocalAITest.py` and saves the answer. |
| `adminAgent.php` | The same for admins, plus a ☰ dashboard with usage numbers and a chart |

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

All three PHP pages read your MySQL login and the Python paths from `passwords.txt`, next to them in `C:\xampp\htdocs\SOPAgent`. It's kept out of git, so a fresh copy of the project doesn't have one.

Create it in Notepad. When saving, choose **Save as type: All files**, so it isn't saved as `passwords.txt.txt`. Paste this in and fill it out:

```ini
; Passwords the PHP pages read with parse_ini_file().
; Keep every value inside double quotes. Do not commit this file to git.

[database]
db_host = "127.0.0.1"
db_user = "root"
db_pass = ""

[paths]
; Agent.php and adminAgent.php run LocalAITest.py with this Python for every question
python = "C:\Users\YOUR-NAME\AppData\Local\Programs\Python\Python312\python.exe"
script = "C:\path\to\LLM\LocalAITest.py"
```

| Setting | What to put |
|---|---|
| `db_host`, `db_user` | Leave as they are for XAMPP. |
| `db_pass` | XAMPP's MySQL `root` user has **no password** out of the box, so leave it as `""`. If you've set one in phpMyAdmin, put it here. |
| `python` | Your Python. To find it, run `py -c "import sys; print(sys.executable)"` in a terminal. |
| `script` | `LocalAITest.py` in the **project** folder, not in `htdocs`. It finds `config.json` and your passages relative to its own location. |

- Keep **every value in double quotes**, especially passwords containing `;`, `!`, `=` or `#`. Without quotes, PHP can misread them.
- Write paths with single backslashes, as shown.

`.htaccess` stops browsers from opening `passwords.txt`, and the project's `.gitignore` keeps it out of git.

## 5.3 Start Apache and MySQL

In the **XAMPP Control Panel**, click **Start** next to **Apache** and **MySQL**. Both should turn green. Start them from the Control Panel, not as Windows services: answering needs Apache to run as *your* Windows user, so it can find the Python packages and models you installed.

## 5.4 First login

1. Go to **http://localhost/SOPAgent/**. The login page should appear.
2. Log in with username **`admin`** and password **`123`**. This first login creates the `SOPAgent` database, its four tables (`Admins`, `Users`, `Prompts`, `Responses`) and two starting accounts: `admin` / `123` and `user` / `123`.
3. You land on the admin question page. Type a question and press **Enter**. An animation runs while the answer is prepared; this can take a minute or more.
4. The answer appears with its sources. The ☰ menu (top left) shows the dashboard.
5. **Change both starting passwords straight away** (see 5.5). Everyone knows `123`.

## 5.5 Managing accounts

- **Admin or user is decided by the username.** A username starting with `admin` is checked against the `Admins` table and gets the admin page. Every other username is checked against `Users`.
- **Adding people:** the **Add new user** form in the admin menu doesn't save anything yet. Add accounts in phpMyAdmin instead:
  1. Go to **http://localhost/phpmyadmin**.
  2. Pick the **SOPAgent** database, then open the **SQL** tab.
  3. Run, for example:

     ```sql
     INSERT INTO Users  (Username, PasswordHash) VALUES ('jane',      SHA2('their-password', 256));
     INSERT INTO Admins (Username, PasswordHash) VALUES ('admin.sam', SHA2('their-password', 256));
     ```

- **Changing a password**, including the two starting accounts:

  ```sql
  UPDATE Admins SET PasswordHash = SHA2('new-password', 256) WHERE Username = 'admin';
  UPDATE Users  SET PasswordHash = SHA2('new-password', 256) WHERE Username = 'user';
  ```

  `dataconn.php` only adds `admin` and `user` when they don't exist yet, so a changed password stays changed.

## 5.6 Letting other people use it

Anyone on the same network can open `http://YOUR-PC-IP/SOPAgent/` if Windows Firewall allows Apache. Run `ipconfig` to find your PC's IP address. Every question is answered by your PC, so it must stay on with XAMPP and Ollama running.

This setup is meant for a trusted local network:
- Passwords are stored as unsalted SHA-256 hashes.
- The MySQL password is stored as plain text in `passwords.txt`.
- There's no HTTPS.

Don't expose it to the internet without hardening those first.

---

Next: [6. Troubleshooting](6-Troubleshooting.md)
