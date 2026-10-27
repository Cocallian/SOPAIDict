<?php
session_start();
$secrets = parse_ini_file(__DIR__ . "/passwords.txt");   // MySQL login (kept out of the code)
if($_SESSION["perm"] != "user")
    {
        header("Location: login.php");
        exit();
    }
$ASK_URL = "http://127.0.0.1:5000/ask";   // the answer server LocalAITest.py runs

$q = $_POST['user-input'] ?? '';
$a = "";

if(array_key_exists('logout', $_POST))
    {
        $_SESSION["perm"] = "";    // "" means not logged in, the same as dataconn.php uses
        $_SESSION["uname"] = "";
        header("Location: login.php");
        exit();
    }

        try
        {
            $conn = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"]);
            $conn->select_db("SOPAgent");
        }
        catch(mysqli_sql_exception)
        {
            $_SESSION["err"] = "Database connection error, contact your system administrator for assistance";
        }

if($q !== "")
    {
        set_time_limit(300);   // finding passages + Qwen's answer can take over a minute

        $promptId = null;

        // save the prompt with the current date and time before the model starts working on it
        if(isset($conn))
            {
                try
                    {
                        $uname = $_SESSION["uname"] ?? "";
                        $stmt = $conn->prepare("SELECT UserId FROM Users WHERE Username = ?");
                        $stmt->bind_param("s", $uname);
                        $stmt->execute();
                        $stmt->bind_result($userId);
                        $stmt->fetch();
                        $stmt->close();

                        $stmt = $conn->prepare("INSERT INTO Prompts (UserId, PromptText, CreatedAt) VALUES (?, ?, NOW())");
                        $stmt->bind_param("is", $userId, $q);
                        $stmt->execute();
                        $promptId = $conn->insert_id;
                        $stmt->close();
                    }
                catch(mysqli_sql_exception)
                    {
                        $_SESSION["err"] = "Your prompt could not be saved to the database";
                    }
            }

        // sends the question to the LocalAITest.py server and blocks here until its answer comes back
        $reply = @file_get_contents($ASK_URL, false, stream_context_create(["http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/x-www-form-urlencoded",
            "content"       => http_build_query(["question" => $q, "role" => "user"]),   // users wait behind any admin questions
            "timeout"       => 300,
            "ignore_errors" => true,
        ]]));
        $a = $reply === false ? "The answer server isn't running. Start LocalAITest.py and try again." : $reply;

        // the response is back: save it against the prompt, again with the current date and time
        if($promptId && $a !== null && trim($a) !== "")
            {
                try
                    {
                        $stmt = $conn->prepare("INSERT INTO Responses (PromptId, UserId, ResponseText, CreatedAt) VALUES (?, ?, ?, NOW())");
                        $stmt->bind_param("iis", $promptId, $userId, $a);
                        $stmt->execute();
                        $stmt->close();
                    }
                catch(mysqli_sql_exception)
                    {
                        $_SESSION["err"] = "The response could not be saved to the database";
                    }
            }
    }

    // Read the error, then clear it so it only shows once
    $err = $_SESSION["err"] ?? "";
    $_SESSION["err"] = "";


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>

        const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

        function CArr(arr, num)
        {
            const leng = arr.length;
            var safe = ((num % leng) + leng) % leng;
            return arr[safe];
        }
        function startSearch(e)
        {
            e.preventDefault();                       // hold the submit back for a moment
            if(!document.getElementById("user-input").value.trim()) return;
            throb();                                  // paints the first frame
            setTimeout(() => e.target.submit(), 100); // then post to PHP
        }

        async function throb()
        {
            const ta = document.getElementById("ta");
            ta.style.fontFamily = "Consolas, 'Courier New', monospace"; // equal-width characters while throbbing
            const ani1 = ["▖", "▘","▝" ,"▗", "▖", "▘","▝" ,"▗"]; 
            const ani2 = ["▞", "▚", "▞", "▚", "▞", "▚", "▞", "▚"];
            const ani3 = ["▙", "▛", "▜", "▟", "▙", "▛", "▜", "▟"];  
            const ani4 = ["◣", "◤", "◥", "◢", "◣", "◤", "◥", "◢"];
            const ani5 = ["◱", "◰", "◳", "◲", "◱", "◰", "◳", "◲"];
            const ani6 = ["◒", "◐", "◓", "◑", "◒", "◐", "◓", "◑"];
            const ani7 = ["__", "|", "▔", " |", "__", "|", "▔", " |"];
            const ani8 = ["◟", "◜", "◠", "◝", "◞", "◡", "◟", "◜", "◠", "◝", "◞", "◡"];
            const sizes = [ani1, ani2, ani3, ani4, ani5, ani6, ani7, ani8];
            const min = 0;
            const max = sizes.length - 1;
            // runs until the page reloads with the answer
            while(true)
            {
                var rng = Math.floor(Math.random() * (max - min + 1)) + min;
                const sel = rng;
                for(var i = 0; i < sizes[sel].length * 2; i++)
                {
                    ta.value = "\n Searching database and generating a response..." + CArr(sizes[sel], i);
                    await sleep(150);
                }
                

                

            }
        }

        // opens or closes the pop-out menu
        function togglePanel(open)
        {
            document.getElementById("panel").classList.toggle("open", open);
            document.getElementById("close-btn").classList.toggle("show", open);
        }
    </script>
    <style>
        * {
            box-sizing: border-box;
        }
        html, body {
            height: 100%;
            margin: 0;
        }
        html {
            /* Permalink - use to edit and share this gradient: https://colorzilla.com/gradient-editor/#7d7e7d+0,232323+73 */
            background: linear-gradient(to bottom,  #7d7e7d 0%,#232323 73%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
            background-attachment: fixed;
        }
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            gap: 20px;
        }
        #res {
            flex: 1;            
            min-height: 0;
            width: 70vw;
            padding: 20px;
            border-radius: 10px;
            background-color: dimgrey;
        }
        textarea {
            width: 100%;
            height: 100%;
            color: white;
            background-color: transparent;
            border: none;
            outline: none;
            resize: none;
            font: inherit;
            line-height: 1.5em;     /* fixed height so taller fallback glyphs can't push the line */
        }
        form {
            width: 60vw;
        }
        input[type="text"] {
            display: block;
            width: 100%;
            height: 7vh;
            padding: 10px;
            border-radius: 10px;
            border: none;
            outline: none;
            box-shadow: none;
            background-color: #312f2f;
            color: white;
            font: inherit;
        }

        /* pop-out menu, the same as the admin page's */
        #menu-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 44px;
            height: 44px;
            padding: 11px 9px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 10;
        }
        #menu-btn span {
            display: block;
            height: 4px;
            border-radius: 2px;
            background-color: white;
        }

        #close-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 44px;
            height: 44px;
            display: none;
            background: transparent;
            border: none;
            color: white;
            font-size: 36px;
            line-height: 1;
            cursor: pointer;
            z-index: 30;
        }
        #close-btn.show {
            display: block;
        }

        #panel {
            position: fixed;
            top: 0;
            left: 0;
            width: 92vw;
            height: 100vh;
            padding: 80px 40px 40px;
            overflow-y: auto;
            background-color: #232323;
            color: white;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.6);
            transform: translateX(-100%);
            visibility: hidden;
            transition: transform 0.35s ease, visibility 0s linear 0.35s;
            z-index: 20;
        }
        #panel.open {
            transform: translateX(0);
            visibility: visible;
            transition: transform 0.35s ease, visibility 0s;
        }
        #panel h2 {
            margin: 0 0 20px;
        }
        .panel-box {
            padding: 20px;
            border-radius: 10px;
            background-color: #312f2f;
        }
        .panel-box button[type="submit"] {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: dimgrey;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        /* popup for errors (e.g. database connection) */
        #errPopup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #d32f2f;
            color: #fff;
            padding: 14px 44px 14px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }
        #errPopup.hidden {
            display: none;
        }
        #errPopup button {
            position: absolute;
            top: 6px;
            right: 10px;
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOP Agent</title>
</head>


