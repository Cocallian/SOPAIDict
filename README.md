

<h2><b>WANT TO TEST?</b></h2><br>
Simply click on the ColabTest.ipynb file and follow the instructions (entirely free)<br>
BEWARE: this does download files from this repository onto your drive, they are in PreProcessingSAHPRAGuidelines/PreProcessV1 folder (entirely just sets of numbers and a manifest)
<br>
TO NOTE: 
To enable connection to your own database and/or a google colab notebook place your database in a self-created 'passwords.txt' structured like this:

[database]
db_host = "127.0.0.1"
db_user = "root"
db_pass = "YOUR_PASSWORD_HERE"

[seed_accounts]
; dataconn.php creates these accounts if they don't exist yet
admin_user = "admin"
admin_pass = "123"
user_user  = "user"
user_pass  = "123"

[answers]
; Leave colab_url empty to answer with LocalAITest.py on this PC.
; To use the Colab notebook instead, paste the address its last cell prints (it ends in /ask).
; The address changes every time the notebook is restarted. If Colab can't be reached, the local model answers.
colab_url = "URL_FROM_COLAB_NOTEBOOK_LAST_CELL"

<h3>PROGRESS:</h3>

<ul>
<li>RAG pipeline - 100% </li>
<li>Agent optimization - 90%</li>
<li>Front end - 90% (appealing design only)</li>
<li>Backend database and sign-in - 100%</li>
<ul>


<h3>YET TO DO:</h3>

<ul>
<li>Connect colab and a front end php file</li>
<li>Agent optimization (possible python server?)</li>
<li>User priority queue implementation</li>
<ul>