<?php
session_start();
if(($_SESSION["perm"] ?? "") != "admin" )
    {
        header("Location: login.php");
        exit();
    }else{
        $userN = $_SESSION["uname"];
        $sesID = session_id();
    }

require __DIR__ . "/activeusers.php";
require_once __DIR__ . "/frontendconfig.php";
$secrets = loadSecrets();   // MySQL login and settings from FrontEndConfig.json (kept out of the code)
try
    {
        $conn = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"]);
        $conn->select_db("SOPAgent");
    }
catch(mysqli_sql_exception) {}

// Being on this page counts as being active: refresh this session's LastSeen, or go to Inactive.php when its row is already gone
if(isset($conn)) { touchActiveUser($conn, $userN); }
if(array_key_exists('touch', $_POST)) { exit(); }   // validation.js only wanted LastSeen refreshed, nothing to draw

// The Log out request validation.js sends when the inactivity time runs out here
if(array_key_exists('logout', $_POST))
    {
        if(isset($conn)) { logOutSession($conn, $userN, "admin", $_POST["reason"] ?? ""); }
        $_SESSION["perm"] = "";    // no database to tidy up, just log out
        $_SESSION["uname"] = "";
        header("Location: login.php");
        exit();
    }

$SETTINGS_URL = "http://127.0.0.1:5000/settings";   // the Flask server in LocalAIServ.py (settingsapi.py)

// What each setting accepts. The page draws its hints and validation.js checks the form from this, and the save below
// checks it again, so the limits are only written here. kind: int, float, text, path or model.
$fields = [
    "org_name"        => ["label" => "Organisation name",  "kind" => "text",  "max" => 200],
    "backup_path"     => ["label" => "Backup folder",      "kind" => "path",  "max" => 260, "optional" => true],
    "context_window"  => ["label" => "Context window",     "kind" => "int",   "min" => 256, "max" => 32768],
    "temperature"     => ["label" => "Temperature",        "kind" => "float", "min" => 0,   "max" => 2],
    "length"          => ["label" => "Answer length",      "kind" => "int",   "min" => 16,  "max" => 4096],
    "sources"         => ["label" => "Sources",            "kind" => "int",   "min" => 1,   "max" => 10],
    "running_threads" => ["label" => "Parallel questions", "kind" => "int",   "min" => 1,   "max" => 8],
    "model_name"      => ["label" => "Model name",         "kind" => "model", "max" => 100],
    "embed_model"     => ["label" => "Embedding model",    "kind" => "model", "max" => 100],
];

// The problem with a value, or "" when it is fine
function settingProblem($rule, $value)
    {
        $label = $rule["label"];
        if($value === "") { return ($rule["optional"] ?? false) ? "" : $label . " can't be empty."; }
        if(mb_strlen($value) > $rule["max"] && in_array($rule["kind"], ["text", "path", "model"])) { return $label . " can be at most " . $rule["max"] . " characters."; }
        switch($rule["kind"])
            {
                case "int":
                    if(!ctype_digit($value)) { return $label . " must be a whole number."; }
                    if((int)$value < $rule["min"] || (int)$value > $rule["max"]) { return $label . " must be between " . $rule["min"] . " and " . $rule["max"] . "."; }
                    break;
                case "float":
                    if(!preg_match('/^\d+(\.\d+)?$/', $value)) { return $label . " must be a number, like 0.1."; }
                    if((float)$value < $rule["min"] || (float)$value > $rule["max"]) { return $label . " must be between " . $rule["min"] . " and " . $rule["max"] . "."; }
                    break;
                case "path":
                    if(!preg_match('/^([A-Za-z]:[\\\\\/]|\\\\\\\\)[^<>"|?*]*$/', $value)) { return $label . " must be a full Windows path, like D:\\Backups."; }
                    break;
                case "model":
                    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]*$/', $value)) { return $label . " can only have letters, numbers and . _ : / -"; }
                    break;
                default:
                    if(preg_match('/[\x00-\x1F]/', $value)) { return $label . " can't have control characters."; }
            }
        return "";
    }

// Save: validation.js sends the form with fetch, this answers with a plain message (200 saved, otherwise the problem)
if(array_key_exists('save-settings', $_POST))
    {
        $send = [];
        foreach($fields as $name => $rule)
            {
                $value = trim((string)($_POST[$name] ?? ""));
                $problem = settingProblem($rule, $value);
                if($problem !== "") { http_response_code(400); header("Content-Type: text/plain; charset=utf-8"); echo $problem; exit(); }
                $send[$name] = $value;
            }

        session_write_close();
        $reply = @file_get_contents($SETTINGS_URL, false, stream_context_create(["http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/x-www-form-urlencoded",
            "content"       => http_build_query($send),
            "timeout"       => 15,
            "ignore_errors" => true,
        ]]));
        $status = 0;
        foreach($http_response_header ?? [] as $line)
            {
                if(preg_match('/^HTTP\/\S+ (\d+)/', $line, $m)) { $status = (int)$m[1]; }
            }

        header("Content-Type: text/plain; charset=utf-8");
        if($reply === false) { http_response_code(503); echo "The answer server isn't running. Start LocalAIServ.py and try again."; }
        elseif($status !== 200) { http_response_code($status >= 400 ? $status : 500); echo substr(strip_tags(trim($reply)), 0, 300); }
        else { echo "Settings saved. Stop and start the server on the dashboard for them to take effect."; }
        exit();
    }

