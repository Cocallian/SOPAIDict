"""
Download all 159 documents from https://www.sahpra.org.za/guidelines/
and save them as doc1.pdf ... doc159.pdf in an "output" folder.

How the page works (from the saved HTML):
  * The list is a "Document Library Pro" DataTable (table.posts-data-table)
    with "serverSide": false, so the server sends ALL 159 rows in the page
    and the browser only shows 25 at a time.
  * Each row's "Download" button (td.col-link a) is a SharePoint sharing link
    like https://sahpraza.sharepoint.com/:b:/s/.../IQC4qg...?e=vBL2Ad
    Adding "download=1" to that link returns the PDF file itself.
  * The site sorts the table by "Date Updated" (newest first), so the
    script uses the same order: doc1.pdf is the top row on page 1.

Requirements:
    pip install requests beautifulsoup4
Optional fallback (only used if the plain request doesn't return all rows):
    pip install playwright && playwright install chromium
"""

import csv
import time
from pathlib import Path
from urllib.parse import urlparse, parse_qsl, urlencode, urlunparse

import requests
from bs4 import BeautifulSoup

PAGE_URL = "https://www.sahpra.org.za/guidelines/"
OUTPUT_DIR = Path("Guidelines")
EXPECTED_TOTAL = 159
DELAY_SECONDS = 1.0  # pause between downloads

HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/124.0 Safari/537.36"
    )
}


# ---------------------------------------------------------------- parsing
def parse_rows(html: str) -> list[dict]:
    """Read every document row from the guidelines table."""
    soup = BeautifulSoup(html, "html.parser")
    rows = []
    for i, tr in enumerate(soup.select("table.posts-data-table tbody tr.post-row")):
        link = tr.select_one("td.col-link a[href]")
        if not link:
            continue
        date_td = tr.select_one("td.col-date_updated")
        title_td = tr.select_one("td.col-title")
        number_td = tr.select_one("td.col-document_number")
        rows.append({
            "index": i,  # original position, used to break ties when sorting
            "number": number_td.get_text(strip=True) if number_td else "",
            "title": title_td.get_text(strip=True) if title_td else "",
            "date": date_td.get_text(strip=True) if date_td else "",
            "sort_key": int(date_td.get("data-sort", 0) or 0) if date_td else 0,
            "url": link["href"].strip(),
        })
    # Match the site's default order: Date Updated, newest first.
    rows.sort(key=lambda r: (-r["sort_key"], r["index"]))
    return rows


def get_rows_with_requests(session: requests.Session) -> list[dict]:
    print(f"Fetching {PAGE_URL}")
    resp = session.get(PAGE_URL, timeout=60)
    resp.raise_for_status()
    return parse_rows(resp.text)


def get_rows_with_browser() -> list[dict]:
    """Fallback: open the page in a browser, choose 'All' per page, read the table."""
    from playwright.sync_api import sync_playwright

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page(user_agent=HEADERS["User-Agent"])
        page.goto(PAGE_URL, wait_until="networkidle", timeout=90000)
        # The "Show [25] per page" dropdown has an "All" option with value -1.
        page.select_option("div.dataTables_length select", "-1")
        page.wait_for_timeout(2000)
        html = page.content()
        browser.close()
    return parse_rows(html)


# ---------------------------------------------------------------- downloading
def direct_download_url(share_url: str) -> str:
    """Turn a SharePoint sharing link into a direct-download link."""
    parts = urlparse(share_url)
    query = dict(parse_qsl(parts.query))
    query.pop("e", None)          # tracking token, not needed
    query["download"] = "1"
    return urlunparse(parts._replace(query=urlencode(query)))


def fetch_pdf(session: requests.Session, share_url: str) -> bytes:
    """Download one PDF, checking that we really got a PDF back."""
    resp = session.get(direct_download_url(share_url), timeout=120, allow_redirects=True)
    resp.raise_for_status()
    if resp.content[:5] == b"%PDF-":
        return resp.content

    # Some SharePoint links need the share page visited first to set a cookie.
    session.get(share_url, timeout=60, allow_redirects=True)
    resp = session.get(direct_download_url(share_url), timeout=120, allow_redirects=True)
    resp.raise_for_status()
    if resp.content[:5] == b"%PDF-":
        return resp.content

    raise ValueError(f"response was not a PDF (Content-Type: {resp.headers.get('Content-Type')})")


def download_all(session: requests.Session, rows: list[dict]) -> None:
    OUTPUT_DIR.mkdir(exist_ok=True)
    failed = 0

    with open(OUTPUT_DIR / "manifest.csv", "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["file", "document_number", "title", "date_updated", "link", "status"])

        for n, row in enumerate(rows, start=1):
            name = f"doc{n}.pdf"
            dest = OUTPUT_DIR / name
            label = f"[{n}/{len(rows)}] {name}"

            if dest.exists() and dest.stat().st_size > 0:
                print(f"{label} already exists, skipping")
                status = "skipped (exists)"
            else:
                try:
                    dest.write_bytes(fetch_pdf(session, row["url"]))
                    print(f"{label}  <-  {row['number']}  {row['title'][:60]}")
                    status = "ok"
                except Exception as e:
                    failed += 1
                    print(f"{label} FAILED: {e}")
                    status = f"failed: {e}"
                time.sleep(DELAY_SECONDS)

            writer.writerow([name, row["number"], row["title"], row["date"], row["url"], status])

    print(f"\nDone. Saved to '{OUTPUT_DIR.resolve()}' ({failed} failed).")
    print("manifest.csv lists which document each docN.pdf is.")
    if failed:
        print("Run the script again to retry the failed ones; finished files are skipped.")


# ---------------------------------------------------------------- main
def main() -> None:
    session = requests.Session()
    session.headers.update(HEADERS)

    rows = get_rows_with_requests(session)
    print(f"Found {len(rows)} documents in the page.")

    if len(rows) < EXPECTED_TOTAL:
        print("Fewer than expected, trying a headless browser with 'Show All'...")
        try:
            browser_rows = get_rows_with_browser()
            if len(browser_rows) > len(rows):
                rows = browser_rows
            print(f"Browser found {len(rows)} documents.")
        except ImportError:
            print("Playwright isn't installed, so continuing with what was found.")

    if not rows:
        print("No documents found.")
        return

    download_all(session, rows)


if __name__ == "__main__":
    main()