# 6. Troubleshooting

Find the message or symptom you're seeing. Messages in quotes are exactly what the scripts and pages print.

## Running Python

| Message or symptom | Cause | Fix |
|---|---|---|
| "Python was not found; run without arguments to install from the Microsoft Store" | Windows' Store shortcut is being found before the real Python. | Switch off the **python.exe** and **python3.exe** aliases ([step 1](1-Install-Software.md#python)), then close and reopen VS Code or the terminal. Or use `py` instead of `python`. |
| `'py' is not recognized…` | Python was installed without the launcher or without PATH. | Run the Python installer again, choose **Modify**, and tick **py launcher** and **Add Python to environment variables**. |
| `ModuleNotFoundError: No module named '…'` | The packages aren't installed, or were installed for a different Python. | Run the `py -m pip install …` command from [step 1](1-Install-Software.md#install-the-python-packages) again. |

## Preparing and embedding SOPs

| Message or symptom | Cause | Fix |
|---|---|---|
| `PyPDF.py` stops with a "no such file" error | The `range(…)` includes a number with no matching `docN.pdf`, or you're in the wrong folder. | Check the range ([step 2.4](2-Prepare-Your-SOPs.md#24-convert-the-pdfs-to-text-with-pypdfpy)) and run it from `PreProcessingSAHPRAGuidelines`. |
| "Done: 0 passages from 0 files" | `TextCleanUp.py` was run from the wrong folder, so it can't see `Raw_Text`. | `cd` into `PreProcessingSAHPRAGuidelines` first. |
| "Skipping pdfN.txt: almost no text (probably a scanned PDF)" | The PDF is a scan with no real text. | OCR it, then convert it again ([step 2.1](2-Prepare-Your-SOPs.md#21-get-your-sops-into-the-right-format)). |
| Citations show `pdf12.txt` or the wrong title | `TextCleanUp.py` didn't recognise your SOP numbers or titles. | Adjust `DOC_NUMBER` and the letterhead filter ([step 2.5](2-Prepare-Your-SOPs.md#25-optional-but-recommended-teach-the-clean-up-script-your-sop-numbers)), then run `TextCleanUp.py` and `EmbedScript.py` again. |
| "The passages have changed since they were embedded. Run EmbedScript.py again." | `TextCleanUp.py` was re-run without re-embedding, or (Colab) the three files in Drive come from different runs. | Run `EmbedScript.py`. For Colab, upload all three files again. |

## Local answers (`LocalAITest.py` and Ollama)

| Message or symptom | Cause | Fix |
|---|---|---|
| "Ollama isn't running. Start the Ollama app and try again." | Ollama is closed. | Start **Ollama** from the Start menu; check for the icon in the system tray. |
| "Ollama error: model … not found" | The Qwen model was never downloaded. | `ollama pull qwen3.5:0.8b` |
| "Not enough free memory to load the model. Close some programs and try again." | The PC is short on RAM. | Close programs. Lowering `TOP_K` in `LocalAITest.py` also reduces memory use. |
| An error about not finding `bge-small-en-v1.5`, Hugging Face, or the local cache | `LocalAITest.py` runs offline and BGE isn't in the cache for this Windows user. | Run `EmbedScript.py` once while online, as the same Windows user ([step 3.2](3-Embed-Your-SOPs.md#32-run-it-on-the-pc-that-will-answer-questions)). From the website, also make sure Apache was started from the XAMPP Control Panel and not as a service. |
| Answers take a very long time | Qwen is running on the CPU. | Expected locally (about 90 seconds on a laptop). Lower `TOP_K` to 3, or use Colab. |

**Tip:** the website shows whatever `LocalAITest.py` prints, including errors. If the website shows something odd, run `py LocalAITest.py "your question"` in a terminal to see the same thing with more detail.

## Google Colab

| Message or symptom | Cause | Fix |
|---|---|---|
| Answers are about SAHPRA, not your SOPs | You typed **y** in block 1, which downloaded the demo files over yours. | Upload your three files to `My Drive/RAGFiles` again, and answer **n** next time. |
| "Ollama didn't start. Check the install step." | The install in block 2 failed or timed out. | Run block 2 again. If it keeps failing, use **Runtime → Disconnect and delete runtime**, then start again from block 1. |
| "Ollama isn't running. Run code block 2 first." | Block 2 wasn't run in this session. | Run block 2, then try again. |
| "Tunnel didn't start, check /content/cloudflared.log" | The Cloudflare tunnel took too long to start. | Run the last cell again. |
| `NameError` in the last cell | Block 3 wasn't run first. The last cell reuses its settings. | Run block 3, then the last cell. |
| Very slow even on Colab | The notebook isn't using the GPU. | **Runtime → Change runtime type → T4 GPU**, then run all the blocks again. |

## The website

| Message or symptom | Cause | Fix |
|---|---|---|
| "Database connection error, contact your system administrator for assistance" | MySQL isn't running, `passwords.txt` is missing from the `htdocs` folder, or `db_pass` is wrong. | Start MySQL in XAMPP; check `passwords.txt` is next to `dataconn.php`; check the password. XAMPP's default is empty: `db_pass = ""`. |
| "Incorrect Login details" with the right password | The admin username doesn't start with `admin`, or `passwords.txt` was changed after the accounts were created. | See [step 5.6](5-Set-Up-the-Website.md#56-managing-accounts). Change existing passwords in phpMyAdmin. |
| "The Colab notebook couldn't be reached, so the local model answered instead" | The Colab session ended, or `colab_url` holds an old address. | Restart the Colab blocks and paste the new address ([step 4.3](4-Choose-Local-or-Colab.md#43-google-colab-answering-on-a-free-gpu)). To stop the message, set `colab_url = ""`. |
| The admin page shows an error about `vendor/autoload.php` | The chart library wasn't downloaded. | Run `composer install` in the `htdocs` folder ([step 5.1](5-Set-Up-the-Website.md#51-copy-the-website-into-xampp)). |
| After about 5 minutes the page shows a "Maximum execution time" error or no answer | Local answering went over the 300-second limit. | Lower `TOP_K`, use Colab, or check `LocalAITest.py` in a terminal. |
| http://localhost/SOPAgent/passwords.txt shows your passwords | `.htaccess` is missing, or Apache is ignoring it. | Copy `.htaccess` into the folder. In `C:\xampp\apache\conf\httpd.conf`, the `<Directory "C:/xampp/htdocs">` section must say `AllowOverride All`. Restart Apache. |
| Changes you made don't show up | You edited the project's `FrontEnd` folder, but Apache runs the copy in `htdocs`. | Copy the changed files into `C:\xampp\htdocs\SOPAgent`. |

## Poor answers

| Symptom | What to check |
|---|---|
| "I could not find it in the guidelines" for something that is in your SOPs | Search `PreProcessesV1/passages.jsonl` (open it in VS Code) for a phrase from that section. If it isn't there, the clean-up removed it; see [what the clean-up can't handle](2-Prepare-Your-SOPs.md#what-the-clean-up-script-cant-handle). If it is there, try wording the question closer to the SOP's own terms, or raise `TOP_K`. |
| The answer cites the right SOP but says something wrong | Small models make mistakes. Always check the cited passage. A larger Ollama model (change `MODEL` in `LocalAITest.py` and the Colab notebook) gives better answers but is slower. |
