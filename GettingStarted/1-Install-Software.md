# 1. Install the software

You need four programs. Everything here is free and runs on Windows.

| Program | What it's for | Needed if… |
|---|---|---|
| Python 3.12 | Converting, cleaning and embedding your SOPs; answering questions on your PC | Always |
| Ollama + the Qwen model | Runs the AI model that writes the answers | Always. The website answers through your PC; Colab installs its own copy for testing. |
| XAMPP | Runs the website (Apache web server, PHP and MySQL database) | Always |
| Composer | Downloads the chart library used by the admin dashboard | Always |

## Python

1. Download Python 3.12 from [python.org/downloads](https://www.python.org/downloads/). It's the version this project was built and tested with.
2. Run the installer. On the first screen, tick **"Add python.exe to PATH"**, then click **Install Now**.
3. Turn off Windows' fake Python. Open **Settings → Apps → Advanced app settings → App execution aliases** and switch off **python.exe** and **python3.exe** (both say "App Installer"). If you skip this, typing `python` may open the Microsoft Store instead of running Python.
4. Close and reopen any terminal (or VS Code) so it picks up the change.

### How to run a Python file

All the scripts in this guide are run from a terminal. In VS Code, use **Terminal → New Terminal**; outside it, open **PowerShell** from the Start menu.

Move into the folder with `cd`, then run the script with `py`:

```powershell
cd "C:\path\to\LLM\PreProcessing"
py TextCleanUp.py
```

Where you run from matters for one script:

| Script | Run it from |
|---|---|
| `PreProcessing/PyPDF.py` | Anywhere |
| `PreProcessing/TextCleanUp.py` | **Inside the `PreProcessing` folder.** It looks for `Raw_Text` relative to where you are. |
| `EmbedScript.py`, `LocalAITest.py` | Anywhere |

VS Code's ▶ Run button runs from the folder you opened in VS Code, usually `LLM`. That's fine for every script except `TextCleanUp.py`; use the terminal for that one.

### Install the Python packages

Run this once, from any folder:

```powershell
py -m pip install pymupdf crossfiledialog numpy sentence-transformers requests
```

| Package | Used by |
|---|---|
| `pymupdf` | `PyPDF.py`, which reads the text out of PDFs |
| `crossfiledialog` | `PyPDF.py`, which opens a window to choose your SOP folder |
| `sentence-transformers` | `EmbedScript.py` and `LocalAITest.py`, which run the embedding model. It also installs PyTorch, which is a large download. |
| `numpy` | Storing and searching the vectors |
| `requests` | `LocalAITest.py`, which talks to Ollama |

> `requirement.txt` in the main folder is out of date: it doesn't list `crossfiledialog`, and it mentions a `Setup.bat` that doesn't exist. Use the command above instead.

**Check:** `py -c "import pymupdf, crossfiledialog, sentence_transformers; print('ok')"` prints `ok`.

## Ollama and the Qwen model

The website answers every question through `LocalAITest.py` on your PC, which needs Ollama.

1. Download and install Ollama from [ollama.com/download](https://ollama.com/download).
2. Ollama starts by itself and sits in the system tray (bottom-right of the taskbar). It must be running whenever questions are answered.
3. Download the model. This is about 1.3 GB, and only needed once:

   ```powershell
   ollama pull qwen3.5:0.8b
   ```

**Check:** `ollama list` shows `qwen3.5:0.8b`.

The model name is set in `LocalAITest.py` (`MODEL = "qwen3.5:0.8b"`) and in the Colab notebook. If you pull a different model, change it in both places.

## XAMPP (web server, PHP and MySQL)

1. Download XAMPP for Windows from [apachefriends.org](https://www.apachefriends.org/). It was tested with Apache 2.4, PHP 8.2 and MariaDB 10.4, which is XAMPP's version of MySQL.
2. Install it to the default folder, `C:\xampp`. Keep at least **Apache**, **MySQL**, **PHP** and **phpMyAdmin** ticked.
3. Open the **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**. Start them from the Control Panel, not as Windows services (the "Svc" tick boxes). Services run as a different Windows account, and that account can't find the Python packages and models you installed.

**Check:** [http://localhost](http://localhost) shows the XAMPP welcome page.

## Composer (PHP packages)

The admin dashboard draws its chart with the `c-pchart` library. That library isn't stored in the project, so Composer has to download it.

1. Download and run **Composer-Setup.exe** from [getcomposer.org/download](https://getcomposer.org/download/). When it asks for PHP, point it at `C:\xampp\php\php.exe`.
2. You'll run `composer install` in step 5.

**Check:** `composer --version` prints a version number. Open a new terminal first.

---

Next: [2. Prepare your SOPs](2-Prepare-Your-SOPs.md)
