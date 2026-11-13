# 1. Install the software

The project runs from its Python scripts, so you need Python and a few other programs. Everything here is free and runs on Windows.

| Program | What it's for | Needed if… |
|---|---|---|
| Python 3.12 | Runs the scripts that prepare your SOPs and the answer server | Always |
| Ollama + the Qwen model | Runs the AI model that writes the answers | Always. The website answers through your PC; Colab installs its own copy for testing. |
| XAMPP | Runs the website (Apache web server, PHP and MySQL database) | Always |
| Composer | Downloads the chart library used by the admin dashboard | Always |
| MySQL Server 8.0 | Provides `mysqldump`, which makes the database backups | You want backups |

## Python

1. Download Python 3.12 from [python.org/downloads](https://www.python.org/downloads/). It's the version this project was built and tested with.
2. Run the installer. On the first screen, tick **"Add python.exe to PATH"**, then click **Install Now**.
3. Turn off Windows' fake Python. Open **Settings → Apps → Advanced app settings → App execution aliases** and switch off **python.exe** and **python3.exe** (both say "App Installer"). If you skip this, typing `python` may open the Microsoft Store instead of running Python.
4. Close and reopen any terminal (or VS Code) so it picks up the change.

### Make the project's Python environment and install the packages

The website's **Start server** button starts the answer server with the Python in a folder called `.venv` inside the project, so you need to make it exactly there. Run this once, from the main `LLM` folder:

```powershell
cd "C:\path\to\LLM"
py -m venv .venv
.venv\Scripts\python.exe -m pip install -r requirements.txt
```

`requirements.txt` lists everything the scripts need. `sentence-transformers` also installs PyTorch, which is a large download, so this takes a while.

From now on, run every script in this guide with `.venv\Scripts\python.exe` instead of `py`, for example `.venv\Scripts\python.exe EmbedScript.py`. (Or run `.venv\Scripts\Activate.ps1` once in a terminal, then use plain `python`.)

**Check:** `.venv\Scripts\python.exe -c "import sentence_transformers, flask, pandas; print('ok')"` prints `ok`.

## Ollama and the Qwen model

The website answers every question through `LocalAIServ.py` on your PC, which needs Ollama.

1. Download and install Ollama from [ollama.com/download](https://ollama.com/download). Keep the default install folder (`%LOCALAPPDATA%\Programs\Ollama`); that's where `config.json`'s `OLLAMA_PATH` points.
2. Download the model. This is about 1.3 GB, and only needed once:

   ```powershell
   ollama pull qwen3.5:0.8b
   ```

3. You don't have to start Ollama yourself. `LocalAIServ.py` starts it when it's not running, and the admin page's **Stop server** button stops it again. If the Ollama tray icon is already running, it's used as it is.

**Check:** `ollama list` shows `qwen3.5:0.8b`.

The model is named in `config.json` (`MODEL_NAME`); an admin can change it on the Settings page ([step 5.6](5-Set-Up-the-Website.md#56-settings-and-reports)). The Colab notebook has its own copy of the name.

## XAMPP (web server, PHP and MySQL)

1. Download XAMPP for Windows from [apachefriends.org](https://www.apachefriends.org/). It was tested with Apache 2.4, PHP 8.2 and MariaDB 10.4, which is XAMPP's version of MySQL.
2. Install it to the default folder, `C:\xampp`. Keep at least **Apache**, **MySQL**, **PHP** and **phpMyAdmin** ticked.
3. Turn on the **zip** PHP extension, which Composer needs to unpack the chart library. Open `C:\xampp\php\php.ini` in a text editor, find the line `;extension=zip` and remove the `;` so it reads `extension=zip`. Save it. (`mysqli`, `mbstring` and `gd` are on already; the website and the chart need them.)
4. Open the **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**.

**Check:** [http://localhost](http://localhost) shows the XAMPP welcome page, and `C:\xampp\php\php.exe -m` lists `zip`, `mysqli` and `gd`.

## Composer (PHP packages)

The admin dashboard draws its chart with the `c-pchart` library. That library isn't stored in the project, so Composer has to download it.

1. Download and run **Composer-Setup.exe** from [getcomposer.org/download](https://getcomposer.org/download/). When it asks for PHP, point it at `C:\xampp\php\php.exe`.
2. You'll run `composer install` in step 5.

**Check:** `composer --version` prints a version number. Open a new terminal first. If `composer` later says *"The zip extension and unzip/7z commands are both missing"*, the zip line in `php.ini` above wasn't saved.

## MySQL Server 8.0 (only for backups)

The backup buttons and `ScheduleBackup.exe` run `mysqldump` from `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe`. XAMPP's own copy is in a different place, so install MySQL Server 8.0 from [dev.mysql.com/downloads/installer](https://dev.mysql.com/downloads/installer/) (choose **Server only**) if you want backups. It's only used for its `mysqldump` program: leave XAMPP's MySQL as the database. If you'd rather point the project at XAMPP's `mysqldump`, see [Troubleshooting](6-Troubleshooting.md#backups).

---

Next: [2. Prepare your SOPs](2-Prepare-Your-SOPs.md)
