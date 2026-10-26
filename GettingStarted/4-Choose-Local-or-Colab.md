# 4. Answer questions: on your PC or Google Colab

The answers are written by Qwen (`qwen3.5:0.8b`), a small AI model run by Ollama. It can run in two places:

|  | **On your PC** (`LocalAITest.py`) | **Google Colab** (`ColabTest.ipynb`) |
|---|---|---|
| Used by the website | **Yes.** Every question on the website is answered this way. | Not yet. The notebook can start a web address for the website, but the website doesn't use it yet. Use Colab for testing in the notebook. |
| Speed | Slow on a CPU. A test on an 8 GB laptop took about **90 seconds** per answer. | Much faster, on an NVIDIA T4 GPU. |
| Running it | Ollama must be running. Nothing else to do. | Open the notebook and run its blocks at the start of every session. |
| Privacy | Your SOPs and questions stay on your PC. | Your passages and questions are processed on Google's servers. |
| Cost | Free. | Free tier, with usage limits. |

Set up "on your PC" first; the website needs it.

## 4.1 On your PC

1. Make sure Ollama is running (llama icon in the system tray) and the model is downloaded: `ollama list` shows `qwen3.5:0.8b`. See [step 1](1-Install-Software.md#ollama-and-the-qwen-model).
2. Ask a test question from the main `LLM` folder:

   ```powershell
   cd "C:\path\to\LLM"
   py LocalAITest.py "What must be recorded when a deviation occurs?"
   ```

3. You should get an answer with citations like `[1]`, followed by a **Sources:** list naming your SOPs. The first question after starting Ollama is slowest, because the model has to load.

If the sources aren't your SOPs, `Raw_Text` still held old text files when you ran `TextCleanUp.py`. Clear it and run steps 2.4 to 3.1 again ([step 2.3](2-Prepare-Your-SOPs.md#23-clear-out-old-text-files)).

**What the AI is told.** `LocalAITest.py` tells the model it answers questions about *ORG_NAME*'s Standard Operating Procedure documents, using `ORG_NAME` from `config.json` ([step 2.1](2-Prepare-Your-SOPs.md#21-set-your-organisations-name)). It's also told to use only the sources it's given, cite them, and say so when the answer isn't there. That last part is what stops it making answers up.

**Speed tip:** `TOP_K = 5` near the top of `LocalAITest.py` is how many passages Qwen reads per question. Lowering it to 3 makes answers noticeably faster, at the cost of less context.

## 4.2 Google Colab (testing on a free GPU)

You need a Google account.

**Make the project visible to Colab (once).** A notebook running on Colab's servers can't see folders on your PC. Install [Google Drive for desktop](https://www.google.com/drive/download/) and sync your `LLM` project folder to your Google Drive. The notebook then finds the project's `PreProcessing` folders and `config.json` anywhere in your Drive.

**Each session:**

1. **Open the notebook.** Go to [colab.research.google.com](https://colab.research.google.com), choose **File → Upload notebook**, and pick `ColabTest.ipynb`.
2. **Choose the GPU:** **Runtime → Change runtime type → T4 GPU → Save**.
3. **Run block 1.2** ("for your own documents"). Allow access to your Drive when asked. It copies `passages.jsonl` from `PreProcessing/Raw_Text`, `passage_vectors.npy` and `passage_ids.json` from `PreProcessing/Embeds`, and `config.json` from the main project folder into `My Drive/RAGFiles`. Each copied file is listed.

   Block 1.1 does the same with the SAHPRA demo set in `PreProcessesV1`. Run 1.1 **or** 1.2, not both: whichever runs last fills `RAGFiles`.
4. **Run block 2.** It installs Ollama and downloads Qwen. This takes a few minutes, every session, because Colab starts from a blank machine.
5. **Run block 3.** Type a test question. You should get an answer with sources. The model is told it answers questions about *ORG_NAME*'s guidelines, using the `config.json` copied in block 1.2.

After changing your SOPs or `ORG_NAME`, let Drive finish syncing, then run block 1.2 again so `RAGFiles` gets the new files.

The last cell starts a public web address for the notebook. The website doesn't use that address yet, so you can skip it.

---

Next: [5. Set up the website](5-Set-Up-the-Website.md)
