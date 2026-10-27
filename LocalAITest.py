import os, sys, json, warnings
from pathlib import Path

# keep Hugging Face warnings and progress bars out of the terminal
os.environ["HF_HUB_OFFLINE"] = "1"                     # the model is already downloaded, so don't check online
os.environ["HF_HUB_DISABLE_PROGRESS_BARS"] = "1"
os.environ["HF_HUB_DISABLE_SYMLINKS_WARNING"] = "1"
os.environ["TRANSFORMERS_VERBOSITY"] = "error"
warnings.filterwarnings("ignore")

import numpy as np
import requests
from flask import Flask, request
from sentence_transformers import SentenceTransformer

MODEL = "qwen3.5:0.8b"
EMBED_MODEL = "BAAI/bge-small-en-v1.5"                 # must be the same model EmbedScript.py used
QUERY_PREFIX = "Represent this sentence for searching relevant passages: "   # BGE wants this on questions only
PASSAGES_FOLDER = Path(__file__).parent / "PreProcessing" / "Raw_Text"   # passages.jsonl from TextCleanUp.py
VECTORS_FOLDER = Path(__file__).parent / "PreProcessing" / "Embeds"     # passage_vectors.npy from EmbedScript.py
TOP_K = 3                                              # passages given to Qwen; raise num_ctx if you raise this
PORT = 5000                                            # Agent.php and adminAgent.php send questions to this port

with open(Path(__file__).parent / "config.json", encoding="utf-8") as f:
    ORG_NAME = json.load(f)["ORG_NAME"]                 # the organisation named in the prompt below

# Load the passages, the vectors EmbedScript.py saved for them and the embedder once, when the server starts
with open(PASSAGES_FOLDER / "passages.jsonl", encoding="utf-8") as f:
    passages = [json.loads(line) for line in f]
vectors = np.load(VECTORS_FOLDER / "passage_vectors.npy")
if len(vectors) != len(passages):
    sys.exit("The passages have changed since they were embedded. Run EmbedScript.py again.")

embedder = SentenceTransformer(EMBED_MODEL, device="cpu")

SYSTEM_PROMPT = (
    f"You answer questions about {ORG_NAME} Standard Operating Procedure documents. "
    "Use ONLY the numbered sources provided. Cite sources in square brackets, e.g. [1] or [2][3]. "
    "If the sources do not contain the answer, say you could not find it in the guidelines."
)


def source_label(p):
    pages = f"p. {p['page_start']}" if p["page_start"] == p["page_end"] else f"pp. {p['page_start']}-{p['page_end']}"
    return f"{p['document_number'] or p['source_file']}: {p['title']}, {pages}"


def answer(question):
    # Embed the question and find the passages most similar to it
    q = embedder.encode([QUERY_PREFIX + question], normalize_embeddings=True)[0]
    best = np.argsort(-(vectors @ q))[:TOP_K]
    sources = "\n\n".join(f"[{i}] {source_label(passages[j])}\n{passages[j]['text']}" for i, j in enumerate(best, start=1))

    try:
        reply = requests.post("http://localhost:11434/api/chat", json={
            "model": MODEL,
            "messages": [
                {"role": "system", "content": SYSTEM_PROMPT},
                {"role": "user", "content": f"Sources:\n{sources}\n\nQuestion: {question}"},
            ],
            "stream": False,
            "think": False,
            "keep_alive": "1h",
            "options": {"num_ctx": 2000, "temperature": 0.2, "num_predict" : 300},   # small context window = less memory; low temperature = sticks to the sources
        }, timeout=300)
    except requests.ConnectionError:
        return "Ollama isn't running. Start the Ollama app and try again."

    data = reply.json()

    if "error" in data:                        # Ollama sends {"error": "..."} instead of a message when it fails
        if "out-of-memory" in data["error"] or "unable to allocate" in data["error"]:
            return "Not enough free memory to load the model. Close some programs and try again."
        return "Ollama error: " + data["error"]

    labels = "\n".join(f"[{i}] {source_label(passages[j])}" for i, j in enumerate(best, start=1))
    return f"{data['message']['content']}\n\nSources:\n{labels}"


app = Flask(__name__)


@app.post("/ask")
def ask():
    # Agent.php and adminAgent.php post the question as the form field "question" and show the text that comes back
    question = request.form.get("question", "").strip()
    if not question:
        return "No question was sent.", 400
    return answer(question), 200, {"Content-Type": "text/plain; charset=utf-8"}


if __name__ == "__main__":
    print(f"Answer server ready on http://127.0.0.1:{PORT}/ask - leave this window open while the website is in use")
    app.run(host="127.0.0.1", port=PORT, threaded=True)