// The current values, asked from the server when the page loads
$current = null;
$reply = @file_get_contents($SETTINGS_URL, false, stream_context_create(["http" => ["timeout" => 5, "ignore_errors" => true]]));
if($reply !== false) { $current = json_decode($reply, true); }
if(!is_array($current)) { $current = null; }
$serverDown = $current === null;

$err = $_SESSION["err"] ?? "";
$_SESSION["err"] = "";

// A short line under each input, from the same limits
function settingHint($rule)
    {
        switch($rule["kind"])
            {
                case "int":   return "Whole number, " . $rule["min"] . " to " . $rule["max"];
                case "float": return "Number, " . $rule["min"] . " to " . $rule["max"];
                case "path":  return "Full Windows path, like D:\\Backups. Left empty it is not changed.";
                case "model": return "Letters, numbers and . _ : / - only";
                default:      return "Up to " . $rule["max"] . " characters";
            }
    }

// The explanation card: what each setting does and what can go wrong when it is changed
$help = [
    "org_name"        => ["Is named in the instructions the model is given, so it answers as that organisation.",
                          "A wrong or very long name makes answers refer to the wrong organisation and uses up room in the context window."],
    "backup_path"     => ["The folder the scheduled backups are saved to.",
                          "If the folder doesn't exist, can't be written to, or is on a drive that is offline, the backups fail."],
    "context_window"  => ["How much text, in tokens, the model can read for one question: the sources and the question together.",
                          "Higher fits more sources but uses a lot more memory (it is multiplied by the parallel questions) and slows answers. Too low cuts the sources off, so answers ignore them. Raise it when you raise the sources."],
    "temperature"     => ["How freely the model words its answer. Low sticks closely to the sources.",
                          "Higher values make answers vary and make it more likely to state things the sources don't say. For SOP answers keep it low, around 0.1 to 0.3."],
    "length"          => ["The longest answer, in tokens, the model may write.",
                          "Longer limits give slower, wordier answers. Too short cuts an answer off in the middle of a sentence."],
    "sources"         => ["How many passages from the documents are given to the model with the question.",
                          "More sources give more to work from but need a bigger context window, are slower, and can bury the useful passage. Fewer may miss the one that matters."],
    "running_threads" => ["How many questions are answered at the same time.",
                          "More means shorter waits when many people ask at once, but each running question uses memory and shares the processor, so each one is slower. A question from an admin goes first in the line but doesn't interrupt one already running."],
    "model_name"      => ["The Ollama model that writes the answers.",
                          "It must already be downloaded in Ollama (ollama pull), or every question fails. Bigger models answer better but need more memory and are slower."],
    "embed_model"     => ["The model that turns the documents and each question into numbers so the right passages can be found.",
                          "It must be the same one EmbedScript.py used. Changing it without running EmbedScript.py again makes the search return the wrong passages. It also has to be downloaded on this PC already."],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOP Agent</title>
    <script src="idle.js?v=<?php echo filemtime(__DIR__ . "/idle.js"); ?>" defer></script>
    <script src="validation.js?v=<?php echo filemtime(__DIR__ . "/validation.js"); ?>" defer></script>
    <style>
        * {
            box-sizing: border-box;
        }
        html, body {
            min-height: 100%;
            margin: 0;
        }
        html {
            background: linear-gradient(to bottom,  #7d7e7d 0%,#232323 73%);
            background-attachment: fixed;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px 16px;
        }
        .settings-page {
            width: 560px;
            max-width: 100%;
            margin: 4% auto 20px;
            padding: 45px;
            border-radius: 10px;
            background-color: #312f2f;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
            color: white;
        }
        .settings-page + .settings-page {
            margin-top: 0;
        }
        @media (max-width: 420px) {
            .settings-page {
                padding: 30px 20px;
            }
        }
        .settings-page h1 {
            margin: 0 0 10px;
            font-size: 24px;
            font-weight: normal;
        }
        .settings-page h2 {
            margin: 30px 0 12px;
            font-size: 16px;
            font-weight: normal;
            color: #b3b3b3;
        }
        .settings-page .note {
            margin: 0;
            color: #b3b3b3;
            font-size: 14px;
            line-height: 1.5;
        }
        .setting-block {
            padding: 12px 14px;
            margin-bottom: 10px;
            border: 2px solid transparent;
            border-radius: 8px;
            background-color: #232323;
        }
        .setting-block.invalid {
            border-color: #d32f2f;
        }
        .setting-block label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .settings-page input[type="text"] {
            display: block;
            width: 100%;
            height: 44px;
            padding: 8px 10px;
            border: 2px solid transparent;
            border-radius: 8px;
            outline: 0;
            background-color: #312f2f;
            color: white;
            font: inherit;
            font-size: 14px;
        }
        .settings-page input[type="text"]:focus {
            border-color: dimgrey;
        }
        .settings-page input[type="text"]:disabled {
            opacity: 0.5;
        }
        .setting-block .hint {
            margin: 8px 0 0;
            color: #b3b3b3;
            font-size: 12px;
        }
        .save-btn {
            display: block;
            width: 100%;
            margin-top: 25px;
            padding: 15px;
            border: 0;
            border-radius: 8px;
            background-color: dimgrey;
            color: white;
            font: inherit;
            font-size: 14px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .save-btn:hover,
        .save-btn:focus {
            background-color: #7d7e7d;
        }
        .save-btn:disabled {
            opacity: 0.5;
            cursor: default;
        }
        .settings-status {
            margin-top: 18px;
            padding: 14px;
            border-radius: 8px;
            background-color: #232323;
            color: white;
            font-size: 14px;
            text-align: center;
            line-height: 1.5;
        }
        .settings-status[hidden] {
            display: none;
        }
        .settings-status.done {
            background-color: #2e7d32;
        }
        .settings-status.failed {
            background-color: #d32f2f;
        }
        /* The explanation card */
        .help-item {
            padding: 12px 14px;
            margin-bottom: 10px;
            border-radius: 8px;
            background-color: #232323;
            font-size: 14px;
            line-height: 1.5;
        }
        .help-item h3 {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: normal;
        }
        .help-item p {
            margin: 0 0 6px;
        }
        .help-item p:last-child {
            margin-bottom: 0;
        }
        .help-item .drawback {
            color: #ef9a9a;
        }
        .help-item .drawback b,
        .help-item .effect b {
            font-weight: normal;
            color: #b3b3b3;
        }
        #errPopup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            max-width: calc(100% - 32px);
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
        .field-error {
            margin: 8px 0 0;
            color: #ef9a9a;
            font-size: 13px;
            line-height: 1.4;
        }
        .field-error[hidden] {
            display: none;
        }
        .back-link {
            display: block;
            margin-top: 18px;
            text-align: center;
            color: #b3b3b3;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php if ($err !== ""): ?>
        <div id = "errPopup">
            <?php echo htmlspecialchars($err); ?>
            <button type = "button" id = "closePopup" aria-label = "Close">&times;</button>
        </div>
    <?php endif; ?>

    <div class="settings-page">
        <h1>Settings</h1>
        <p class="note">These are kept in config.json. A saved change takes effect when the server is stopped and started again from the dashboard.</p>

        <?php if ($serverDown): ?>
            <div class="settings-status failed">The answer server isn't running, so the settings can't be loaded. Start LocalAIServ.py from the dashboard and reload this page.</div>
        <?php endif; ?>

        <form method="post" id="settings-form" novalidate>
            <h2>Settings</h2>
            <?php foreach ($fields as $name => $rule): ?>
                <div class="setting-block" data-block="<?php echo $name; ?>">
                    <label for="set-<?php echo $name; ?>"><?php echo $rule["label"]; ?></label>
                    <input type="text" id="set-<?php echo $name; ?>" name="<?php echo $name; ?>" autocomplete="off"
                           value="<?php echo htmlspecialchars($current[$name] ?? ""); ?>"
                           data-label="<?php echo $rule["label"]; ?>" data-kind="<?php echo $rule["kind"]; ?>"
                           data-min="<?php echo $rule["min"] ?? ""; ?>" data-max="<?php echo $rule["max"]; ?>"
                           data-optional="<?php echo ($rule["optional"] ?? false) ? "1" : ""; ?>"
                           <?php echo $serverDown ? "disabled" : ""; ?>>
                    <p class="hint"><?php echo htmlspecialchars(settingHint($rule)); ?></p>
                    <p class="field-error" data-error="<?php echo $name; ?>" hidden></p>
                </div>
            <?php endforeach; ?>

            <button type="submit" name="save-settings" value="save-settings" class="save-btn" <?php echo $serverDown ? "disabled" : ""; ?>>Save settings</button>
        </form>

        <div id="settings-status" class="settings-status" aria-live="polite" hidden></div>

        <a class="back-link" href="adminAgent.php">Back to the dashboard</a>
    </div>

    <div class="settings-page">
        <h1>What the settings do</h1>
        <p class="note">Read these before changing a value. Each one lists what it controls and what can go wrong.</p>
        <h2>Settings guide</h2>
        <?php foreach ($fields as $name => $rule): ?>
            <div class="help-item">
                <h3><?php echo $rule["label"]; ?></h3>
                <p class="effect"><b>What it does:</b> <?php echo htmlspecialchars($help[$name][0]); ?></p>
                <p class="drawback"><b>Drawbacks:</b> <?php echo htmlspecialchars($help[$name][1]); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div id = "gray-out">
        <div id = "inactiveBox" data-timeout-minutes="<?php echo inactivityMinutes(); ?>">
            You have been detected as inactive, you will be logged out in : <span id="inactiveCountdown"></span>
                </br>
                Move your mouse or click/tap anywhere to reactivate the page.
        </div>
    </div>
</body>
</html>
