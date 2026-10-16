# 1. Install the software

You need four programs. Everything here is free and runs on Windows.

| Program | What it's for | Needed if… |
|---|---|---|
| Python 3.12 | Converting, cleaning and embedding your SOPs; answering questions locally | Always |
| Ollama + the Qwen model | Runs the AI model that writes the answers | You answer questions on your own PC (step 4). Colab installs its own copy. |
| XAMPP | Runs the website (Apache web server, PHP and MySQL database) | Always |
| Composer | Downloads the chart library used by the admin dashboard | Always |

## Python

1. Download Python 3.12 from [python.org/downloads](https://www.python.org/downloads/). It's the version this project was built and tested with.
2. Run the installer. On the first screen, tick **"Add python.exe to PATH"**, then click **Install Now**.
3. Turn off Windows' fake Python. Open **Settings → Apps → Advanced app settings → App execution aliases** and switch off **python.exe** and **python3.exe** (both say "App Installer"). If you skip this, typing `python` may open the Microsoft Store instead of running Python.
4. Close and reopen any terminal (or VS Code) so it picks up the change.

### How to run a Python file

All the scripts in this guide are run from a terminal. In VS Code, use **Terminal → New Terminal**; outside it, open **PowerShell** from the Start menu.

1. Move into the folder the script is in with `cd`, then run it with `py`:

   ```powershell
   cd "C:\path\to\LLM\PreProcessingSAHPRAGuidelines"
   py TextCleanUp.py
   ```

2. Each step in this guide says which folder to run from. It matters: `PyPDF.py` and `TextCleanUp.py` look for their folders (`Guidelines`, `Raw_Text`) relative to where you are. Run from the wrong folder and they won't find your files.

`py` is the Python launcher that the installer adds; `python` works too once the aliases above are switched off. VS Code's ▶ Run button also works, but it runs from the folder you opened in VS Code, which is the wrong folder for `PyPDF.py` and `TextCleanUp.py`. Use the terminal for those two.

### Install the Python packages

Run this once, from any folder:

```powershell
py -m pip install requests beautifulsoup4 pymupdf sentence-transformers numpy
```

| Package | Used by |
|---|---|
| `pymupdf` | `PyPDF.py`, which reads text out of PDFs |
| `sentence-transformers` | `EmbedScript.py` and `LocalAITest.py`, which run the BGE embedding model. It also installs PyTorch, which is a large download. |
| `numpy` | Storing and searching the vectors |
| `requests` | `LocalAITest.py`, which talks to Ollama |
| `beautifulsoup4` | `DownloadAll.py` only (downloading SAHPRA's public guidelines) |

> `requirement.txt` in the main folder lists these too, but it contains explanatory text that `pip install -r` can't read. Use the command above instead.

**Check:** `py -c "import pymupdf, sentence_transformers; print('ok')"` prints `ok`.

## Ollama and the Qwen model

You need this if you answer questions on your own PC (step 4). You can skip it if you'll only use Google Colab, which installs its own copy. Installing it anyway is worthwhile: the website falls back to your PC whenever Colab can't be reached.

1. Download and install Ollama from [ollama.com/download](https://ollama.com/download).
2. Ollama starts by itself and sits in the system tray (bottom-right of the taskbar). It must be running whenever questions are answered on your PC.
3. Download the model. This is about 1.3 GB, and only needed once:

   ```powershell
   ollama pull qwen3.5:0.8b
   ```

**Check:** `ollama list` shows `qwen3.5:0.8b`.

The model name is set in `LocalAITest.py` (`MODEL = "qwen3.5:0.8b"`) and in the Colab notebook. If you pull a different model, change it in both places.

## XAMPP (web server, PHP and MySQL)

1. Download XAMPP for Windows from [apachefriends.org](https://www.apachefriends.org/). It was tested with Apache 2.4, PHP 8.2 and MariaDB 10.4, which is XAMPP's version of MySQL.
2. Install it to the default folder, `C:\xampp`. When asked which components to install, keep at least **Apache**, **MySQL**, **PHP** and **phpMyAdmin**.
3. Open the **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**. Start them from the Control Panel, not as Windows services (the "Svc" tick boxes). Services run as a different Windows account, and that account can't find the Python packages and models you installed.

XAMPP's PHP already has the three extensions this project needs switched on: `mysqli` (database), `openssl` (talking to Colab over HTTPS) and `gd` (drawing the dashboard chart).

**Check:** [http://localhost](http://localhost) shows the XAMPP welcome page.

## Composer (PHP packages)

The admin dashboard draws its chart with the `c-pchart` library. That library isn't stored in the project, so Composer has to download it.

1. Download and run **Composer-Setup.exe** from [getcomposer.org/download](https://getcomposer.org/download/). When it asks for PHP, point it at `C:\xampp\php\php.exe`.
2. You'll run `composer install` in step 5, after copying the website into XAMPP.

**Check:** `composer --version` prints a version number. Open a new terminal first.

---

Next: [2. Prepare your SOPs](2-Prepare-Your-SOPs.md)
