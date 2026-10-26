import pymupdf
import crossfiledialog as cfd
import json
import os

CONFIG = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "config.json")   # config.json in the main project folder

W_DIR = cfd.choose_folder("Choose your working directory for SOPs")

if not W_DIR:
    raise SystemExit("No folder chosen.")

print("Chosen directory: ", W_DIR)

# save the chosen folder in config.json so the other scripts can use it
with open(CONFIG, encoding="utf-8") as f:
    config = json.load(f)
config["WORKING_DIRECTORY"] = W_DIR
with open(CONFIG, "w", encoding="utf-8") as f:
    json.dump(config, f, indent=4)

os.chdir(W_DIR)
RAW_TEXT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "Raw_Text")   # where TextCleanUp.py reads from
os.makedirs(RAW_TEXT, exist_ok=True)

for filename in os.listdir(W_DIR):
    if not os.path.isfile(filename) or not filename.lower().endswith(".pdf"):
        continue   # skip folders and anything that isn't a PDF

    print(f"Processing {filename}")
    with pymupdf.open(filename) as doc, \
         open(os.path.join(RAW_TEXT, os.path.splitext(filename)[0] + ".txt"), "wb") as out:
        for page in doc: # iterate the document pages
            text = page.get_text().encode("utf8") # get plain text (is in UTF-8)
            out.write(text) # write text of page
            out.write(bytes((12,)))
