import os, sys, json, warnings, threading
from datetime import datetime
from dataclasses import dataclass, field
from itertools import count
from pathlib import Path
from queue import PriorityQueue

# keep Hugging Face warnings and progress bars out of the terminal
os.environ["HF_HUB_OFFLINE"] = "1"                     # the model is already downloaded, so don't check online
os.environ["HF_HUB_DISABLE_PROGRESS_BARS"] = "1"
os.environ["HF_HUB_DISABLE_SYMLINKS_WARNING"] = "1"
os.environ["TRANSFORMERS_VERBOSITY"] = "error"
warnings.filterwarnings("ignore")

import numpy as np
import requests
from flask import Flask, request
from reportapi import register_report_routes
from sentence_transformers import SentenceTransformer

PROJECT = Path(sys.executable).parent if getattr(sys, "frozen", False) else Path(__file__).parent   # next to the .exe when built with PyInstaller

# Settings come from config.json; it stores them as text, so the numbers are converted here
with open(PROJECT / "config.json", encoding="utf-8") as f:
    config = json.load(f)

ORG_NAME = config["ORG_NAME"]                          # the organisation named in the prompt below
MODEL = config["MODEL_NAME"]                           # the Ollama model that writes the answers
EMBED_MODEL = config["EMBED_MODEL"]                    # must be the same model EmbedScript.py used
TOP_K = int(config["SOURCES"])                         # passages given to Qwen; raise CONTEXT_WINDOW if you raise this
CONTEXT_WINDOW = int(config["CONTEXT_WINDOW"])         # how much text Qwen can read; smaller = less memory
TEMPERATURE = float(config["TEMPERATURE"])             # low = sticks to the sources
ANSWER_LENGTH = int(config["LENGTH"])                  # longest answer Qwen may write, in tokens
PORT = int(config["PORT"])                             # Agent.php and adminAgent.php send questions to this port
QUERY_PREFIX = "Represent this sentence for searching relevant passages: "   # BGE wants this on questions only
PASSAGES_FOLDER = PROJECT / "PreProcessing" / "Raw_Text"   # passages.jsonl from TextCleanUp.py
VECTORS_FOLDER = PROJECT / "PreProcessing" / "Embeds"      # passage_vectors.npy from EmbedScript.py
PRIORITIES = {"admin": 0, "user": 1}                   # lower numbers are answered first

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
            "options": {"num_ctx": CONTEXT_WINDOW, "temperature": TEMPERATURE, "num_predict": ANSWER_LENGTH},
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


# ---- Priority queue: questions wait here and are answered one at a time, admins first ----

@dataclass(order=True)
class QueuedQuestion:
    priority: int                                      # from PRIORITIES; the queue sorts on this first
    arrival_number: int                                # then on this, so equal priorities are answered first come, first served
    question: str = field(compare=False)
    answered: threading.Event = field(default_factory=threading.Event, compare=False)   # set once the answer is ready
    answer: str = field(default="", compare=False)


question_queue = PriorityQueue()
arrival_numbers = count()                             


def answer_P():
    # Runs forever in its own thread: take the most important waiting question, answer it, repeat
    while True:
        waiting = question_queue.get()                 # blocks until a question is waiting
        try:
            waiting.answer = answer(waiting.question)
        except Exception as error:                     # one bad question mustn't stop the queue
            waiting.answer = f"Something went wrong while answering: {error}"
        waiting.answered.set()                         # wakes up the /ask request that is waiting for this answer


app = Flask(__name__)
register_report_routes(app, PROJECT)                   # adds /report, reports.php asks it for the report file


@app.post("/ask")
def ask():
    # Agent.php and adminAgent.php post the question as "question" and the asker's role ("admin" or "user") as "role"
    question = request.form.get("question", "").strip()
    if not question:
        return "No question was sent.", 400

    # PHP passes on who asked, because from here every request just looks like 127.0.0.1
    print(f"[{datetime.now():%H:%M:%S}] {request.form.get('ip', 'unknown')} "
          f"({request.form.get('user') or 'unknown user'}, {request.form.get('role', 'user')}): {question}")

    priority = PRIORITIES.get(request.form.get("role"), PRIORITIES["user"])   # anything unknown is treated as a user
    queued = QueuedQuestion(priority, next(arrival_numbers), question)
    question_queue.put(queued)
    queued.answered.wait()                             # wait here until the worker thread has answered it

    return queued.answer, 200, {"Content-Type": "text/plain; charset=utf-8"}


if __name__ == "__main__":
    threading.Thread(target=answer_P, daemon=True).start()
    print(f"Answer server ready on http://127.0.0.1:{PORT}/ask - leave this window open while the website is in use")
    app.run(host="127.0.0.1", port=PORT, threaded=True)
