"""
Clean the SAHPRA text files and split them into passages for RAG.

Cleaning: removes repeated headers/footers, page numbers and table of contents
lines, and rejoins words split with a hyphen at the end of a line ("regu-" + "lation").

Output (in the passages folder):
  passages.jsonl  one passage per line, with its text and details
  manifest.csv    the same details without the text
"""

import csv
import json
import re
from collections import Counter
from pathlib import Path

INPUT_DIR = Path("Raw_Text")
OUTPUT_DIR = Path("PreProcessesV1")
PASSAGE_WORDS = 300      # words per passage
OVERLAP_WORDS = 50       # words repeated at the start of the next passage

PAGE_NUMBER = re.compile(r"^\s*(page\s*)?-?\s*\d+\s*((of|/)\s*\d+)?\s*-?\s*$", re.IGNORECASE)#determines wether the line read is a part of a page number or not
DOC_NUMBER = re.compile(r"SAHP[A-Z]{1,4}(?:-[A-Z0-9]+)+")#determines wether the line read is a part of a document number or not
VERSION = re.compile(r"\b(?:version|rev)\.?\s*:?\s*(\d+(?:\.\d+)?)|\bv(\d+(?:\.\d+)?)\b", re.IGNORECASE)#determines wether the line read is a part of a version or not
DATE = re.compile(r"\b\d{1,2}[/.-]\d{1,2}[/.-]\d{4}\b|\b\d{1,2}\s+[A-Z][a-z]+\s+\d{4}\b|\b[A-Z][a-z]+\s+\d{4}\b")#determines wether the line read is a part of a date or not
TOC_ENTRY = re.compile(r"(?:[.…�]\s?){4,}\s*(?:\d+|Error! Bookmark not defined\.)\s*$")#determines wether the line is a table of contents entry ("Scope ........ 7"); lines like "EPA ..... 80 mg" are kept
SECTION_NUMBER = re.compile(r"^\s*\d+(?:\.\d+)*\.?\s*$")#determines wether the line is just a section number ("3.1.1") on its own


def simplify(line):
    """Lowercase and blank out numbers, so 'Page 3' and 'Page 4' count as the same line."""
    return re.sub(r"\d+", "#", line.strip().lower())


def clean_pages(raw):
    """Return a list of pages, each a list of lines, with headers and page numbers removed."""
    pages = [page.split("\n") for page in raw.split("\f") if page.strip()]

    # A short line found on at least half the pages (or 4+ times if there are no page breaks) is a header/footer
    if len(pages) > 1:
        counts = Counter(key for page in pages for key in {simplify(l) for l in page if l.strip()})
        needed = max(2, len(pages) // 2)
    else:
        counts = Counter(simplify(l) for l in pages[0] if l.strip()) if pages else Counter()
        needed = 4
    headers = {key for key, n in counts.items() if n >= needed and len(key) <= 120}

    return [remove_contents([l for l in page if l.strip() and simplify(l) not in headers and not PAGE_NUMBER.match(l)])
            for page in pages]


def remove_contents(lines):
    """Remove table of contents entries, plus the section numbers and 'Contents' heading sitting just before them."""
    kept = []
    for i, line in enumerate(lines):
        if TOC_ENTRY.search(line):
            continue
        next_line = lines[i + 1] if i + 1 < len(lines) else ""
        if TOC_ENTRY.search(next_line) and (SECTION_NUMBER.match(line) or line.strip().lower() in {"contents", "table of contents"}):
            continue
        kept.append(line)
    return kept


def words_with_pages(pages):
    """List every word with its page number, joining words that were split with a hyphen."""
    words = []
    for page_number, lines in enumerate(pages, start=1):
        for word in " ".join(lines).split():
            if words and words[-1][0].endswith("-") and word[0].islower():
                words[-1] = (words[-1][0][:-1] + word, words[-1][1])   # "regu-" + "lation"
            else:
                words.append((word, page_number))
    return words


def document_details(raw, pages):
    """Find the document number, title, version and date."""
    numbers = DOC_NUMBER.findall(raw)
    first_pages = "\n".join("\n".join(p) for p in pages[:2])
    version = VERSION.search(raw)
    date = DATE.search(first_pages)

    title = ""
    for line in (pages[0] if pages else []):
        line = line.strip()
        if len(line.split()) >= 3 and "south african health products" not in line.lower() \
                and not DOC_NUMBER.search(line) and not re.search(r"\d", line):
            title = line
            break

    return {
        "document_number": Counter(numbers).most_common(1)[0][0] if numbers else "",
        "title": title,
        "version": (version.group(1) or version.group(2)) if version else "",
        "date": date.group(0) if date else "",
    }


OUTPUT_DIR.mkdir(exist_ok=True)
files = sorted(INPUT_DIR.glob("*.txt"), key=lambda p: [int(t) if t.isdigit() else t for t in re.split(r"(\d+)", p.name)])
fields = ["passage_id", "source_file", "document_number", "title", "version", "date",
          "page_start", "page_end", "word_count"]
total = 0

with open(OUTPUT_DIR / "passages.jsonl", "w", encoding="utf-8") as jsonl, \
     open(OUTPUT_DIR / "manifest.csv", "w", newline="", encoding="utf-8") as manifest:
    writer = csv.DictWriter(manifest, fieldnames=fields)
    writer.writeheader()

    for path in files:
        raw = path.read_text(encoding="utf-8", errors="replace")
        pages = clean_pages(raw)
        words = words_with_pages(pages)
        if len(words) < 30:
            print(f"Skipping {path.name}: almost no text (probably a scanned PDF)")
            continue

        details = document_details(raw, pages)
        step = PASSAGE_WORDS - OVERLAP_WORDS

        for n, start in enumerate(range(0, max(len(words) - OVERLAP_WORDS, 1), step), start=1):
            chunk = words[start:start + PASSAGE_WORDS]
            record = {
                "passage_id": f"{path.stem}-p{n:04d}",
                "source_file": path.name,
                **details,
                "page_start": chunk[0][1],
                "page_end": chunk[-1][1],
                "word_count": len(chunk),
            }
            writer.writerow(record)
            jsonl.write(json.dumps({**record, "text": " ".join(w for w, _ in chunk)}, ensure_ascii=False) + "\n")
            total += 1

print(f"Done: {total} passages from {len(files)} files, saved in '{OUTPUT_DIR}'")