"""
Turn the SAHPRA passages into vectors with BGE, then test a search.

Input:   PreProcessingSAHPRAGuidelines/PreProcessesV1/passages.jsonl  (made by TextCleanUp.py)
Output (same folder):
  passage_vectors.npy   one row of 384 numbers per passage
  passage_ids.json      the passage_id for each row, in the same order
"""

import json
from pathlib import Path

import numpy as np
from sentence_transformers import SentenceTransformer

FOLDER = Path(__file__).parent / "PreProcessingSAHPRAGuidelines" / "PreProcessesV1"
MODEL_NAME = "BAAI/bge-small-en-v1.5"     # same model as the Colab notebook, so the vectors match
QUERY_PREFIX = "Represent this sentence for searching relevant passages: "   # BGE wants this on questions only

with open(FOLDER / "passages.jsonl", encoding="utf-8") as f:
    passages = [json.loads(line) for line in f]
print(f"Loaded {len(passages)} passages")

embedder = SentenceTransformer(MODEL_NAME, device="cpu")   # "cuda" if you have an NVIDIA GPU

# normalize_embeddings=True makes every vector length 1, so a dot product gives the cosine similarity
vectors = embedder.encode([p["text"] for p in passages], batch_size=16,
                          normalize_embeddings=True, show_progress_bar=True)

np.save(FOLDER / "passage_vectors.npy", vectors)
with open(FOLDER / "passage_ids.json", "w", encoding="utf-8") as f:
    json.dump([p["passage_id"] for p in passages], f)
print("Saved vectors:", vectors.shape)


def search(question, k=3):
    """Return the k passages most similar to the question."""
    q = embedder.encode([QUERY_PREFIX + question], normalize_embeddings=True)[0]
    scores = vectors @ q
    best = np.argsort(-scores)[:k]
    return [(passages[i], float(scores[i])) for i in best]


for p, score in search("How do I report an adverse drug reaction?"):
    print(f"{score:.3f}  {p['document_number']}  {p['title'][:60]}  (pages {p['page_start']}-{p['page_end']})")
