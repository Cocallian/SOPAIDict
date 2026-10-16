# 5. Set up the website

The website is the `FrontEnd` folder. It's a set of PHP pages run by XAMPP's Apache web server, with XAMPP's MySQL storing accounts, questions and answers.

| Page | What it does |
|---|---|
| `login.php` | The login page. It's the default page, set in `.htaccess`. |
| `dataconn.php` | Checks logins; creates the database the first time; saves each question, gets the answer (Colab or local) and saves it |
| `Agent.php` | The question page for normal users |
| `adminAgent.php` | The question page for admins, plus a ☰ dashboard with usage numbers and a chart |
| `passwords.txt` | Your database login, starting accounts and Colab address. You create this. |

## 5.1 Copy the website into XAMPP

Apache only serves files inside `C:\xampp\htdocs`.

1. Create a folder such as `C:\xampp\htdocs\SOPAgent`.
2. Copy **everything** from the project's `FrontEnd` folder into it, including `.htaccess`. That file blocks browsers from downloading `passwords.txt`.
3. Download the chart library into the same folder:

   ```powershell
   cd C:\xampp\htdocs\SOPAgent
   composer install
   ```

   This creates a `vendor` folder. Without it, the admin page shows an error about `vendor/autoload.php`.

The copy in `htdocs` is the one that runs. If you change a file in the project's `FrontEnd` folder later, copy it across again.

## 5.2 Start Apache and MySQL

In the **XAMPP Control Panel**, click **Start** next to **Apache** and **MySQL**. Both should turn green. Start them from the Control Panel, not as Windows services: answering locally needs Apache to run as *your* Windows user, so it can find the Python packages and models you installed.

## 5.3 Point the website at Python

`dataconn.php` runs `LocalAITest.py` for local answers, and when Colab can't be reached. Its two paths are set at the top of the file and currently point at the original developer's PC. Open `C:\xampp\htdocs\SOPAgent\dataconn.php` and change lines 5–6:

```php
$PYTH = "C:\\Users\\YOUR-NAME\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
$SCRIPT = "C:\\path\\to\\LLM\\LocalAITest.py";
```

- To find your Python path, run `py -c "import sys; print(sys.executable)"` in a terminal.
- Write every `\` as `\\` inside these PHP strings.
- `$SCRIPT` must point at `LocalAITest.py` in the project folder, **not** in `htdocs`, because it finds your passages relative to its own location.

## 5.4 Fill in `passwords.txt`

Create `C:\xampp\htdocs\SOPAgent\passwords.txt` in Notepad. When saving, choose **Save as type: All files**, so it isn't saved as `passwords.txt.txt`. Paste this in and fill it out:

```ini
; Passwords the PHP pages read with parse_ini_file().
; Keep every value inside double quotes. Do not commit this file to git.

[database]
db_host = "127.0.0.1"
db_user = "root"
db_pass = ""

[seed_accounts]
; dataconn.php creates these accounts if they don't exist yet
admin_user = "admin"
admin_pass = "choose-an-admin-password"
user_user  = "user"
user_pass  = "choose-a-user-password"

[answers]
; Leave colab_url empty to answer with LocalAITest.py on this PC.
; To use the Colab notebook instead, paste the address its last cell prints (it ends in /ask).
; The address changes every time the notebook is restarted. If Colab can't be reached, the local model answers.
colab_url = ""
```

| Setting | What to put |
|---|---|
| `db_host`, `db_user` | Leave as they are for XAMPP. |
| `db_pass` | XAMPP's MySQL `root` user has **no password** out of the box, so leave it as `""`. If you've set one, put it here. |
| `admin_user`, `admin_pass` | The first admin account, created on the first login. **The admin username must start with `admin`** (see 5.6). |
| `user_user`, `user_pass` | The first normal user account. |
| `colab_url` | `""` for local answers, or the address from the Colab notebook's last cell ([step 4.3](4-Choose-Local-or-Colab.md#43-google-colab-answering-on-a-free-gpu)). |

Keep **every value in double quotes**, especially passwords containing `;`, `!`, `=` or `#`. Without quotes, PHP can misread them.

The project's `.gitignore` already stops git from committing `FrontEnd/passwords.txt`. Keep it that way.

## 5.5 First login

1. Go to **http://localhost/SOPAgent/**. The login page should appear.
2. Log in with your `admin_user` and `admin_pass`. This first login creates the `SOPAgent` database, its four tables (`Admins`, `Users`, `Prompts`, `Responses`) and the two starting accounts.
3. You land on the admin question page. Type a question and press **Enter**. An animation runs while the answer is prepared; locally this can take a minute or more.
4. The answer appears with its sources. The ☰ menu (top left) shows the dashboard.

**Check:** open **http://localhost/SOPAgent/passwords.txt**. You must get **403 Forbidden**. If you can see your passwords instead, `.htaccess` wasn't copied or isn't being read. Fix that before anyone else uses the site.

## 5.6 Managing accounts

- **Admin or user is decided by the username.** A username starting with `admin` is checked against the `Admins` table and gets the admin page. Every other username is checked against `Users`.
- **Adding people:** the **Add new user** form in the admin menu doesn't save anything yet. Add accounts in phpMyAdmin instead:
  1. Go to **http://localhost/phpmyadmin**.
  2. Pick the **SOPAgent** database, then open the **SQL** tab.
  3. Run, for example:

     ```sql
     INSERT INTO Users  (Username, PasswordHash) VALUES ('jane',      SHA2('their-password', 256));
     INSERT INTO Admins (Username, PasswordHash) VALUES ('admin.sam', SHA2('their-password', 256));
     ```

- **Changing a password:** editing `passwords.txt` after the first login does **not** change existing accounts. Change them in phpMyAdmin:

  ```sql
  UPDATE Users SET PasswordHash = SHA2('new-password', 256) WHERE Username = 'user';
  ```

## 5.7 Letting other people use it

Anyone on the same network can open `http://YOUR-PC-IP/SOPAgent/` if Windows Firewall allows Apache. Run `ipconfig` to find your PC's IP address. Every question is handled by your PC, or by your Colab session, so your PC must stay on with XAMPP running.

This setup is meant for a trusted local network:
- Passwords are stored as unsalted SHA-256 hashes.
- The starting accounts come from a text file.
- There's no HTTPS.

Don't expose it to the internet without hardening those first.

---

Next: [6. Troubleshooting](6-Troubleshooting.md)
