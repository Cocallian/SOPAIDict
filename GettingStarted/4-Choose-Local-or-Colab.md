# 4. Choose local or Google Colab

The answers are written by Qwen (`qwen3.5:0.8b`), a small AI model run by Ollama. It can run in either of two places, and the website can use both: it tries Colab first when you've given it a Colab address, and falls back to your PC if Colab can't be reached.

|  | **Local** (your PC) | **Google Colab** (free Google GPU) |
|---|---|---|
| Speed | Slow on a CPU. A test on an 8 GB laptop took about **90 seconds** per answer. | Much faster, on an NVIDIA T4 GPU. |
| Running it | Ollama must be running. Nothing else to do. | Open the notebook and run its cells at the start of every session, then paste the new address into `passwords.txt`. |
| Availability | Whenever your PC is on. | Until the session ends (after a period of inactivity, or Colab's maximum runtime). The address changes every time. |
| Privacy | Your SOPs and questions stay on your PC. | Your passages and every question are processed on Google's servers and pass through a Cloudflare tunnel. |
| Cost and rules | Free. | Free tier, with usage limits. Colab's free tier is meant for interactive use, and its FAQ lists running web services among restricted activities. Check it before serving real users. |

**Recommendation:** set up local first. It's simpler, private, and is the fallback anyway. Add Colab when you need faster answers and your organisation allows it.

## 4.1 Either way: change the assistant's instructions

The assistant's instructions still say it answers questions about SAHPRA guidelines. If you're using your own SOPs, change the `SYSTEM_PROMPT` text in:

- `LocalAITest.py` (around line 44)
- `ColabTest.ipynb`, in block 3 (the last cell reuses it)

For example:

```python
SYSTEM_PROMPT = (
    "You answer questions about Acme Pharmaceuticals' standard operating procedures (SOPs). "
    "Use ONLY the numbered sources provided. Cite sources in square brackets, e.g. [1] or [2][3]. "
    "If the sources do not contain the answer, say you could not find it in the SOPs."
)
```

Keep the instructions to use only the sources and to cite them. They're what stop the model from making answers up.

## 4.2 Local: answering on your PC

1. Make sure Ollama is running (llama icon in the system tray) and the model is downloaded (`ollama list` shows `qwen3.5:0.8b`). See [step 1](1-Install-Software.md#ollama-and-the-qwen-model).
2. Ask a test question from the main `LLM` folder:

   ```powershell
   cd "C:\path\to\LLM"
   py LocalAITest.py "What must be recorded when a deviation occurs?"
   ```

3. You should get an answer with citations like `[1]`, followed by a **Sources:** list. The first question after starting Ollama is slowest, because the model has to load.
4. In `passwords.txt` (step 5), leave `colab_url = ""`.

The website runs this same script for every question, using the Python and script paths you set in [step 5](5-Set-Up-the-Website.md#53-point-the-website-at-python).

**Speed tip:** `TOP_K = 5` near the top of `LocalAITest.py` is how many passages Qwen reads per question. Lowering it to 3 makes answers noticeably faster, at the cost of less context.

## 4.3 Google Colab: answering on a free GPU

You need a Google account. Do 1–3 once; repeat 4–8 at the start of every session.

1. **Open the notebook in Colab.** Go to [colab.research.google.com](https://colab.research.google.com), choose **File → Upload notebook**, and pick `ColabTest.ipynb`. Upload your own copy (with your `SYSTEM_PROMPT` changes) rather than opening the one on GitHub.
2. **Choose the GPU.** Use **Runtime → Change runtime type → T4 GPU → Save**.
3. **Upload your SOP files to Google Drive.** In [drive.google.com](https://drive.google.com), create a folder named exactly **`RAGFiles`** directly inside **My Drive**. Upload these three files from `PreProcessingSAHPRAGuidelines/PreProcessesV1/`:
   - `passages.jsonl`
   - `passage_vectors.npy`
   - `passage_ids.json`

   Upload them again whenever you re-run step 3.
4. **Run block 1.** Allow access to your Drive when asked. When it asks *"Create a 'RAGFiles' folder in your Google Drive and download the RAG files into it? (y/n)"*, type **`n`**. Typing `y` downloads the project's SAHPRA demo files from GitHub **over the top of yours**. (Block 1.1 lists what's in `RAGFiles`, if you want to check.)
5. **Run block 2.** It installs Ollama and downloads Qwen. This takes a few minutes, and happens again in each new session, because Colab starts from a blank machine.
6. **Run block 3.** Type a test question. You should get an answer with sources, which proves your files and the model work.
7. **Run the last cell.** It starts the connection for the website and prints:

   ```
   API is live. Paste this into FrontEnd/passwords.txt as colab_url: https://something-random.trycloudflare.com/ask
   ```

8. **Paste that address** into `colab_url` in the `passwords.txt` the web server uses, which is the copy in `C:\xampp\htdocs\…` ([step 5](5-Set-Up-the-Website.md#54-fill-in-passwordstxt)). Keep the Colab tab open while people are using the website.

When the session ends, the address stops working and the website quietly falls back to your PC, with a pop-up saying so. To switch back to Colab, repeat steps 4–8.

---

Next: [5. Set up the website](5-Set-Up-the-Website.md)
