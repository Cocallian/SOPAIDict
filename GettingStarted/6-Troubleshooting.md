# 6. Troubleshooting

Find the message or symptom you're seeing. Messages in quotes are exactly what the programs and pages print.

## Running the programs

| Message or symptom | Cause | Fix |
|---|---|---|
| "Windows protected your PC" when opening an `.exe` | Windows SmartScreen doesn't recognise new programs. | Click **More info → Run anyway**. |
| An `.exe` disappears, or the antivirus reports it | Some antivirus programs wrongly flag `.exe` files built with PyInstaller. | Restore it from quarantine and add an exception for that file. |
| An `.exe`'s window flashes and closes before you can read it | The window closes as soon as the program finishes, including after an error. | Run it from a terminal to read its messages: `cd` into its folder, then `.\Name.exe`. |
| "Python was not found; run without arguments to install from the Microsoft Store" | Windows' Store shortcut is being found before the real Python. | Switch off the **python.exe** and **python3.exe** aliases ([step 1](1-Install-Software.md#python)), then close and reopen the terminal. Or use `py` instead of `python`. |
| `'py' is not recognized…` | Python was installed without the launcher or without PATH. | Run the Python installer again, choose **Modify**, and tick **py launcher** and **Add Python to environment variables**. |
| `ModuleNotFoundError: No module named '…'` when running `EmbedScript.py` | The packages aren't installed, or were installed for a different Python. | Run `py -m pip install -r requirements.txt` from the main `LLM` folder ([step 1](1-Install-Software.md#install-the-python-packages)). |

## Preparing and embedding SOPs

