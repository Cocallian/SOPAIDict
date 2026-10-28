# 1. Install the software

Most of the project's programs are ready-made `.exe` files, so they don't need Python. You still need these programs. Everything here is free and runs on Windows.

| Program | What it's for | Needed if… |
|---|---|---|
| Python 3.12 | Running `EmbedScript.py`, which embeds your SOPs (step 3) | Always |
| Ollama + the Qwen model | Runs the AI model that writes the answers | Always. The website answers through your PC; Colab installs its own copy for testing. |
| XAMPP | Runs the website (Apache web server, PHP and MySQL database) | Always |
| Composer | Downloads the chart library used by the admin dashboard | Always |

## Python

1. Download Python 3.12 from [python.org/downloads](https://www.python.org/downloads/). It's the version this project was built and tested with.
2. Run the installer. On the first screen, tick **"Add python.exe to PATH"**, then click **Install Now**.
3. Turn off Windows' fake Python. Open **Settings → Apps → Advanced app settings → App execution aliases** and switch off **python.exe** and **python3.exe** (both say "App Installer"). If you skip this, typing `python` may open the Microsoft Store instead of running Python.
4. Close and reopen any terminal (or VS Code) so it picks up the change.

### Install the Python packages

Run this once, from the main `LLM` folder:

```powershell
cd "C:\path\to\LLM"
py -m pip install -r requirements.txt
```

`requirements.txt` lists the packages `EmbedScript.py` needs. `sentence-transformers` also installs PyTorch, which is a large download.

**Check:** `py -c "import sentence_transformers; print('ok')"` prints `ok`.

## Running the `.exe` programs

Double-click the `.exe` in File Explorer. Each one opens a black console window that shows its progress and closes when it finishes, except `LocalAIServ.exe`, which keeps running (step 4).

The first time you run each one, Windows may show **"Windows protected your PC"**. Click **More info → Run anyway**. If your antivirus quarantines one, add an exception for that file.

## Ollama and the Qwen model

The website answers every question through `LocalAIServ.exe` on your PC, which needs Ollama.

1. Download and install Ollama from [ollama.com/download](https://ollama.com/download).
2. Ollama starts by itself and sits in the system tray (bottom-right of the taskbar). It must be running whenever questions are answered.
3. Download the model. This is about 1.3 GB, and only needed once:

   ```powershell
   ollama pull qwen3.5:0.8b
   ```

**Check:** `ollama list` shows `qwen3.5:0.8b`.

`LocalAIServ.exe` and the Colab notebook both use `qwen3.5:0.8b`. Using a different model means changing `MODEL` in `LocalAIServ.py` and the notebook, then rebuilding the `.exe` ([step 4.1](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)).

## XAMPP (web server, PHP and MySQL)

1. Download XAMPP for Windows from [apachefriends.org](https://www.apachefriends.org/). It was tested with Apache 2.4, PHP 8.2 and MariaDB 10.4, which is XAMPP's version of MySQL.
2. Install it to the default folder, `C:\xampp`. Keep at least **Apache**, **MySQL**, **PHP** and **phpMyAdmin** ticked.
3. Open the **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**. Start them from the Control Panel, not as Windows services (the "Svc" tick boxes). The admin page's **Perform Backup** button opens a folder window, and only Apache started from the Control Panel can show it on your desktop.

**Check:** [http://localhost](http://localhost) shows the XAMPP welcome page.

## Composer (PHP packages)

The admin dashboard draws its chart with the `c-pchart` library. That library isn't stored in the project, so Composer has to download it.

1. Download and run **Composer-Setup.exe** from [getcomposer.org/download](https://getcomposer.org/download/). When it asks for PHP, point it at `C:\xampp\php\php.exe`.
2. You'll run `composer install` in step 5.

**Check:** `composer --version` prints a version number. Open a new terminal first.

---

Next: [2. Prepare your SOPs](2-Prepare-Your-SOPs.md)
