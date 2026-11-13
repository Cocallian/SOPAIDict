# 6. Troubleshooting

Find the message or symptom you're seeing. Messages in quotes are exactly what the programs and pages print.

## Running the scripts

| Message or symptom | Cause | Fix |
|---|---|---|
| "Python was not found; run without arguments to install from the Microsoft Store" | Windows' Store shortcut is being found before the real Python. | Switch off the **python.exe** and **python3.exe** aliases ([step 1](1-Install-Software.md#python)), then close and reopen the terminal. Or use `py` instead of `python`. |
| `'py' is not recognized…` | Python was installed without the launcher or without PATH. | Run the Python installer again, choose **Modify**, and tick **py launcher** and **Add Python to environment variables**. |
| `ModuleNotFoundError: No module named '…'` | The packages aren't installed, or were installed for a different Python than the one running the script. | Run the script with `.venv\Scripts\python.exe`, and install the packages into it: `.venv\Scripts\python.exe -m pip install -r requirements.txt` ([step 1](1-Install-Software.md#make-the-projects-python-environment-and-install-the-packages)). |
| `.venv\Scripts\python.exe` isn't found | The environment hasn't been made, or it was made in another folder. | From the main `LLM` folder: `py -m venv .venv`, then install the packages. |
| An `.exe` (`ScheduleBackup.exe`) disappears, or the antivirus reports it | Some antivirus programs wrongly flag `.exe` files built with PyInstaller. | Restore it from quarantine and add an exception for that file. |
| "Windows protected your PC" when opening `ScheduleBackup.exe` | Windows SmartScreen doesn't recognise new programs. | Click **More info → Run anyway**. |

## Installing

| Message or symptom | Cause | Fix |
|---|---|---|
| Composer says "The zip extension and unzip/7z commands are both missing" | PHP's zip extension is switched off. | In `C:\xampp\php\php.ini` change `;extension=zip` to `extension=zip`, save, and run `composer install` again. Check with `php -m`. |
| Composer or a PHP tool reports a missing extension (`mbstring`, `gd`, `mysqli`) | The extension is commented out in `php.ini`, or a different PHP is running. | Remove the `;` in front of it in `C:\xampp\php\php.ini`. Run `where php` to check that `C:\xampp\php` is the PHP being used. A PHP installed somewhere else has its own `php.ini`. |

## Preparing and embedding SOPs

