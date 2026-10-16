# 3. Embed your SOPs

Embedding turns each passage into a list of 384 numbers (a *vector*) that captures what it means. Passages about similar things get similar vectors. When someone asks a question, the question is turned into a vector the same way, and the passages with the closest vectors are the ones handed to the AI model. This lets "how do I report a deviation?" find a passage that says "non-conformances must be logged within 24 hours", even though they share no words.

The embedding model is **BGE** (`BAAI/bge-small-en-v1.5`). It runs on your own PC, so your SOP text isn't sent to an online service.

## 3.1 Run `EmbedScript.py`

Run it from the main `LLM` folder:

```powershell
cd "C:\path\to\LLM"
py EmbedScript.py
```

- **The first run downloads BGE** from Hugging Face. This is about 130 MB and needs an internet connection. It's saved in a cache folder on your PC, and every later run reuses it.
- It then shows a progress bar while it embeds every passage. Expect several minutes for a couple of thousand passages on a laptop CPU.
- It finishes with `Saved vectors: (1876, 384)`: one row per passage, 384 numbers each.

It reads `PreProcessesV1/passages.jsonl` from step 2 and writes two files next to it:

| File | Contents |
|---|---|
| `passage_vectors.npy` | The vectors, one row per passage |
| `passage_ids.json` | Which passage each row belongs to, in the same order |

**Check:** both files in `PreProcessingSAHPRAGuidelines/PreProcessesV1/` have just been updated, and the first number in `Saved vectors: (…, 384)` matches the passage count from `TextCleanUp.py`.

## 3.2 Run it on the PC that will answer questions

`LocalAITest.py`, which answers questions on your PC in step 4, deliberately runs offline. It uses the copy of BGE this step downloaded and doesn't go online to check for updates. So run `EmbedScript.py` at least once on the PC that will answer questions, logged in as the same Windows user. Otherwise `LocalAITest.py` stops with an error that it can't find the model.

## 3.3 When to run it again

Run `EmbedScript.py` again **every time you run `TextCleanUp.py`**, which means every time you add, remove or update an SOP. The full sequence is always:

```
PyPDF.py  →  TextCleanUp.py  →  EmbedScript.py  →  (Colab only) upload the 3 files again
```

If you forget, the answering scripts notice that the number of passages and vectors don't match. They stop with: *"The passages have changed since they were embedded. Run EmbedScript.py again."*

## 3.4 Changing the embedding model

Questions and passages must be embedded by the **same** model, or the search returns nonsense. The model name appears in three places:

- `EmbedScript.py`: `MODEL_NAME`
- `LocalAITest.py`: `EMBED_MODEL`
- `ColabTest.ipynb`: `EMBED_MODEL`

If you switch to another model, for example a multilingual one for non-English SOPs, change all three and run `EmbedScript.py` again.

---

Next: [4. Choose local or Google Colab](4-Choose-Local-or-Colab.md)
