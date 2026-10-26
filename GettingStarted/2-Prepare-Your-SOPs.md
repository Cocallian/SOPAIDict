# 2. Prepare your SOPs

This step turns your SOP documents into short, clean passages the assistant can search. It uses two scripts in the `PreProcessing` folder:

1. **`PyPDF.py`** copies the text out of each PDF into a plain text file.
2. **`TextCleanUp.py`** removes headers, footers, page numbers and tables of contents, then cuts each document into overlapping 300-word passages.

These scripts were written for SAHPRA's guideline documents. They work on other SOPs, but read [what the clean-up can't handle](#what-the-clean-up-script-cant-handle) before relying on the results.

## 2.1 Set your organisation's name

Open `config.json` in the main `LLM` folder and put your organisation's name in `ORG_NAME`:

```json
{
    "WORKING_DIRECTORY": "",
    "ORG_NAME": "Acme Pharmaceuticals"
}
```

The AI is told it answers questions about *ORG_NAME*'s SOP documents, so this is the name it works under. Leave `WORKING_DIRECTORY` alone; `PyPDF.py` fills it in.

Keep the quotes and commas exactly as shown. If the file isn't valid JSON, `PyPDF.py` and `LocalAITest.py` stop with an error.

## 2.2 Get your SOPs into the right format

Put all your SOPs **in one folder**, anywhere on your PC. File names don't matter, and you don't need to rename anything. The names are kept and shown in the answers' citations, so descriptive names such as `SOP-QA-012 Deviation Handling.pdf` help.

`PyPDF.py` only reads **PDFs that contain real text**, in that folder itself (not in subfolders). To check a PDF, open it and try to select a sentence with the mouse.

| What you have | What to do |
|---|---|
| A PDF where text can be selected | Nothing; it's ready. |
| Word, Excel or other Office files | Open the file and use **File → Save As → PDF**. Other file types are skipped. |
| A scanned PDF (text is a picture and can't be selected) | Run it through OCR (text recognition) first, for example Adobe Acrobat's **Scan & OCR → Recognize text**. If you don't, it's skipped later with "almost no text (probably a scanned PDF)". |
| Several SOPs in one PDF | Split them into one PDF per SOP, so each answer cites the right document. |
| SOPs in subfolders | Move them into the main SOP folder. |

## 2.3 Clear out old text files

`Raw_Text` keeps the text of every document converted before, and `TextCleanUp.py` uses **everything** in it. So before converting a new set of SOPs, delete the contents of `PreProcessing/Raw_Text`. Otherwise old documents (including SAHPRA's, if they're there) get mixed in with yours.

## 2.4 Copy the text out of your PDFs with `PyPDF.py`

Run it from any folder:

```powershell
cd "C:\path\to\LLM"
py PreProcessing\PyPDF.py
```

1. A window opens: choose the folder with your SOP PDFs.
2. The folder is saved as `WORKING_DIRECTORY` in `config.json`.
3. Each PDF is converted, with a `Processing <name>.pdf` line for each.

Each PDF becomes `PreProcessing/Raw_Text/<same name>.txt`. For example, `SOP-QA-012 Deviation Handling.pdf` becomes `SOP-QA-012 Deviation Handling.txt`. Pages are separated by a page-break character, which the next script uses to find headers and footers.

**Check:** `PreProcessing/Raw_Text` has one `.txt` file per PDF. Open one in Notepad; you should see the SOP's text.

## 2.5 (Optional but recommended) Teach the clean-up script your SOP numbers

`TextCleanUp.py` tries to find each document's number, title, version and date, so answers can cite "SOP-QA-012: Deviation Handling, p. 4" instead of just the file name. Two settings are SAHPRA-specific and worth changing.

**Document number** (line 24). It only recognises SAHPRA numbers like `SAHPGL-CEM-01`:

```python
DOC_NUMBER = re.compile(r"SAHP[A-Z]{1,4}(?:-[A-Z0-9]+)+")
```

Change it to match your numbering:

| Your SOP numbers look like | Use |
|---|---|
| `SOP-QA-012` | `re.compile(r"SOP-[A-Z]+-\d+")` |
| `QA-SOP-0012` | `re.compile(r"[A-Z]{2,4}-SOP-\d+")` |

To recognise both SAHPRA's and yours, join the patterns with `|` (meaning "or"):

```python
DOC_NUMBER = re.compile(r"SAHP[A-Z]{1,4}(?:-[A-Z0-9]+)+|SOP-[A-Z]+-\d+")
```

**Letterhead** (line 88). The title is taken from the first line on page 1 with at least three words and no digits, but SAHPRA's letterhead is skipped:

```python
if len(line.split()) >= 3 and "south african health products" not in line.lower() \
```

Replace `south african health products` with your organisation's name in lower case, such as `"acme pharmaceuticals"`. Otherwise your letterhead may become every SOP's title.

## 2.6 Clean and split the text with `TextCleanUp.py`

This one must be run from **inside the `PreProcessing` folder**:

```powershell
cd "C:\path\to\LLM\PreProcessing"
py TextCleanUp.py
```

It finishes with `Done: 42 passages from 2 files, saved in 'Raw_Text'`. It writes two files into `PreProcessing/Raw_Text`, replacing the previous ones:

| File | Contents |
|---|---|
| `passages.jsonl` | One passage per line: its text plus the document details. This is what step 3 embeds and what the answers cite. |
| `manifest.csv` | The same details without the text, for checking in Excel |

**Check:** open `Raw_Text/manifest.csv` in Excel. There is one row per passage, with columns including `source_file`, `document_number`, `title`, `version`, `date`, `page_start` and `page_end`. Look through the details for a few SOPs; this is what the assistant will cite. If numbers or titles are wrong, adjust [2.5](#25-optional-but-recommended-teach-the-clean-up-script-your-sop-numbers) and run it again. Also look for `Skipping …: almost no text` lines in the output; those are scanned PDFs that need OCR.

## What the clean-up script can't handle

These are the limitations of the current `TextCleanUp.py`. Most only affect how neat the citations are; the ones marked ⚠ can remove or scramble text the assistant needs.

| Limitation | What you'll see | Workaround |
|---|---|---|
| **PDF only, with real text.** Word, Excel and scanned documents can't be read directly. | Non-PDFs are skipped by `PyPDF.py`; scans are skipped with "almost no text". | Save as PDF; OCR scanned documents (2.2). |
| ⚠ **Repeated lines are treated as headers and footers.** Any short line on at least half the pages is deleted, with numbers ignored, so "Page 3" and "Page 4" count as the same line. In a one-page document, any line appearing 4 or more times is deleted. | Lines such as "Signature: ______", "Yes / No" or "Responsible person:" that repeat through a form or checklist disappear. | Check `passages.jsonl` for SOPs that are mostly forms or checklists. |
| ⚠ **Tables and flowcharts become running text.** PDF text extraction reads across rows, so columns can be mixed together and flowchart arrows are lost. | Answers about tables (such as limits or responsibilities) may be muddled. | Where it matters, add a plain-text version of key tables to the SOP before converting. |
| **Document numbers are only found in SAHPRA's format** until you change `DOC_NUMBER`. The most frequent match in the document wins, so an SOP that refers to another SOP more often than to itself gets the wrong number. | Citations show the file name, or the wrong SOP number. | Change `DOC_NUMBER` (2.5); use descriptive file names (2.2). |
| **Title guessing is simple.** It takes the first line on page 1 with at least 3 words and no digits, so titles containing a number ("Cleaning of Area 2") are skipped. When none is found, citations read like `Deviation Handling.txt: , p. 3`. | The wrong title, or a blank one. | Change the letterhead filter (2.5); check `manifest.csv`. |
| **Version is the first "Version 2" / "Rev 3" / "v2" anywhere in the document.** With a revision-history table, that may be an old version. **Date** is the first date on the first two pages, which may be the effective or the review date. | An outdated version or the wrong date in citations. | Check `manifest.csv`. This only affects citations, not answers. |
| **Passages are a fixed 300 words** (overlapping by 50) and don't follow headings. | A passage can start mid-section, without its heading. | Change `PASSAGE_WORDS` and `OVERLAP_WORDS` (lines 20–21). Shorter passages are more precise; longer ones carry more context but fill the model's limited prompt faster. |
| **Words split with a hyphen are rejoined** whenever the next word starts in lower case, which also catches "pre- and post-" (becomes "preand post-"). | Occasional joined words. | Harmless for search in most cases. |
| **English only.** The embedding model (step 3) is trained on English text. | Poor search results for other languages. | Use a multilingual embedding model (needs code changes in steps 3 and 4). |

---

Next: [3. Embed your SOPs](3-Embed-Your-SOPs.md)
