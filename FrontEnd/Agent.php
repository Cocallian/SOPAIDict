<?php
// The user page: ask the agent questions and look back at the prompt history.
// Each part of the work is a function in one of the files below, this file only runs them in order.
session_start();

require __DIR__ . "/common/config.php";
require __DIR__ . "/common/session.php";
require __DIR__ . "/common/csrf.php";
require __DIR__ . "/common/database.php";
require __DIR__ . "/common/popups.php";
require __DIR__ . "/common/assets.php";
require __DIR__ . "/common/inactivitybox.php";
require __DIR__ . "/common/activeusers.php";
require __DIR__ . "/common/logouthandling.php";
require __DIR__ . "/common/feedback.php";
require __DIR__ . "/common/questionhandling.php";
require __DIR__ . "/Agent/historydata.php";

$userN = requireRole("user");
verifyCsrf();
$secrets = loadSecrets();
$q = $_POST['user-input'] ?? '';
$a = "";
$feedbackId = null;   // set when this answer got its feedback row

$conn = connectDatabase($secrets, "Database connection error, contact your system administrator for assistance");

handleTouchRequest($conn, $userN);
handleLogoutRequest($conn, $userN, "user");
handleFeedbackSave($conn);

if($q !== "") { [$a, $feedbackId] = answerQuestion($conn, $q, "user"); }

$history = loadHistory($conn);

$err = takeError();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo csrfMeta(); ?>
    <?php scriptTag("idle.js"); scriptTag("js/common.js"); scriptTag("js/menu.js"); scriptTag("js/inactivity.js"); scriptTag("js/feedback.js"); styleTag("common/feedback.css"); ?>
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

<?php renderPopups($err); ?>

<div id="out"<?php echo $feedbackId ? ' class="has-feedback"' : ""; ?>>
<div id="res">
    <textarea id = "ta" readonly><?php echo $a === "" ? "Welcome to the SOP RAG agent, type below to start querying the agent" : htmlspecialchars($a); ?></textarea>
</div>
<?php renderFeedback($feedbackId); ?>
</div>
    <form method = "post" onsubmit = "startSearch(event)"><?php echo csrfField(); ?>
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
            <form method="post" style="width: auto;"><?php echo csrfField(); ?>
                <button type="submit" name="logout" value="logout">Log out</button>
            </form>
        </div>
    </div>

<?php renderInactivityBox(); ?>
</body>
</html>
