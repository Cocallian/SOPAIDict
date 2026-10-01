import pymupdf

i = 146


print(f"Processing document {i}")
for i in range(146, 154):
    i = i + 1



    doc = pymupdf.open(f"Guidelines/doc{i}.pdf")
    out = open(f"Raw_Text/pdf{i}.txt", "wb")
    for page in doc: # iterate the document pages
        text = page.get_text().encode("utf8") # get plain text (is in UTF-8)
        out.write(text) # write text of page
        out.write(bytes((12,)))
doc.close()
out.close()
