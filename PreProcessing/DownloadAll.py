"""
Download every document from https://www.sahpra.org.za/guidelines/
as output/doc1.pdf, output/doc2.pdf, ... and write output/manifest.csv.

pip install requests beautifulsoup4
"""

import csv
import time
from pathlib import Path

import requests
from bs4 import BeautifulSoup

PAGE_URL = "https://www.sahpra.org.za/guidelines/"
OUTPUT_DIR = Path("output")
OUTPUT_DIR.mkdir(exist_ok=True)

session = requests.Session()
session.headers["User-Agent"] = "Mozilla/5.0"

# The page contains all rows of the table; the website only shows 25 at a time.
html = session.get(PAGE_URL, timeout=60).text
rows = BeautifulSoup(html, "html.parser").select("table.posts-data-table tbody tr")
print(f"Found {len(rows)} documents")

with open(OUTPUT_DIR / "manifest.csv", "w", newline="", encoding="utf-8") as f:
    writer = csv.writer(f)
    writer.writerow(["file", "document_number", "title", "date_updated", "link"])

    for i, row in enumerate(rows, start=1):
        number = row.select_one("td.col-document_number").get_text(strip=True)
        title = row.select_one("td.col-title").get_text(strip=True)
        date = row.select_one("td.col-date_updated").get_text(strip=True)
        link = row.select_one("td.col-link a")["href"]
        file_name = f"doc{i}.pdf"

        # The links are SharePoint share links; download=1 returns the file itself.
        download_url = link.split("?")[0] + "?download=1"
        response = session.get(download_url, timeout=120)

        if response.ok and response.content.startswith(b"%PDF"):
            (OUTPUT_DIR / file_name).write_bytes(response.content)
            print(f"[{i}/{len(rows)}] {file_name}  {number}  {title[:50]}")
        else:
            print(f"[{i}/{len(rows)}] FAILED {number}: {link}")
            file_name = "FAILED"

        writer.writerow([file_name, number, title, date, link])
        time.sleep(1)  # be polite to the server

print("Done. Files and manifest.csv are in the output folder.")