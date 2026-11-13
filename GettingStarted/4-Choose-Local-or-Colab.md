# 4. Answer questions: on your PC or Google Colab

The answers are written by Qwen (`qwen3.5:0.8b`), a small AI model run by Ollama. It can run in two places:

|  | **On your PC** (`LocalAIServ.py`) | **Google Colab** (`ColabTest.ipynb`) |
|---|---|---|
| Used by the website | **Yes.** Every question on the website is answered this way, and so are the reports, backups and settings. | No. Use Colab for testing in the notebook. |
| Speed | Slow on a CPU, roughly **15–30 seconds** per answer on a laptop. | Much faster, on an NVIDIA T4 GPU. |
| Running it | `LocalAIServ.py` must be running. It starts Ollama itself. | Open the notebook and run its blocks at the start of every session. |
| Privacy | Your SOPs and questions stay on your PC. | Your passages and questions are processed on Google's servers. |
| Cost | Free. | Free tier, with usage limits. |

Set up "on your PC" first; the website needs it.

## 4.1 On your PC

`LocalAIServ.py` is the **answer server**. It loads your passages, vectors and the BGE model once, then waits for the website to send it questions at `http://127.0.0.1:5000/ask`. It only accepts questions from the same PC, so other computers can't use it directly. Besides answering questions it also serves the website's report, backup and settings requests.

1. Make sure the model is downloaded: `ollama list` shows `qwen3.5:0.8b`. See [step 1](1-Install-Software.md#ollama-and-the-qwen-model). You don't need to start Ollama yourself: if it isn't running, the answer server starts it (using `OLLAMA_PATH` from `config.json`) and shows `Ollama is running`.
2. Start the answer server. Either:
   - **from a terminal**, in the main `LLM` folder:

     ```powershell
     cd "C:\path\to\LLM"
     .venv\Scripts\python.exe LocalAIServ.py
     ```

   - **or from the website**: log in as an admin and press **Start server** in the *RAG Pipeline Server* card ([step 5.4](5-Set-Up-the-Website.md#54-first-login)). It needs the website set up first.

   After a few seconds its window shows:

   ```
   Answer server ready on http://127.0.0.1:5000/ask - leave this window open while the website is in use
   ```

   **Leave this window open.** Closing it stops the website from answering. Start it again whenever you restart the PC. To stop it cleanly (and stop Ollama with it), use the admin page's **Stop server** button.
3. Ask a test question. In a new PowerShell window:

   ```powershell
   (Invoke-WebRequest -Method Post -Uri http://127.0.0.1:5000/ask -Body @{question = "What must be recorded when a deviation occurs?"}).Content
   ```

4. You should get an answer with citations like `[1]`, followed by a **Sources:** list naming your SOPs. The first question after Ollama starts is slowest, because the model has to load.

If the sources aren't your SOPs, `Raw_Text` still held old text files when you ran `TextCleanUp.py`. Clear it and run steps 2.4 to 3.1 again ([step 2.3](2-Prepare-Your-SOPs.md#23-clear-out-old-text-files)), then restart `LocalAIServ.py`.

Run only one copy of `LocalAIServ.py` at a time. Windows lets two copies share port 5000, and an old copy can keep answering with old settings or old SOPs.

**What the AI is told.** `LocalAIServ.py` tells the model it answers questions about *ORG_NAME*'s Standard Operating Procedure documents, using `ORG_NAME` from `config.json` ([step 2.1](2-Prepare-Your-SOPs.md#21-set-your-organisations-name)). It's also told to use only the sources it's given, cite them, and say so when the answer isn't there. That last part is what stops it making answers up.

**Who goes first.** Questions wait in a queue. Admins' questions jump ahead of users' questions that are still waiting; otherwise it's first come, first served. `RUNNING_THREADS` (below) sets how many questions are answered at the same time.

### Changing the answer server's settings

The settings live in `config.json`, in the main `LLM` folder, and are read **once, when the server starts**. Edit the file, or let an admin change them on the website's Settings page ([step 5.6](5-Set-Up-the-Website.md#56-settings-and-reports)). Then stop and start the server for the change to apply. `config.json` stores every value as text in quotes.

| Setting | What it does |
|---|---|
| `ORG_NAME` | The organisation named in the AI's instructions |
| `MODEL_NAME` | The Ollama model that writes the answers. Download it first with `ollama pull`. |
| `EMBED_MODEL` | The embedding model. It must match the one `EmbedScript.py` used ([step 3.4](3-Embed-Your-SOPs.md#34-changing-the-embedding-model)). |
| `SOURCES` | How many passages Qwen reads per question. Fewer is faster, with less context. If you raise it, raise `CONTEXT_WINDOW` too. |
| `CONTEXT_WINDOW` | How much text Qwen can read, in tokens. Smaller uses less memory. |
| `LENGTH` | The longest answer Qwen may write, in tokens. Raise it if answers stop mid-sentence. |
| `TEMPERATURE` | Low (like `0.1`) makes the answer stick to the sources; higher makes it more varied. |
| `RUNNING_THREADS` | How many questions are answered at the same time. More needs more memory. |
| `OLLAMA_PATH` | Where `ollama.exe` is, so the server can start it. The default is Ollama's normal install location. |
| `PORT` | Leave at `5000`. The website's pages are written to talk to port 5000. |

The rest of `config.json` (`BETWEEN_DAYS`, `SPECIFIC_WEEK`, `MONTH`, `MOST_SOURCED`, `BUSIEST_DAYS`, `TOTAL_RESPONSES`, `LONGEST_SESSIONS`, `REPORT_NAME`) is written by the Reports page each time a report is made. Don't edit those by hand.

## 4.2 Google Colab (testing on a free GPU)

You need a Google account.

**Make the project visible to Colab (once).** A notebook running on Colab's servers can't see folders on your PC. Install [Google Drive for desktop](https://www.google.com/drive/download/) and sync your `LLM` project folder to your Google Drive. The notebook then finds the project's `PreProcessing` folders and `config.json` anywhere in your Drive.

**Each session:**

1. **Open the notebook.** Go to [colab.research.google.com](https://colab.research.google.com), choose **File → Open notebook**, then the **Upload** tab, and pick `ColabTest.ipynb`.
2. **Choose the GPU:** **Runtime → Change runtime type → T4 GPU → Save**.
3. **Run block 1.2** ("for your own documents"). Allow access to your Drive when asked. It copies `passages.jsonl` from `PreProcessing/Raw_Text`, `passage_vectors.npy` and `passage_ids.json` from `PreProcessing/Embeds`, and `config.json` from the main project folder into `My Drive/RAGFiles`. Each copied file is listed.

   Block 1.1 does the same with the SAHPRA demo set in `PreProcessesV1`. Run 1.1 **or** 1.2, not both: whichever runs last fills `RAGFiles`.
4. **Run block 2.** It installs Ollama and downloads Qwen. This takes a few minutes, every session, because Colab starts from a blank machine.
5. **Run block 3.** Type a test question. You should get an answer with sources. The model is told it answers questions about *ORG_NAME*'s guidelines, using the `config.json` copied in block 1.2.

After changing your SOPs or `ORG_NAME`, let Drive finish syncing, then run block 1.2 again so `RAGFiles` gets the new files.

The notebook's last cell starts a public web address. The website doesn't use it (it always asks `LocalAIServ.py`), so you can skip it.

---

Next: [5. Set up the website](5-Set-Up-the-Website.md)
