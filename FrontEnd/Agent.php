<?php
session_start();
$secrets = parse_ini_file(__DIR__ . "/passwords.txt");   // MySQL login (kept out of the code)
if($_SESSION["perm"] != "user")
    {
        header("Location: login.php");
        exit();
    }
require __DIR__ . "/activeusers.php";
$ASK_URL = "http://127.0.0.1:5000/ask";   // the answer server LocalAITest.py runs
$_SESSION["LastSeen"] = time();
$q = $_POST['user-input'] ?? '';
$a = "";


function LogOut($conn, $userN)
    {
        $_SESSION["perm"] = "";    // "" means not logged in, the same as dataconn.php uses
        $_SESSION["uname"] = "";
        // validation.js sends reason=inactive when the 15 minutes ran out, a click on Log out sends nothing
        writeLog($conn, $userN, "user", ($_POST["reason"] ?? "") === "inactive" ? "inactive" : "logout");
        try{
            $stmt = $conn->prepare("DELETE FROM ActiveUsers WHERE Username = ?");
                $stmt->bind_param("s", $userN);
                $stmt->execute();
                $stmt->close();
                header("Location: login.php");
                exit();
        }catch(mysqli_sql_exception $e)
        {
            header("Location: inactive.php");
            exit();
        }
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

// Needs the database connection above, so it can't sit with the function
if(array_key_exists('logout', $_POST))
    {
        $userN = $_SESSION["uname"] ?? "";
        if(isset($conn)) { LogOut($conn, $userN); }
        $_SESSION["perm"] = "";    // no database to tidy up, just log out
        $_SESSION["uname"] = "";
        header("Location: login.php");
        exit();
    }

// validation.js asks every so often whether this session is still in use, it only needs LastSeen refreshed
if(array_key_exists('touch', $_POST))
    {
        if(isset($conn)) { touchActiveUser($conn, $_SESSION["uname"]); }
        exit();
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
            "content"       => http_build_query(["question" => $q, "role" => "user", "ip" => $_SERVER["REMOTE_ADDR"], "user" => $_SESSION["uname"] ?? ""]),   // users wait behind any admin questions
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

                // LocalAIServ.py ends every answer with "Sources:" and one "[n] document number: title, p. x" line per passage,
                // what is kept is "document number: title", without the [n] and the page numbers
                $titles = [];
                if(preg_match('/\n\nSources:\n(.+)$/s', $a, $block))
                    {
                        foreach(explode("\n", trim($block[1])) as $line)
                            {
                                if(preg_match('/^\[\d+\] (.+), pp?\. [\d-]+$/', trim($line), $parts)) { $titles[] = $parts[1]; }
                            }
                    }

                try
                    {
                        $stmt = $conn->prepare("INSERT INTO SourcedDocuments (PromptId, UserId, Title, SourcedAt) VALUES (?, ?, ?, NOW())");
                        foreach(array_unique($titles) as $title)
                            {
                                $stmt->bind_param("iis", $promptId, $userId, $title);
                                $stmt->execute();
                            }
                        $stmt->close();
                    }
                catch(mysqli_sql_exception)
                    {
                        $_SESSION["err"] = "The source documents could not be saved to the database";
                    }
            }
    }

    // This user's questions and answers, newest first, for the Prompt history table in the menu
    $history = [];
    if(isset($conn))
        {
            try
                {
                    $uname = $_SESSION["uname"] ?? "";
                    $stmt = $conn->prepare("SELECT p.PromptText, r.ResponseText, p.CreatedAt
                                            FROM Prompts p
                                            JOIN Users u ON u.UserId = p.UserId
                                            LEFT JOIN Responses r ON r.PromptId = p.PromptId
                                            WHERE u.Username = ?
                                            ORDER BY p.CreatedAt DESC, p.PromptId DESC");
                    $stmt->bind_param("s", $uname);
                    $stmt->execute();
                    $stmt->bind_result($question, $answer, $askedAt);
                    while($stmt->fetch())
                        {
                            $history[] = ["question" => $question, "answer" => $answer, "askedAt" => $askedAt];
                        }
                    $stmt->close();
                }
            catch(mysqli_sql_exception)
                {
                    $_SESSION["err"] = "Your prompt history could not be loaded";
                }
        }

        if(isset($conn))
            {
                touchActiveUser($conn, $_SESSION["uname"]);
            }

    // Read the error, then clear it so it only shows once
    $err = $_SESSION["err"] ?? "";
    $_SESSION["err"] = "";


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="idle.js?v=<?php echo filemtime(__DIR__ . "/idle.js"); ?>" defer></script>
    <script src="validation.js?v=<?php echo filemtime(__DIR__ . "/validation.js"); ?>" defer></script>
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
        .panel-box + .panel-box {
            margin-top: 20px;
        }
        .panel-box button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: dimgrey;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        /* Prompt history table, hidden until its button is pressed */
        #history {
            display: none;
            margin-top: 15px;
            max-height: 60vh;
            overflow-y: auto;
        }
        #history.show {
            display: block;
        }
        #history table {
            width: 100%;
            border-collapse: collapse;
        }
        #history th, #history td {
            padding: 10px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid dimgrey;
        }
        #history th {
            position: sticky;   /* headings stay visible while scrolling */
            top: 0;
            background-color: #312f2f;
        }
        #history td {
            white-space: pre-wrap;   /* keeps the answers' line breaks */
        }
        #history td.when {
            white-space: nowrap;
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

        /* Transparent gray layer over the whole page, hidden until validation.js shows it */
        #gray-out {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(35, 35, 35, 0.7);
            z-index: 2000;
        }
        /* The box sits in the middle of the screen, on top of the gray layer */
        #inactiveBox {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 360px;
            max-width: calc(100% - 32px);
            padding: 45px;
            text-align: center;
            border-radius: 10px;
            background-color: #312f2f;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
            color: white;
            z-index: 2001;
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
    <textarea id = "ta" readonly><?php echo $a === "" ? "Welcome to the SOP RAG agent, type below to start querying the agent" : htmlspecialchars($a); ?></textarea>
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
            <button type="button" onclick="toggleHistory()">Prompt history</button>
            <div id="history">
                <?php if (count($history) === 0): ?>
                    <p>You haven't asked any questions yet.</p>
                <?php else: ?>
                    <table>
                        <tr><th>Question</th><th>Answer</th><th>Date and time</th></tr>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["question"]); ?></td>
                                <td><?php echo htmlspecialchars($row["answer"] ?? "No answer was saved"); ?></td>
                                <td class="when"><?php echo date("Y-m-d H:i", strtotime($row["askedAt"])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <div class="panel-box">
            <form method="post" style="width: auto;">
                <button type="submit" name="logout" value="logout">Log out</button>
            </form>
        </div>
    </div>

    <div id = "gray-out">
        <div id = "inactiveBox">
            You have been detected as inactive, you will be logged out in : <span id="inactiveCountdown"></span>
                </br>
                Move your mouse or click/tap anywhere to reactivate the page.
        </div>
    </div>
</body>
</html>