| Message or symptom | Cause | Fix |
|---|---|---|
| "No folder chosen." | The folder window in `PyPDF.py` was closed or cancelled. | Run it again and choose your SOP folder. |
| An error mentioning `config.json`, such as `FileNotFoundError` or `JSONDecodeError` | `config.json` is missing from the main `LLM` folder, or isn't valid JSON (for example, a missing quote or comma). | Fix or recreate it ([step 2.1](2-Prepare-Your-SOPs.md#21-set-your-organisations-name)). |
| `PyPDF.py` finishes without any `Processing …` lines | The chosen folder has no PDFs directly in it. Subfolders and other file types are skipped. | Choose the folder that contains the PDFs themselves, or move them out of subfolders. |
| "Done: 0 passages from 0 files" | `TextCleanUp.py` can't see `Raw_Text`: it was run from the wrong folder. | `cd` into `PreProcessing` first ([step 2.6](2-Prepare-Your-SOPs.md#26-clean-and-split-the-text-with-textcleanuppy)). |
| "Skipping …: almost no text (probably a scanned PDF)" | The PDF is a scan with no real text. | OCR it, then run `PyPDF.py` again ([step 2.2](2-Prepare-Your-SOPs.md#22-get-your-sops-into-the-right-format)). |
| Old or SAHPRA documents appear among your SOPs | `Raw_Text` still held text files from earlier. | Clear `Raw_Text` and run steps 2.4 onwards again ([step 2.3](2-Prepare-Your-SOPs.md#23-clear-out-old-text-files)). |
| `EmbedScript.py` stops with `FileNotFoundError` for `passages.jsonl` | `TextCleanUp.py` hasn't been run, or couldn't find `Raw_Text`. | Run it from inside `PreProcessing`. |
| Citations show the file name, a blank title, or the wrong SOP number | `TextCleanUp.py` didn't recognise your SOP numbers or titles. | Adjust `DOC_NUMBER` and the letterhead filter in `TextCleanUp.py` ([step 2.5](2-Prepare-Your-SOPs.md#25-optional-but-recommended-teach-the-clean-up-script-your-sop-numbers)), then run it and the rest of the sequence again. |
| "The passages have changed since they were embedded. Run EmbedScript.py again." | `passages.jsonl` in `Raw_Text` was remade by `TextCleanUp.py`, but the vectors in `Embeds` weren't. | Run `EmbedScript.py` again, then restart `LocalAIServ.py` ([step 3.3](3-Embed-Your-SOPs.md#33-when-to-run-it-again)). For Colab, run block 1.2 again too. |

## Answering on your PC (`LocalAIServ.py` and Ollama)

| Message or symptom | Cause | Fix |
|---|---|---|
| Answers cite old documents instead of your SOPs | `Raw_Text` still held old text files when you ran `TextCleanUp.py`, or `LocalAIServ.py` wasn't restarted after embedding. | Clear `Raw_Text` and run steps 2.4 to 3.1 again ([step 2.3](2-Prepare-Your-SOPs.md#23-clear-out-old-text-files)), then restart `LocalAIServ.py`. |
| Changes to your SOPs, `ORG_NAME` or the Settings page don't take effect | `LocalAIServ.py` reads everything once, when it starts. | Stop it and start it again. Make sure only one copy is running ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)). |
| "Ollama isn't running and … wasn't found. Start Ollama yourself or fix OLLAMA_PATH in config.json" | Ollama isn't installed in its normal folder. | Fix `OLLAMA_PATH` in `config.json`, or start Ollama yourself. |
| "Ollama isn't running. Start the Ollama app and try again." | Ollama stopped or didn't start. | Restart `LocalAIServ.py`, or start **Ollama** from the Start menu. |
| "Ollama error: model … not found" | The model named in `MODEL_NAME` was never downloaded. | `ollama pull qwen3.5:0.8b` (or the name in `config.json`). |
| "Not enough free memory to load the model. Close some programs and try again." | The PC is short on RAM. | Close programs. Lowering `SOURCES`, `CONTEXT_WINDOW` or `RUNNING_THREADS` also reduces memory use ([changing the settings](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)). |
| `llama-server process has terminated: exit status 0xc0000409 … GGML_ASSERT(ctx->mem_buffer != NULL) failed` | Ollama ran out of memory while loading Qwen. Despite the wording, this isn't a security problem. | Close programs, stop and start the server, and lower `CONTEXT_WINDOW` if it keeps happening. |
| An error about not finding `bge-small-en-v1.5`, Hugging Face, or the local cache | `LocalAIServ.py` runs offline and BGE isn't in the cache for this Windows user. | Run `EmbedScript.py` once while online, as the same Windows user that runs `LocalAIServ.py` ([step 3.2](3-Embed-Your-SOPs.md#32-run-it-on-the-pc-that-will-answer-questions)). |
| `KeyError: 'ORG_NAME'` (or another setting name) | `config.json` is missing that setting. | Add it ([step 4.1](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)). |
| Answers take a long time | Qwen is running on the CPU, or other people's questions are queued ahead. | Expected on a laptop. Lowering `SOURCES` makes each answer faster. |

**Tip:** to see an answer, or an error, straight from the answer server without the website, send it a question from PowerShell as in [step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc). The server window also prints one line per request, which shows what the website asked for.

## Google Colab

| Message or symptom | Cause | Fix |
|---|---|---|
| "Couldn't find the project's PreProcessing/Raw_Text/passages.jsonl and PreProcessing/Embeds files" (block 1.2) | Colab can't see the project: it isn't synced to your Google Drive, the sync hasn't finished, or `EmbedScript.py` hasn't been run. | Sync the `LLM` folder with Google Drive for desktop, wait for it to finish, then run block 1.2 again ([step 4.2](4-Choose-Local-or-Colab.md#42-google-colab-testing-on-a-free-gpu)). |
| "Couldn't find config.json in the main project folder" | `config.json` isn't next to the `PreProcessing` folder in the synced project. | Check it's in the main `LLM` folder and synced. |
| Block 3 stops with `FileNotFoundError` for `config.json` | Block 1.2 didn't copy it (see above). | Fix that, then run block 1.2 and block 3 again. |
| "Ollama didn't start. Check the install step." | The install in block 2 failed or timed out. | Run block 2 again. If it keeps failing, use **Runtime → Disconnect and delete runtime**, then start again from block 1.2. |
| "Ollama isn't running. Run code block 2 first." | Block 2 wasn't run in this session. | Run block 2, then try again. |
| "Cannot connect to GPU backend" or insufficient quota | Colab's free GPU time has run out for now. | Wait (usually 12–24 hours), or use **Runtime → Change runtime type → CPU**, which is slower. |
| Very slow even on Colab | The notebook isn't using the GPU. | **Runtime → Change runtime type → T4 GPU**, then run the blocks again. |

## The website

| Message or symptom | Cause | Fix |
|---|---|---|
| "Database connection error, contact your system administrator for assistance" | MySQL isn't running, `FrontEndConfig.json` is missing from the `htdocs` folder, or `db_pass` is wrong. | Start MySQL in XAMPP. Check `FrontEndConfig.json` is next to `dataconn.php` and `db_pass` is right ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-frontendconfigjson)). XAMPP's default is empty: `""`. |
| Nobody can log in to a new, empty database | `FrontEndConfig.json` has no `seed_accounts` section (it is removed once a real admin has logged in), so no starting accounts are made. | Put the section back ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-frontendconfigjson)), then log in again. |
| The starting `admin` / `user` accounts stopped working | They are deleted automatically once an admin other than the starting admin logs in. | Log in as your own admin ([step 5.5](5-Set-Up-the-Website.md#55-managing-accounts)). |
| "The answer server isn't running. Start LocalAIServ.py and try again." | `LocalAIServ.py` isn't running. | Start it and leave its window open ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)), or press **Start server** on the admin page. |
| **Start server**: "… \.venv\Scripts\python.exe wasn't found" | There's no `.venv` next to `LocalAIServ.py`. | Make it ([step 1](1-Install-Software.md#make-the-projects-python-environment-and-install-the-packages)), or press **Change server file** and choose the right `LocalAIServ.py`. |
| **Start server** says "Server not running" again after a moment | The server stopped as it loaded: for example the passages and vectors don't match, or a package is missing. | Start it from a terminal ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)) to read its message. |
| "Incorrect Login details" with the right password | The admin username doesn't start with `admin`, or the password was changed in phpMyAdmin. | See [step 5.5](5-Set-Up-the-Website.md#55-managing-accounts). |
| "Already logged in on another device" | The account has a session already, possibly one that was closed without logging out. | Wait for the inactivity timeout, or have an admin press **Log out** next to it in the **Active users** card. |
| "The username must be an email ending in: …" | Usernames have to be email addresses at one of the allowed domains. | Use such an address, or add the domain under **Allowed email domains** on the Settings page ([step 5.6](5-Set-Up-the-Website.md#56-settings-and-reports)). |
| "Admin usernames must start with "admin"" or "User usernames can't start with "admin"" | The username has to match the role. | Pick a username that fits the role ([step 5.5](5-Set-Up-the-Website.md#55-managing-accounts)). |
| "The password needs 8+ characters with a number, lowercase, uppercase and special character" | The password is too weak. | Use a stronger one. |
| "The username … already exists" or "… already taken or already has an application waiting" | Someone already has or applied for that username. | Choose another, or approve or reject the application. |
| The admin page shows an error about `vendor/autoload.php` | The chart library wasn't downloaded. | Run `composer install` in the `htdocs` folder ([step 5.1](5-Set-Up-the-Website.md#51-copy-the-website-into-xampp)). |
| After about 5 minutes the page shows a "Maximum execution time" error or no answer | The answer took longer than the pages' 300-second limit, often because many questions were queued ahead. | Try again when it's quieter, or lower `SOURCES` ([changing the settings](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)). |
| Changes you made don't show up | You edited the project's `FrontEnd` folder, but Apache runs the copy in `htdocs`. | Copy the changed files into `C:\xampp\htdocs\SOPAgent`. |
| Settings or Reports pages show an error about the server | The answer server isn't running. | Start it ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)). |

## Settings and reports

| Message or symptom | Cause | Fix |
|---|---|---|
| A setting is refused with "… must be a whole number" or "… must be between …" | The value is outside the allowed range. | The message shows the range; enter a value inside it. |
| Saved settings have no effect | The answer server reads `config.json` only when it starts. | Stop and start it ([step 5.6](5-Set-Up-the-Website.md#56-settings-and-reports)). |
| Answers become nonsense after changing the embedding model | The vectors were made by the old model. | Run `EmbedScript.py` again with the same model, then restart the server ([step 3.4](3-Embed-Your-SOPs.md#34-changing-the-embedding-model)). |
| A report fails, or the server prints `ModuleNotFoundError` for `pandas`, `matplotlib`, `xhtml2pdf`, `jinja2` or `pymysql` | The `.venv` was made before those packages were added to `requirements.txt`. | `.venv\Scripts\python.exe -m pip install -r requirements.txt`, then restart the server. |
| The report says there's no data, or a date can't be chosen | Nothing was saved in that period, or the date is before the first entry or after today. | Pick a period that has questions in it. |

## Backups

| Message or symptom | Cause | Fix |
|---|---|---|
| "Incorrect password, the backup was not made" | The password box needs **your** admin password. | Enter the password of the admin you're logged in as. |
| "The backup server isn't running. Start LocalAIServ.py and try again" | All backups are made by the answer server. | Start it ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)). |
| "ScheduleBackup.exe wasn't found in …" | The local database backup button runs `ScheduleBackup.exe`, which isn't included. | Build it ([step 5.8](5-Set-Up-the-Website.md#58-backups)) and put it in the main `LLM` folder. |
| "Backup failed: … 1045 … Access denied" | `mysqldump` couldn't log in to MySQL with the details in `FrontEndConfig.json`. | Check `db_user` and `db_pass` in the project's `FrontEnd\FrontEndConfig.json` ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-frontendconfigjson)). |
| "Backup failed: …" saying the system cannot find the path, or `FileNotFoundError` for `mysqldump.exe` | MySQL Server 8.0's `mysqldump` isn't at `C:\Program Files\MySQL\MySQL Server 8.0\bin`. | Install MySQL Server 8.0 there, or point `MYSQLDUMP` at your `mysqldump.exe` (XAMPP has one in `C:\xampp\mysql\bin`) in `Backup\ScheduleBackup.py` and `APIS\backupapi.py`, then rebuild `ScheduleBackup.exe`. |
| "Backup failed: No Google client file (client_secret*.json) found in …" | The Google client file isn't in the main `LLM` folder. | Do the Google setup in [step 5.8](5-Set-Up-the-Website.md#58-backups). |
| The Google sign in never opens, or "A Google sign in is already waiting" | The sign in opens on the PC running the answer server, and a previous one is still waiting (it gives up after 2 minutes). | Look for the browser window on that PC, or wait 2 minutes and try again. |
| "redirect_uri_mismatch" in the Google sign in page | The client doesn't allow the sign in address. | Add `http://localhost:8765/` to the OAuth client's authorised redirect URIs, or make the client type **Desktop app**. |
| "Access blocked" or "not verified" at Google sign in | Your account isn't a test user of the app. | In **OAuth consent screen → Test users**, add the account. |
| Drive backups ask for a new sign in about once a week | While the consent screen is in **Testing**, Google ends the sign in after about 7 days. | Sign in again when asked, or publish the app in the Google console. |
| "No folder chosen, so no backup was made." | `BACKUP_PATH` in `config.json` is empty and the folder window was cancelled. | Choose a folder, or set `BACKUP_PATH` ([step 5.8](5-Set-Up-the-Website.md#58-backups)). |
| A scheduled backup never finishes, or no file appears | `BACKUP_PATH` is empty, so `ScheduleBackup.exe` is waiting for someone to choose a folder. | Set `BACKUP_PATH` in `config.json`, then end the waiting task in Task Scheduler (right-click → **End**). |
| Task Scheduler's **Last Run Result** isn't `0x0` | The backup failed. | Double-click `ScheduleBackup.exe` and read its message, or run it from a terminal. Check MySQL was running at the scheduled time. |
| An error about `FrontEndConfig.json` or `'database'` from `ScheduleBackup.exe` | The project's `FrontEnd\FrontEndConfig.json` is missing. | Copy it there from `htdocs` ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-frontendconfigjson)). |

## Poor answers

| Symptom | What to check |
|---|---|
| "I could not find it in the guidelines" for something that is in your SOPs | Search `PreProcessing/Raw_Text/passages.jsonl` (open it in VS Code) for a phrase from that section. If it isn't there, the clean-up removed it; see [what the clean-up can't handle](2-Prepare-Your-SOPs.md#what-the-clean-up-program-cant-handle). If it is there, try wording the question closer to the SOP's own terms, or raise `SOURCES`. |
| The answer cites the right SOP but says something wrong | Small models make mistakes. Always check the cited passage. A larger Ollama model (set `MODEL_NAME`, after `ollama pull`) gives better answers but is slower. |
| Answers stop mid-sentence | `LENGTH` limits answers to about 400 tokens. Raise it on the Settings page or in `config.json`, then restart the server ([changing the settings](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)). |
