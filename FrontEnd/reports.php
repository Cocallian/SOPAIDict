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
$secrets = parse_ini_file(__DIR__ . "/passwords.txt");   // MySQL login (kept out of the code)
try
    {
        $conn = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"]);
        $conn->select_db("SOPAgent");
    }
catch(mysqli_sql_exception) {}

// Being on this page counts as being active: refresh this session's LastSeen, or go to Inactive.php when its row is already gone
if(isset($conn)) { touchActiveUser($conn, $userN); }
if(array_key_exists('touch', $_POST)) { exit(); }   // validation.js only wanted LastSeen refreshed, nothing to draw

// The Log out request validation.js sends when the 15 minutes of inactivity run out here
if(array_key_exists('logout', $_POST))
    {
        if(isset($conn)) { logOutSession($conn, $userN, "admin", $_POST["reason"] ?? ""); }
        $_SESSION["perm"] = "";    // no database to tidy up, just log out
        $_SESSION["uname"] = "";
        header("Location: login.php");
        exit();
    }

$REPORT_URL = "http://127.0.0.1:5000/report";   // the Flask server in LocalAIServ.py makes the report and sends the file back

if(array_key_exists('generate', $_POST))
    {
        set_time_limit(330);   // the server waits up to 5 minutes for the report
        $post = http_build_query([
            "date_from" => $_POST["report_date_from"] ?? "",
            "date_to"   => $_POST["report_date_to"] ?? "",
            "week"     => $_POST["report_week"] ?? "",
            "month"    => $_POST["report_month"] ?? "",
            "year"     => $_POST["report_year"] ?? "",
            "sections" => implode(",", array_map("strval", (array)($_POST["sections"] ?? []))),
        ]);

        session_write_close();   // don't hold the session for the whole wait, the admin's other pages must keep loading
        $body = @file_get_contents($REPORT_URL, false, stream_context_create(["http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/x-www-form-urlencoded",
            "content"       => $post,
            "timeout"       => 330,
            "ignore_errors" => true,
        ]]));

        $status = 0;
        $filename = "report.pdf";
        $type = "application/octet-stream";
        foreach($http_response_header ?? [] as $line)
            {
                if(preg_match('/^HTTP\/\S+ (\d+)/', $line, $m)) { $status = (int)$m[1]; }
                if(preg_match('/^Content-Disposition:.*filename="?([^";\r\n]+)"?/i', $line, $m)) { $filename = basename($m[1]); }
                if(preg_match('/^Content-Type:\s*([^;\r\n]+)/i', $line, $m)) { $type = trim($m[1]); }
            }

        if($body !== false && $status === 200)
            {
                header("Content-Type: " . $type);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header("Content-Length: " . strlen($body));
                echo $body;
                exit();
            }

        $message = $body === false ? "The answer server isn't running. Start LocalAIServ.py and try again."
                                   : substr(strip_tags(trim($body)), 0, 300);

        // validation.js sends the form with fetch and shows the message under the form, so it gets a plain error instead of a redirect
        if(($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "fetch")
            {
                http_response_code($status >= 400 ? $status : 503);
                header("Content-Type: text/plain; charset=utf-8");
                echo $message;
                exit();
            }

        session_start();
        $_SESSION["err"] = $message;
        header("Location: reports.php");
        exit();
    }

$err = $_SESSION["err"] ?? "";
$_SESSION["err"] = "";

// Options for the week and month selects, worked out from today's date
$today = date("Y-m-d");
$thisMonday = strtotime("monday this week");
$weeks = [];
for($i = 0; $i < 12; $i++)
    {
        $start = strtotime("-$i week", $thisMonday);
        $label = $i === 0 ? "This week" : ($i === 1 ? "Last week" : "$i weeks ago");
        $weeks[] = ["value" => date("Y-m-d", $start),
                    "label" => $label . " (" . date("j M", $start) . " - " . date("j M", strtotime("+6 day", $start)) . ")"];
    }
