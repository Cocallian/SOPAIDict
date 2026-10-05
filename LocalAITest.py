import os, sys, json, warnings
from pathlib import Path

# keep Hugging Face warnings and progress bars out of the output, since PHP shows everything this script prints
os.environ["HF_HUB_OFFLINE"] = "1"                     # the model is already downloaded, so don't check online
os.environ["HF_HUB_DISABLE_PROGRESS_BARS"] = "1"
os.environ["HF_HUB_DISABLE_SYMLINKS_WARNING"] = "1"
os.environ["TRANSFORMERS_VERBOSITY"] = "error"
warnings.filterwarnings("ignore")

import numpy as np, requests
from sentence_transformers import SentenceTransformer

MODEL = "qwen3.5:0.8b"
EMBED_MODEL = "BAAI/bge-small-en-v1.5"                 # must be the same model EmbedScript.py used
QUERY_PREFIX = "Represent this sentence for searching relevant passages: "   # BGE wants this on questions only
FOLDER = Path(__file__).parent / "PreProcessingSAHPRAGuidelines" / "PreProcessesV1"
TOP_K = 5                                               # passages given to Qwen; raise num_ctx if you raise this

sys.stdout.reconfigure(encoding="utf-8")   # so PHP receives special characters correctly

question = sys.argv[1] if len(sys.argv) > 1 else "In one sentence, what does SAHPRA regulate?"

# Load the passages and the vectors EmbedScript.py saved for them
with open(FOLDER / "passages.jsonl", encoding="utf-8") as f:
    passages = [json.loads(line) for line in f]
vectors = np.load(FOLDER / "passage_vectors.npy")
if len(vectors) != len(passages):
    sys.exit("The passages have changed since they were embedded. Run EmbedScript.py again.")

# Embed the question and find the passages most similar to it
embedder = SentenceTransformer(EMBED_MODEL, device="cpu")
q = embedder.encode([QUERY_PREFIX + question], normalize_embeddings=True)[0]
best = np.argsort(-(vectors @ q))[:TOP_K]


def source_label(p):
    pages = f"p. {p['page_start']}" if p["page_start"] == p["page_end"] else f"pp. {p['page_start']}-{p['page_end']}"
    return f"{p['document_number'] or p['source_file']}: {p['title']}, {pages}"


sources = "\n\n".join(f"[{i}] {source_label(passages[j])}\n{passages[j]['text']}" for i, j in enumerate(best, start=1))

SYSTEM_PROMPT = (
    "You answer questions about South African Health Products Regulatory Authority (SAHPRA) guidelines. "
    "Use ONLY the numbered sources provided. Cite sources in square brackets, e.g. [1] or [2][3]. "
    "If the sources do not contain the answer, say you could not find it in the guidelines."
)

try:
    reply = requests.post("http://localhost:11434/api/chat", json={
        "model": MODEL,
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": f"Sources:\n{sources}\n\nQuestion: {question}"},
        ],
        "stream": False,
        "think": False,
        "options": {"num_ctx": 8192, "temperature": 0.2},   # small context window = less memory; low temperature = sticks to the sources
    }, timeout=300)
except requests.ConnectionError:
    sys.exit("Ollama isn't running. Start the Ollama app and try again.")

data = reply.json()

if "error" in data:                        # Ollama sends {"error": "..."} instead of a message when it fails
    if "out-of-memory" in data["error"] or "unable to allocate" in data["error"]:
        print("Not enough free memory to load the model. Close some programs and try again.")
    else:
        print("Ollama error:", data["error"])
else:
    print(data["message"]["content"])
    print("\nSources:")
    for i, j in enumerate(best, start=1):
        print(f"[{i}] {source_label(passages[j])}")