<body>

<?php if ($err !== ""): ?>
    <div id = "errPopup">
        <?php echo htmlspecialchars($err); ?>
        <button type = "button" id = "closePopup" aria-label = "Close">&times;</button>
    </div>
<?php endif; ?>

<div id="res">
    <textarea id = "ta" readonly>Welcome to the SOP RAG agent, type below to start querying the agent<?php echo htmlspecialchars($a); ?></textarea>
</div>
    <form method = "post" onsubmit = "startSearch(event)">
        <input type="text" id="user-input" name="user-input" autofocus>
        <button type="submit" tabindex="-1" hidden>Submit</button>

    </form>

    <button id="menu-btn" type="button" aria-label="Open menu" onclick="togglePanel(true)">
        <span></span><span></span><span></span>
    </button>
    <button id="close-btn" type="button" aria-label="Close menu" onclick="togglePanel(false)">&times;</button>

    <div id="panel">
        <h2>Menu</h2>
        <div class="panel-box">
            <form method="post" style="width: auto;">
                <button type="submit" name="logout" value="logout">Log out</button>
            </form>
        </div>
    </div>
<script>
    // Close the popup on click, or automatically after 5 seconds
    const popup = document.getElementById("errPopup");
    if (popup) {
        document.getElementById("closePopup").addEventListener("click", function () {
            popup.classList.add("hidden");
        });
        setTimeout(function () {
            popup.classList.add("hidden");
        }, 5000);
    }
</script>
</body>
</html>