// The oldest entry in the tables a report is built from. Nothing before it and nothing after today can be picked.
$earliest = $today;
if(isset($conn))
    {
        try
            {
                $oldest = $conn->query("SELECT MIN(t) FROM (SELECT MIN(CreatedAt) AS t FROM Prompts UNION ALL SELECT MIN(CreatedAt) FROM Responses
                                        UNION ALL SELECT MIN(SourcedAt) FROM SourcedDocuments UNION ALL SELECT MIN(LoggedAt) FROM Logs) AS oldest")->fetch_row()[0];
                if($oldest) { $earliest = substr($oldest, 0, 10); }
            }
        catch(mysqli_sql_exception) {}
    }
$weeks = array_values(array_filter($weeks, fn($week) => date("Y-m-d", strtotime("+6 day", strtotime($week["value"]))) >= $earliest));   // weeks that can hold data

$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$thisYear = (int)date("Y");
$firstYear = max($thisYear - 4, (int)substr($earliest, 0, 4));

$sections = [
    "total"    => "Total responses",
    "busiest"  => "Busiest days",
    "sourced"  => "Most sourced",
    "sessions" => "Longest sessions",
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
        .report-page {
            width: 560px;
            max-width: 100%;
            margin: 4% auto 40px;
            padding: 45px;
            border-radius: 10px;
            background-color: #312f2f;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
            color: white;
        }
        @media (max-width: 420px) {
            .report-page {
                padding: 30px 20px;
            }
        }
        .report-page h1 {
            margin: 0 0 10px;
            font-size: 24px;
            font-weight: normal;
        }
        .report-page h2 {
            margin: 30px 0 12px;
            font-size: 16px;
            font-weight: normal;
            color: #b3b3b3;
        }
        .report-page .note {
            margin: 0;
            color: #b3b3b3;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Date / week / month: each one can be set or left on "-", a set one is active */
        .period-block {
            padding: 12px 14px;
            margin-bottom: 10px;
            border: 2px solid transparent;
            border-radius: 8px;
            background-color: #232323;
        }
        .period-block.active {
            border-color: #2e7d32;
        }
        .period-head {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .period-head .tag {
            font-size: 12px;
            color: #b3b3b3;
        }
        .period-head .tag::before {
            content: "Not set";
        }
        .period-block.active .period-head .tag {
            color: #a5d6a7;
        }
        .period-block.active .period-head .tag::before {
            content: "Active";
        }
        .period-panel {
            display: flex;
            gap: 10px;
        }
        .period-block input[type="date"],
        .period-block select {
            background-color: #312f2f;
        }
        .period-block select:disabled {
            opacity: 0.5;
        }
        .report-page input[type="date"],
        .report-page select {
            flex: 1;
            height: 44px;
            padding: 8px 10px;
            border: 2px solid transparent;
            border-radius: 8px;
            outline: 0;
            background-color: #232323;
            color: white;
            font: inherit;
            font-size: 14px;
            color-scheme: dark;
        }
        .report-page input[type="date"]:focus,
        .report-page select:focus {
            border-color: dimgrey;
        }
        .range-label {
            flex: 1;
            font-size: 12px;
            color: #b3b3b3;
        }
        .range-label input[type="date"] {
            display: block;
            width: 100%;
            margin-top: 4px;
        }
        .today-btn {
            height: 40px;
            padding: 0 16px;
            border: 0;
            border-radius: 8px;
            background-color: dimgrey;
            color: white;
            font: inherit;
            font-size: 14px;
            cursor: pointer;
        }

        /* The four on/off switches: red when off, green when on */
        .option-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            margin-bottom: 8px;
            border-radius: 8px;
            background-color: #232323;
        }
        .switch {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .switch input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .switch .track {
            position: relative;
            width: 52px;
            height: 28px;
            border-radius: 14px;
            background-color: #d32f2f;
            transition: background-color 0.2s ease;
        }
        .switch .track::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background-color: white;
            transition: transform 0.2s ease;
        }
        .switch input:checked + .track {
            background-color: #2e7d32;
        }
        .switch input:checked + .track::after {
            transform: translateX(24px);
        }
        .switch input:focus-visible + .track {
            outline: 2px solid white;
            outline-offset: 2px;
        }
        .switch .state {
            width: 28px;
            font-size: 13px;
            color: #ef9a9a;
        }
        .switch .state::before {
            content: "Off";
        }
        .switch input:checked ~ .state {
            color: #a5d6a7;
        }
        .switch input:checked ~ .state::before {
            content: "On";
        }

        .generate-btn {
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
        .generate-btn:hover,
        .generate-btn:focus {
            background-color: #7d7e7d;
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
        .generate-btn:disabled {
            opacity: 0.5;
            cursor: default;
        }

        /* Under the form: the throbber while the report is made, then the result */
        .report-status {
            margin-top: 18px;
            padding: 14px;
            border-radius: 8px;
            background-color: #232323;
            color: white;
            font-size: 14px;
            text-align: center;
            line-height: 1.5;
        }
        .report-status[hidden] {
            display: none;
        }
        .report-status.done {
            background-color: #2e7d32;
        }
        .report-status.failed {
            background-color: #d32f2f;
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
        .period-block.invalid {
            border-color: #d32f2f;
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

    <div class="report-page">
        <h1>Generate report</h1>
        <p class="note">Choose the period and what the report should include. When you press Generate, you will be asked where to save the report.</p>

        <form method="post" id="report-form" novalidate data-earliest="<?php echo $earliest; ?>" data-today="<?php echo $today; ?>">
            <h2>Period</h2>
            <p class="note" style="margin-bottom: 12px;">Set the ones you want. A picker left on "-" (or empty) is not used.</p>
            <p class="field-error" data-error="period" hidden></p>

            <div class="period-block" data-block="date">
                <div class="period-head"><span>Between two dates</span><span class="tag"></span></div>
                <div class="period-panel">
                    <label class="range-label">From
                        <input type="date" name="report_date_from" id="report-from" value="" min="<?php echo $earliest; ?>" max="<?php echo $today; ?>">
                    </label>
                    <label class="range-label">To
                        <input type="date" name="report_date_to" id="report-to" value="" min="<?php echo $earliest; ?>" max="<?php echo $today; ?>">
                    </label>
                </div>
                <p class="field-error" data-error="date" hidden></p>
                <p class="note" style="margin: 8px 0;">The report starts at the beginning of the first day and ends at the end of the last day. Pick the same day in both for that one whole day.</p>
                <div class="period-panel">
                    <button type="button" class="today-btn" id="today-btn" data-today="<?php echo $today; ?>">Today</button>
                    <button type="button" class="today-btn" id="clear-date-btn">Clear</button>
                </div>
            </div>

            <div class="period-block" data-block="week">
                <div class="period-head"><span>Week</span><span class="tag"></span></div>
                <div class="period-panel">
                    <select name="report_week" id="report-week" aria-label="Week">
                        <option value="">-</option>
                        <?php foreach ($weeks as $week): ?>
                            <option value="<?php echo $week["value"]; ?>"><?php echo htmlspecialchars($week["label"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="field-error" data-error="week" hidden></p>
            </div>

            <div class="period-block" data-block="month">
                <div class="period-head"><span>Month</span><span class="tag"></span></div>
                <div class="period-panel">
                    <select name="report_month" id="report-month" aria-label="Month">
                        <option value="">-</option>
                        <?php foreach ($months as $number => $name): ?>
                            <option value="<?php echo $number + 1; ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="report_year" id="report-year" aria-label="Year" disabled>
                        <?php for ($y = $thisYear; $y >= $firstYear; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <p class="field-error" data-error="month" hidden></p>
            </div>

            <h2>Include in the report</h2>
            <p class="field-error" data-error="sections" hidden></p>
            <?php foreach ($sections as $key => $label): ?>
                <div class="option-row">
                    <span><?php echo $label; ?></span>
                    <label class="switch">
                        <input type="checkbox" name="sections[]" value="<?php echo $key; ?>" aria-label="<?php echo $label; ?>">
                        <span class="track"></span>
                        <span class="state"></span>
                    </label>
                </div>
            <?php endforeach; ?>

            <button type="submit" name="generate" value="generate" class="generate-btn">Generate report</button>
        </form>

        <div id="report-status" class="report-status" aria-live="polite" hidden>
            <span id="report-status-text"></span>
        </div>

        <a class="back-link" href="adminAgent.php">Back to the dashboard</a>
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
