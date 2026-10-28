# Getting started

This guide takes you from a folder of your own SOP documents to a working website where people log in and ask questions about them. The assistant finds the passages that answer the question and has a small AI model (Qwen) write an answer that cites them.

Work through the documents in order. Each one ends with a check, so you know a step worked before you move on.

| Step | Document | What you end up with |
|---|---|---|
| 1 | [Install the software](1-Install-Software.md) | Python, Ollama, XAMPP and Composer installed |
| 2 | [Prepare your SOPs](2-Prepare-Your-SOPs.md) | Your organisation's name set, and your SOPs turned into clean, searchable passages |
| 3 | [Embed your SOPs](3-Embed-Your-SOPs.md) | Vectors that let the assistant search your SOPs by meaning |
| 4 | [Answer questions: on your PC or Google Colab](4-Choose-Local-or-Colab.md) | The answer server running and answering questions about your SOPs |
| 5 | [Set up the website](5-Set-Up-the-Website.md) | The login page and question pages running, with backups |
| 6 | [Troubleshooting](6-Troubleshooting.md) | Fixes for the errors people run into most |

## How the pieces fit together

```mermaid
flowchart LR
    A["Your SOP PDFs<br/>(any folder, any names)"] -->|PyPDF.exe| B["Plain text<br/>PreProcessing/Raw_Text/*.txt"]
    B -->|TextCleanUp.exe| C["Passages<br/>Raw_Text/passages.jsonl"]
    C -->|EmbedScript.py| D["Vectors<br/>PreProcessing/Embeds/"]
    C --> F["LocalAIServ.exe + Ollama<br/>(answer server on your PC)"]
    D --> F
    C -->|Colab block 1.2| G["ColabTest.ipynb<br/>(Google GPU, testing)"]
    D -->|Colab block 1.2| G
    F --> H["Website<br/>XAMPP: Apache + PHP + MySQL"]
    H -->|ScheduleBackup.exe| I["Database backups<br/>SOPagent&lt;date&gt;.sql"]
```

Steps 2 and 3 prepare your documents. You only repeat them when your SOPs change. When someone asks a question on the website:

1. The question page (`Agent.php`, or `adminAgent.php` for admins) saves it in MySQL.
2. It sends the question to `LocalAIServ.exe`, the answer server running on the same PC.
3. The answer server queues the question (admins' questions go first), finds the passages closest in meaning to it, and has Qwen answer using only those passages.
4. The answer and its sources appear on the page and are saved in MySQL.

## Where things live

```
LLM/
├── GettingStarted/          ← this guide
├── config.json              ← your organisation's name, the last SOP folder you chose, and where backups go
├── requirements.txt         ← the Python packages EmbedScript.py needs
├── PreProcessing/
│   ├── PyPDF.exe            ← 1. copies the text out of your SOP PDFs
│   ├── TextCleanUp.exe      ← 2. cleans the text and splits it into passages
│   ├── Raw_Text/            ←    text files and passages.jsonl end up here
│   ├── Embeds/              ← 3. EmbedScript.py saves the vectors here
│   ├── PreProcessesV1/      ←    passages and vectors for SAHPRA's guidelines (demo, used by Colab block 1.1)
│   ├── Guidelines/          ←    SAHPRA's public guidelines (demo documents)
│   └── DownloadAll.exe      ←    only for downloading SAHPRA's public guidelines
├── EmbedScript.py           ← turns passages into vectors
├── LocalAIServ.exe          ← the answer server the website sends questions to
├── ScheduleBackup.exe       ← backs up the website's database (run it from Windows Task Scheduler)
├── ColabTest.ipynb          ← answers questions on Google Colab
└── FrontEnd/                ← the website (copied into XAMPP)
    └── passwords.txt        ← your MySQL login, for the website and ScheduleBackup.exe
```

Keep each `.exe` where it is shown. They find `config.json`, `Raw_Text`, `Embeds` and `passwords.txt` relative to their own folder, so a moved `.exe` can't find its files.

## Before you start: keep your SOPs and passwords private

Your SOP documents and the files made from them (`Raw_Text/`, `Embeds/`, `PreProcessesV1/`) contain your SOP text. Don't commit them to a public GitHub repository. `FrontEnd/passwords.txt` holds your MySQL password (step 5). The project's `.gitignore` already keeps it out of git; keep it that way. If you use Google Colab, your passages are processed on Google's servers; check that your organisation allows this.