| Message or symptom | Cause | Fix |
|---|---|---|
| "No folder chosen." | The folder window in `PyPDF.exe` was closed or cancelled. | Run it again and choose your SOP folder. |
| An error mentioning `config.json`, such as `FileNotFoundError` or `JSONDecodeError` | `config.json` is missing from the main `LLM` folder, or isn't valid JSON (for example, a missing quote or comma). | Recreate it as shown in [step 2.1](2-Prepare-Your-SOPs.md#21-set-your-organisations-name). |
| `PyPDF.exe` finishes without any `Processing …` lines | The chosen folder has no PDFs directly in it. Subfolders and other file types are skipped. | Choose the folder that contains the PDFs themselves, or move them out of subfolders. |
| "Done: 0 passages from 0 files" | `TextCleanUp.exe` can't see `Raw_Text`: it was moved out of `PreProcessing`, or run from a terminal in another folder. | Keep it in `PreProcessing`, and `cd` there first when using a terminal ([step 2.6](2-Prepare-Your-SOPs.md#26-clean-and-split-the-text-with-textcleanupexe)). |
| "Skipping …: almost no text (probably a scanned PDF)" | The PDF is a scan with no real text. | OCR it, then run `PyPDF.exe` again ([step 2.2](2-Prepare-Your-SOPs.md#22-get-your-sops-into-the-right-format)). |
| Old or SAHPRA documents appear among your SOPs | `Raw_Text` still held text files from earlier. | Clear `Raw_Text` and run steps 2.4 onwards again ([step 2.3](2-Prepare-Your-SOPs.md#23-clear-out-old-text-files)). |
| `EmbedScript.py` stops with `FileNotFoundError` for `passages.jsonl` | `TextCleanUp.exe` hasn't been run, or couldn't find `Raw_Text`. | Run it from inside `PreProcessing`. |
| Citations show the file name, a blank title, or the wrong SOP number | `TextCleanUp.exe` didn't recognise your SOP numbers or titles. | Adjust `DOC_NUMBER` and the letterhead filter in `TextCleanUp.py` and rebuild the `.exe` ([step 2.5](2-Prepare-Your-SOPs.md#25-optional-but-recommended-teach-the-clean-up-program-your-sop-numbers)), then run it and the rest of the sequence again. |
| "The passages have changed since they were embedded. Run EmbedScript.py again." | `passages.jsonl` in `Raw_Text` was remade by `TextCleanUp.exe`, but the vectors in `Embeds` weren't. | Run `EmbedScript.py` again, then restart `LocalAIServ.exe` ([step 3.3](3-Embed-Your-SOPs.md#33-when-to-run-it-again)). For Colab, run block 1.2 again too. |

## Answering on your PC (`LocalAIServ.exe` and Ollama)

| Message or symptom | Cause | Fix |
|---|---|---|
| Answers cite old documents instead of your SOPs | `Raw_Text` still held old text files when you ran `TextCleanUp.exe`, or `LocalAIServ.exe` wasn't restarted after embedding. | Clear `Raw_Text` and run steps 2.4 to 3.1 again ([step 2.3](2-Prepare-Your-SOPs.md#23-clear-out-old-text-files)), then restart `LocalAIServ.exe`. |
| Changes to your SOPs or `ORG_NAME` don't show up | `LocalAIServ.exe` loads everything once, when it starts. | Close it and start it again. Make sure only one copy is running ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)). |
| "Ollama isn't running. Start the Ollama app and try again." | Ollama is closed. | Start **Ollama** from the Start menu; check for the icon in the system tray. |
| "Ollama error: model … not found" | The Qwen model was never downloaded. | `ollama pull qwen3.5:0.8b` |
| "Not enough free memory to load the model. Close some programs and try again." | The PC is short on RAM. | Close programs. Lowering `TOP_K` or `num_ctx` also reduces memory use ([changing the settings](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)). |
| `llama-server process has terminated: exit status 0xc0000409 … GGML_ASSERT(ctx->mem_buffer != NULL) failed` | Ollama ran out of memory while loading Qwen. Despite the wording, this isn't a security problem. | Close programs, quit and restart Ollama from the tray icon, and lower `num_ctx` if it keeps happening. |
| An error about not finding `bge-small-en-v1.5`, Hugging Face, or the local cache | `LocalAIServ.exe` runs offline and BGE isn't in the cache for this Windows user. | Run `EmbedScript.py` once while online, as the same Windows user that runs `LocalAIServ.exe` ([step 3.2](3-Embed-Your-SOPs.md#32-run-it-on-the-pc-that-will-answer-questions)). |
| `KeyError: 'ORG_NAME'` | `config.json` has no `ORG_NAME` field. | Add it ([step 2.1](2-Prepare-Your-SOPs.md#21-set-your-organisations-name)). |
| Answers take a long time | Qwen is running on the CPU, or other people's questions are queued ahead. | Expected on a laptop. Lowering `TOP_K` makes each answer faster. |

**Tip:** to see an answer, or an error, straight from the answer server without the website, send it a question from PowerShell as in [step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc).

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
| "Database connection error, contact your system administrator for assistance" | MySQL isn't running, `passwords.txt` is missing from the `htdocs` folder, or `db_pass` is wrong. | Start MySQL in XAMPP. Check `passwords.txt` is next to `dataconn.php` and `db_pass` is right ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-passwordstxt)). XAMPP's default is empty: `""`. |
| "The answer server isn't running. Start LocalAITest.py and try again." | `LocalAIServ.exe` isn't running. (The message still uses the program's old name.) | Start `LocalAIServ.exe` and leave its window open ([step 4.1](4-Choose-Local-or-Colab.md#41-on-your-pc)). |
| "Incorrect Login details" with the right password | The admin username doesn't start with `admin`, or the password was changed in phpMyAdmin. | See [step 5.5](5-Set-Up-the-Website.md#55-managing-accounts). |
| "Admin usernames must start with "admin"" or "User usernames can't start with "admin"" | The **Add new user** form checks the username matches the role. | Pick a username that fits the role ([step 5.5](5-Set-Up-the-Website.md#55-managing-accounts)). |
| "The username … already exists" | Someone already has that username. | Choose another. |
| The admin page shows an error about `vendor/autoload.php` | The chart library wasn't downloaded. | Run `composer install` in the `htdocs` folder ([step 5.1](5-Set-Up-the-Website.md#51-copy-the-website-into-xampp)). |
| After about 5 minutes the page shows a "Maximum execution time" error or no answer | The answer took longer than the pages' 300-second limit, often because many questions were queued ahead. | Try again when it's quieter, or lower `TOP_K` ([changing the settings](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)). |
| Changes you made don't show up | You edited the project's `FrontEnd` folder, but Apache runs the copy in `htdocs`. | Copy the changed files into `C:\xampp\htdocs\SOPAgent`. |

## Backups

| Message or symptom | Cause | Fix |
|---|---|---|
| Nothing happens after clicking **Perform Backup** | The folder window opened on the PC running XAMPP, maybe behind other windows, or Apache runs as a Windows service and can't show windows. | Look on that PC's desktop. Start Apache from the XAMPP Control Panel, not as a service ([step 5.3](5-Set-Up-the-Website.md#53-start-everything)). |
| "Backup failed: … 1045 … Access denied" | `mysqldump` couldn't log in to MySQL with the details in `passwords.txt`. | Check `db_user` and `db_pass` in `passwords.txt`. For `ScheduleBackup.exe`, check the copy in the project's `FrontEnd` folder ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-passwordstxt)). |
| "Backup failed: …" saying the system cannot find the path, or `FileNotFoundError` for `mysqldump.exe` | MySQL Server 8.0's `mysqldump` isn't at `C:\Program Files\MySQL\MySQL Server 8.0\bin`. | Install MySQL Server 8.0 there, or point `MYSQLDUMP` in `ScheduleBackup.py` and the path in `adminAgent.php` at your `mysqldump.exe`, then rebuild `ScheduleBackup.exe`. |
| "No folder chosen, so no backup was made." | `BACKUP_PATH` in `config.json` is empty and the folder window was cancelled. | Choose a folder, or set `BACKUP_PATH` ([step 5.7](5-Set-Up-the-Website.md#57-backups)). |
| A scheduled backup never finishes, or no file appears | `BACKUP_PATH` is empty, so `ScheduleBackup.exe` is waiting for someone to choose a folder. | Set `BACKUP_PATH` in `config.json`, then end the waiting task in Task Scheduler (right-click → **End**). |
| Task Scheduler's **Last Run Result** isn't `0x0` | The backup failed. | Double-click `ScheduleBackup.exe` and read its message, or run it from a terminal. Check MySQL was running at the scheduled time. |
| An error about `passwords.txt` or `'database'` from `ScheduleBackup.exe` | The project's `FrontEnd\passwords.txt` is missing. | Copy it there from `htdocs` ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-passwordstxt)). |

## Poor answers

| Symptom | What to check |
|---|---|
| "I could not find it in the guidelines" for something that is in your SOPs | Search `PreProcessing/Raw_Text/passages.jsonl` (open it in VS Code) for a phrase from that section. If it isn't there, the clean-up removed it; see [what the clean-up can't handle](2-Prepare-Your-SOPs.md#what-the-clean-up-program-cant-handle). If it is there, try wording the question closer to the SOP's own terms, or raise `TOP_K`. |
| The answer cites the right SOP but says something wrong | Small models make mistakes. Always check the cited passage. A larger Ollama model (change `MODEL` in `LocalAIServ.py` and the Colab notebook, then rebuild `LocalAIServ.exe`) gives better answers but is slower. |
| Answers stop mid-sentence | `num_predict` limits answers to about 300 tokens. Raise it in `LocalAIServ.py` and rebuild ([changing the settings](4-Choose-Local-or-Colab.md#changing-the-answer-servers-settings)). |
