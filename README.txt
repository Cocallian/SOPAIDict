------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Thank you for downloading and trying my project!
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Before you start hosting your own RAG pipeline for your own documents a few 
things to note:

1. There is a getting started guide in this folder that outines step by step everything needed for this to run.
2. The embedding (converting your documents into data the LLM can understand) can only work with pdfs
3. The PyPDF.py script (the script that turns your documents into text for further processing) has limitations on getting information out of pdf files, images wont and some tables will not translate correctly
4. The model being hosted needs a decent amount of avaiable RAM, in testing 8GB is more than enough but note that output performance will vary on your processing speed
5. The executables require administrator permissions and/or read and write permissions in this folder as well as the folder where your documents are stored
6. Yes the EmbedScript.py is meant to take that long, it is converting all your text inside the documents into a multi-dimensional array of how each word and phrase relate to each other (this helps the LLM [Qwen] search through the data and output the most similar text)
7. Current testing has a 20-40~ second delay before an answer on Google's T4 GPUs (not accounting for network traffic)
8. The website allows multiple prompts at once (although it can use a lot more memory)
9. The base inject prompt for the LLM is by default in context of SAHPRA, in config.json change the "ORG_NAME" : "South African Health Products Regulatory Authority (SAHPRA)" field and replace the SAHPRA text with your organisation's name

----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Basic flowchart:

Preparing your Documents:
Run PyPDF -> choose working directory -> run TextCleanUP -> run EmbedScript

Front end:
Copy all files in FrontEnd to your hosting directory (htdocs if using xampp) and port-forward, here is a tutorial that might help: https://www.youtube.com/watch?v=gsNF7trOtU8

Back End: 
Set back end directory via prompts from front end

________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________________

For Inquiries or help installing please email: "tygranwozniak100@gmail.com" or start a discussion on the github page