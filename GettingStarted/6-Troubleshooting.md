# 6. Troubleshooting

Find the message or symptom you're seeing. Messages in quotes are exactly what the scripts and pages print.

## Running Python

| Message or symptom | Cause | Fix |
|---|---|---|
| "Python was not found; run without arguments to install from the Microsoft Store" | Windows' Store shortcut is being found before the real Python. | Switch off the **python.exe** and **python3.exe** aliases ([step 1](1-Install-Software.md#python)), then close and reopen VS Code or the terminal. Or use `py` instead of `python`. |
| `'py' is not recognized…` | Python was installed without the launcher or without PATH. | Run the Python installer again, choose **Modify**, and tick **py launcher** and **Add Python to environment variables**. |
| `ModuleNotFoundError: No module named '…'` | A package isn't installed, or was installed for a different Python. `crossfiledialog` is the one most often missed. | Run the `py -m pip install …` command from [step 1](1-Install-Software.md#install-the-python-packages) again. |

## Preparing and embedding SOPs

| Message or symptom | Cause | Fix |
|---|---|---|
| "No folder chosen." | The folder window in `PyPDF.py` was closed or cancelled. | Run it again and choose your SOP folder. |
| `FileNotFoundError` or `JSONDecodeError` mentioning `config.json` | `config.json` is missing from the main `LLM` folder, or isn't valid JSON (for example, a missing quote or comma). | Recreate it as shown in [step 2.1](2-Prepare-Your-SOPs.md#21-set-your-organisations-name). |
| `PyPDF.py` finishes without any `Processing …` lines | The chosen folder has no PDFs directly in it. Subfolders and other file types are skipped. | Choose the folder that contains the PDFs themselves, or move them out of subfolders. |
| "Done: 0 passages from 0 files" | `TextCleanUp.py` was run from the wrong folder, so it can't see `Raw_Text`. | `cd` into `PreProcessing` first ([step 2.6](2-Prepare-Your-SOPs.md#26-clean-and-split-the-text-with-textcleanuppy)). |
| "Skipping …: almost no text (probably a scanned PDF)" | The PDF is a scan with no real text. | OCR it, then run `PyPDF.py` again ([step 2.2](2-Prepare-Your-SOPs.md#22-get-your-sops-into-the-right-format)). |
| Old or SAHPRA documents appear among your SOPs | `Raw_Text` still held text files from earlier. | Clear `Raw_Text` and run steps 2.4 onwards again ([step 2.3](2-Prepare-Your-SOPs.md#23-clear-out-old-text-files)). |
| `EmbedScript.py` stops with `FileNotFoundError` for `passages.jsonl` | `TextCleanUp.py` hasn't been run, or was run from the wrong folder. | Run it from inside `PreProcessing`. |
| Citations show the file name, a blank title, or the wrong SOP number | `TextCleanUp.py` didn't recognise your SOP numbers or titles. | Adjust `DOC_NUMBER` and the letterhead filter ([step 2.5](2-Prepare-Your-SOPs.md#25-optional-but-recommended-teach-the-clean-up-script-your-sop-numbers)), then run `TextCleanUp.py` and the rest of the sequence again. |
| "The passages have changed since they were embedded. Run EmbedScript.py again." | `passages.jsonl` in `Raw_Text` was remade by `TextCleanUp.py`, but the vectors in `Embeds` weren't. | Run `EmbedScript.py` again ([step 3.3](3-Embed-Your-SOPs.md#33-when-to-run-it-again)). For Colab, run block 1.2 again too. |

## Answering on your PC (`LocalAITest.py` and Ollama)

| Message or symptom | Cause | Fix |
|---|---|---|
| Answers cite old documents instead of your SOPs | `Raw_Text` still held old text files when you ran `TextCleanUp.py`. | Clear it and run steps 2.4 to 3.1 again ([step 2.3](2-Prepare-Your-SOPs.md#23-clear-out-old-text-files)). |
| "Ollama isn't running. Start the Ollama app and try again." | Ollama is closed. | Start **Ollama** from the Start menu; check for the icon in the system tray. |
| "Ollama error: model … not found" | The Qwen model was never downloaded. | `ollama pull qwen3.5:0.8b` |
| "Not enough free memory to load the model. Close some programs and try again." | The PC is short on RAM. | Close programs. Lowering `TOP_K` in `LocalAITest.py` also reduces memory use. |
| An error about not finding `bge-small-en-v1.5`, Hugging Face, or the local cache | `LocalAITest.py` runs offline and BGE isn't in the cache for this Windows user. | Run `EmbedScript.py` once while online, as the same Windows user ([step 3.2](3-Embed-Your-SOPs.md#32-run-it-on-the-pc-that-will-answer-questions)). From the website, also make sure Apache was started from the XAMPP Control Panel and not as a service. |
| `KeyError: 'ORG_NAME'` | `config.json` has no `ORG_NAME` field. | Add it ([step 2.1](2-Prepare-Your-SOPs.md#21-set-your-organisations-name)). |
| Answers take a very long time | Qwen is running on the CPU. | Expected (about 90 seconds on a laptop). Lower `TOP_K` to 3. |

**Tip:** the website shows whatever `LocalAITest.py` prints, including errors. If the website shows something odd, run `py LocalAITest.py "your question"` in a terminal to see the same thing with more detail.

## Google Colab

| Message or symptom | Cause | Fix |
|---|---|---|
| "Couldn't find the project's PreProcessing/Raw_Text/passages.jsonl and PreProcessing/Embeds files" (block 1.2) | Colab can't see the project: it isn't synced to your Google Drive, the sync hasn't finished, or `EmbedScript.py` hasn't been run. | Sync the `LLM` folder with Google Drive for desktop, wait for it to finish, then run block 1.2 again ([step 4.2](4-Choose-Local-or-Colab.md#42-google-colab-testing-on-a-free-gpu)). |
| "Couldn't find config.json in the main project folder" | `config.json` isn't next to the `PreProcessing` folder in the synced project. | Check it's in the main `LLM` folder and synced. |
| Block 3 stops with `FileNotFoundError` for `config.json` | Block 1.2 didn't copy it (see above). | Fix that, then run block 1.2 and block 3 again. |
| "Ollama didn't start. Check the install step." | The install in block 2 failed or timed out. | Run block 2 again. If it keeps failing, use **Runtime → Disconnect and delete runtime**, then start again from block 1.2. |
| "Ollama isn't running. Run code block 2 first." | Block 2 wasn't run in this session. | Run block 2, then try again. |
| Very slow even on Colab | The notebook isn't using the GPU. | **Runtime → Change runtime type → T4 GPU**, then run the blocks again. |

## The website

| Message or symptom | Cause | Fix |
|---|---|---|
| "Database connection error, contact your system administrator for assistance" | MySQL isn't running, `passwords.txt` is missing from the `htdocs` folder, or `db_pass` is wrong. | Start MySQL in XAMPP. Check `passwords.txt` is next to `dataconn.php` and `db_pass` is right ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-passwordstxt)). XAMPP's default is empty: `""`. |
| "Incorrect Login details" with the right password | The admin username doesn't start with `admin`, or the password was changed in phpMyAdmin. | See [step 5.5](5-Set-Up-the-Website.md#55-managing-accounts). |
| The admin page shows an error about `vendor/autoload.php` | The chart library wasn't downloaded. | Run `composer install` in the `htdocs` folder ([step 5.1](5-Set-Up-the-Website.md#51-copy-the-website-into-xampp)). |
| The answer area shows a Python error, or "The system cannot find the path specified" | `python` or `script` in `passwords.txt` is wrong. | Fix the paths ([step 5.2](5-Set-Up-the-Website.md#52-fill-in-passwordstxt)). |
| After about 5 minutes the page shows a "Maximum execution time" error or no answer | Answering took longer than the pages' 300-second limit. | Lower `TOP_K` in `LocalAITest.py`, or check `LocalAITest.py` in a terminal. |
| Changes you made don't show up | You edited the project's `FrontEnd` folder, but Apache runs the copy in `htdocs`. | Copy the changed files into `C:\xampp\htdocs\SOPAgent`. |

## Poor answers

| Symptom | What to check |
|---|---|
| "I could not find it in the guidelines" for something that is in your SOPs | Search `PreProcessing/Raw_Text/passages.jsonl` (open it in VS Code) for a phrase from that section. If it isn't there, the clean-up removed it; see [what the clean-up can't handle](2-Prepare-Your-SOPs.md#what-the-clean-up-script-cant-handle). If it is there, try wording the question closer to the SOP's own terms, or raise `TOP_K`. |
| The answer cites the right SOP but says something wrong | Small models make mistakes. Always check the cited passage. A larger Ollama model (change `MODEL` in `LocalAITest.py` and the Colab notebook) gives better answers but is slower. |
