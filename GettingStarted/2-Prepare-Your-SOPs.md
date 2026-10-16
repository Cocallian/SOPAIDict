# 2. Prepare your SOPs

This step turns your SOP documents into short, clean passages the assistant can search. It uses two scripts in `PreProcessingSAHPRAGuidelines/`:

1. **`PyPDF.py`** copies the text out of each PDF into a plain text file.
2. **`TextCleanUp.py`** removes headers, footers, page numbers and tables of contents, then cuts each document into overlapping 300-word passages.

These scripts were written for SAHPRA's guideline documents. They work on other SOPs, but read [what the clean-up can't handle](#what-the-clean-up-script-cant-handle) before relying on the results.

## 2.1 Get your SOPs into the right format

The scripts only read **PDFs that contain real text**. To check a PDF, open it and try to select a sentence with the mouse. If you can select and copy it, the PDF is fine.

| What you have | What to do |
|---|---|
| A PDF where text can be selected | Nothing; it's ready. |
| Word, Excel or other Office files | Open the file and use **File → Save As → PDF**. |
| A scanned PDF (text is a picture and can't be selected) | Run it through OCR (text recognition) first, for example Adobe Acrobat's **Scan & OCR → Recognize text**. If you don't, it will be skipped with "almost no text (probably a scanned PDF)". |
| Several SOPs in one PDF | Split them into one PDF per SOP, so each answer cites the right document. |

## 2.2 Decide: your SOPs only, or alongside SAHPRA's guidelines

The project comes with about 150 SAHPRA guidelines already in `Guidelines/` and `Raw_Text/`. `TextCleanUp.py` processes every text file in `Raw_Text/`, so whatever is in that folder is what the assistant can search.

- **Your SOPs only.** Move everything in `Guidelines/` and `Raw_Text/` into a backup folder outside `PreProcessingSAHPRAGuidelines`, then number your SOPs from 1.
- **Both.** Leave the SAHPRA files where they are and number your SOPs after the highest existing number. For example, if the last file is `doc153.pdf`, your first SOP is `doc154.pdf`.

## 2.3 Name your files and put them in `Guidelines/`

`PyPDF.py` only recognises files named **`doc` + a number + `.pdf`**:

```
PreProcessingSAHPRAGuidelines/Guidelines/doc1.pdf
PreProcessingSAHPRAGuidelines/Guidelines/doc2.pdf
PreProcessingSAHPRAGuidelines/Guidelines/doc3.pdf
```

- Number them without gaps. A missing number stops `PyPDF.py` with a "no such file" error.
- Keep a list of which number is which SOP, for example a spreadsheet with *file, SOP number, title, version*. The assistant cites the SOP number if it can find one in the document (see [2.5](#25-optional-but-recommended-teach-the-clean-up-script-your-sop-numbers)). If it can't, it cites the file name, such as `pdf12.txt`, and you'll want to know which SOP that is.

## 2.4 Convert the PDFs to text with `PyPDF.py`

`PyPDF.py` converts a fixed range of numbers, which you set each time. Open it and find these lines:

```python
for i in range(146, 154):
    i = i + 1
```

Set the two numbers to **(first file number − 1, last file number)**:

| To convert | Change the line to |
|---|---|
| `doc1.pdf` to `doc20.pdf` | `for i in range(0, 20):` |
| `doc154.pdf` to `doc160.pdf` | `for i in range(153, 160):` |

Then run it from the `PreProcessingSAHPRAGuidelines` folder:

```powershell
cd "C:\path\to\LLM\PreProcessingSAHPRAGuidelines"
py PyPDF.py
```

**Check:** `Raw_Text/` now has a `pdfN.txt` for every `docN.pdf` you converted. Open one in Notepad. You should see the SOP's text, with page breaks shown as small boxes or arrows.

## 2.5 (Optional but recommended) Teach the clean-up script your SOP numbers

`TextCleanUp.py` tries to find each document's number, title, version and date, so answers can cite "SOP-QA-012: Deviation Handling, p. 4" instead of "pdf12.txt". Two settings are SAHPRA-specific and worth changing.

**Document number** (near the top, around line 24). It only recognises SAHPRA numbers like `SAHPGL-CEM-01`:

```python
DOC_NUMBER = re.compile(r"SAHP[A-Z]{1,4}(?:-[A-Z0-9]+)+")
```

Change it to match your numbering. Examples:

| Your SOP numbers look like | Use |
|---|---|
| `SOP-QA-012` | `re.compile(r"SOP-[A-Z]+-\d+")` |
| `QA-SOP-0012` | `re.compile(r"[A-Z]{2,4}-SOP-\d+")` |

If you're keeping the SAHPRA guidelines as well, join both patterns with `|` (meaning "or"):

```python
DOC_NUMBER = re.compile(r"SAHP[A-Z]{1,4}(?:-[A-Z0-9]+)+|SOP-[A-Z]+-\d+")
```

**Letterhead** (around line 88). The title is taken from the first line on page 1 with at least three words and no digits, but SAHPRA's letterhead is skipped:

```python
and "south african health products" not in line.lower() \
```

Replace `south african health products` with your organisation's name in lower case, for example `"acme pharmaceuticals"`. Otherwise your letterhead may become every SOP's title.

## 2.6 Clean and split the text with `TextCleanUp.py`

Run it from the same folder:

```powershell
py TextCleanUp.py
```

It finishes with a line like `Done: 1876 passages from 150 files, saved in 'PreProcessesV1'`. Each run replaces the previous output, built from everything in `Raw_Text/`.

**Check:** open `PreProcessesV1/manifest.csv` in Excel. There is one row per passage, with the columns:
- `source_file`
- `document_number`, `title`, `version`, `date`
- `page_start`, `page_end`

Look through the four detail columns for a few of your SOPs. This is what the assistant will cite. If the numbers or titles are wrong, adjust [2.5](#25-optional-but-recommended-teach-the-clean-up-script-your-sop-numbers) and run it again. Also look in the terminal output for `Skipping …: almost no text` lines. Those are scanned PDFs that need OCR.

## What the clean-up script can't handle

These are the limitations of the current `TextCleanUp.py`. Most only affect how neat the citations are; the ones marked ⚠ can remove or scramble text the assistant needs.

| Limitation | What you'll see | Workaround |
|---|---|---|
| **PDF only, with real text.** Word, Excel and scanned documents can't be read directly. | Scanned files are skipped with "almost no text". | Save as PDF; OCR scanned documents (2.1). |
| ⚠ **Repeated lines are treated as headers and footers.** Any short line that appears on at least half the pages is deleted, with numbers ignored, so "Page 3" and "Page 4" count as the same line. In a document without page breaks, any line that appears 4 or more times is deleted. | Lines such as "Signature: ______", "Yes / No" or "Responsible person:" that repeat through a form or checklist disappear. | Check `passages.jsonl` for SOPs that are mostly forms or checklists. If text is missing, don't rely on those SOPs. |
| ⚠ **Tables and flowcharts become running text.** PDF text extraction reads across rows, so columns can be mixed together and flowchart arrows are lost. | Answers about tables (such as limits or responsibilities) may be muddled. | Where it matters, add a plain-text version of key tables to the SOP before converting. |
| **Document numbers are only found in SAHPRA's format** until you change `DOC_NUMBER`. The most frequent match in the document wins, so an SOP that refers to another SOP more often than to itself gets the wrong number. | Citations show `pdfN.txt`, or the wrong SOP number. | Change `DOC_NUMBER` (2.5); keep your file list (2.3). |
| **Title guessing is simple.** It takes the first line on page 1 with at least 3 words and no digits, so titles containing a number ("Cleaning of Area 2") are skipped. | The wrong title, or a blank one. | Change the letterhead filter (2.5); check `manifest.csv`. |
| **Version is the first "Version 2" / "Rev 3" / "v2" anywhere in the document.** With a revision-history table, that may be an old version. **Date** is the first date on the first two pages, which may be the effective date or the review date. | An outdated version or the wrong date in citations. | Check `manifest.csv`. This only affects citations, not answers. |
| **Passages are a fixed 300 words** (overlapping by 50) and don't follow headings. | A passage can start mid-section, without its heading. | Change `PASSAGE_WORDS` and `OVERLAP_WORDS` at the top. Shorter passages are more precise; longer ones carry more context but fill the model's limited prompt faster. |
| **Words split with a hyphen are rejoined** whenever the next word starts in lower case, which also catches "pre- and post-" (becomes "preand post-"). | Occasional joined words. | Harmless for search in most cases. |
| **English only.** The embedding model (step 3) is trained on English text. | Poor search results for other languages. | Use a multilingual embedding model (needs code changes in step 3 and step 4). |

---

Next: [3. Embed your SOPs](3-Embed-Your-SOPs.md)
