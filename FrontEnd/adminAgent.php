<?php
session_start();
$secrets = parse_ini_file(__DIR__ . "/passwords.txt");   // MySQL login (kept out of the code)
require __DIR__ . "/vendor/autoload.php";
use CpChart\Data;
use CpChart\Image;
if($_SESSION["perm"] != "admin" )
    {
        header("Location: login.php");
        exit();
    }else{
        $userN = $_SESSION["uname"];
        $sesID = session_id();
    }

// Every value under [org_name] in passwords.txt is an allowed email domain, whatever its key is called
require __DIR__ . "/orgdomains.php";
$orgDomains = loadOrgDomains();
require __DIR__ . "/activeusers.php";
$ASK_URL ="http://127.0.0.1:5000/ask";   // the answer server LocalAITest.py runs
$_SESSION["LastSeen"] = time();

$q = $_POST['user-input'] ?? '';
$a = "";



    

// Dumps the SOPAgent database into $folder, returns an error message or "" on success
function dumpDatabase($folder, $secrets)
    {
        $file = $folder . "\\SOPagent" . date("Y-m-d") . ".sql";
        $dump = escapeshellarg("C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe")
              . " --host=" . escapeshellarg($secrets["db_host"])
              . " --user=" . escapeshellarg($secrets["db_user"])
              . ($secrets["db_pass"] !== "" ? " --password=" . escapeshellarg($secrets["db_pass"]) : "")
              . " --single-transaction --databases SOPAgent"
              . " --skip-extended-insert"
              . " --result-file=" . escapeshellarg($file) . " 2>&1";
        exec($dump, $output, $code);
        return $code !== 0 ? "Backup failed: " . implode(" ", $output) : "";
    }

if(array_key_exists('backup', $_POST)) {
            // Folder window opens on this PC's desktop (Apache must be started from the XAMPP Control Panel)
            $pick = 'Add-Type -AssemblyName System.Windows.Forms; '
                  . '$f = New-Object System.Windows.Forms.FolderBrowserDialog; '
                  . '$f.Description = "Choose where to save the SOPAgent backup"; '
                  . 'if($f.ShowDialog((New-Object System.Windows.Forms.Form -Property @{TopMost = $true})) -eq "OK") { $f.SelectedPath }';
            $folder = trim(shell_exec("powershell -NoProfile -STA -EncodedCommand " . base64_encode(iconv("UTF-8", "UTF-16LE", $pick))) ?? "");

            if($folder !== "")
                {
                    $failed = dumpDatabase($folder, $secrets);
                    if($failed !== "") { $_SESSION["err"] = $failed; }
                    else { $_SESSION["success"] = "Database successfully backed up to: " . $folder; }
                }

            // Reload as a normal GET so refreshing the page doesn't send the backup again
            header("Location: adminAgent.php");
            exit();
        }

if(array_key_exists('backup-drive', $_POST)) {
            // Saves into the Google Drive for desktop folder, which uploads it to the cloud by itself
            $drive = "";
            foreach(array_merge(glob("[A-Z]:\\My Drive", GLOB_ONLYDIR) ?: [],
                                [getenv("USERPROFILE") . "\\My Drive", getenv("USERPROFILE") . "\\Google Drive"]) as $candidate)
                {
                    if(is_dir($candidate)) { $drive = $candidate; break; }
                }

            if($drive === "")
                {
                    $_SESSION["err"] = "Google Drive for desktop wasn't found. Install it and sign in, then try again";
                }else{
                    $failed = dumpDatabase($drive, $secrets);
                    if($failed !== "") { $_SESSION["err"] = $failed; }
                    else { $_SESSION["success"] = "Database successfully backed up to Google Drive: " . $drive; }
                }

            header("Location: adminAgent.php");
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


// Refreshes this session's LastSeen, or sends it to Inactive.php when the MySQL cleanup has already removed its row
if(isset($conn)) { touchActiveUser($conn, $userN); }

if(array_key_exists('touch', $_POST)) { exit(); }   // validation.js only wanted LastSeen refreshed, nothing to draw

// Live user search for the Delete User card, answers with a JSON list of usernames
if(array_key_exists('search-user', $_POST))
    {
        $names = [];
        $term = trim($_POST["search-user"]);
        if(isset($conn) && $term !== "")
            {
                $like = "%" . addcslashes($term, "%_\\") . "%";
                $stmt = $conn->prepare("(SELECT Username, 'user' AS Role FROM Users WHERE Username LIKE ?)
                                        UNION ALL
                                        (SELECT Username, 'admin' AS Role FROM Admins WHERE Username LIKE ?)
                                        ORDER BY Username LIMIT 20");
                $stmt->bind_param("ss", $like, $like);
                $stmt->execute();
                $stmt->bind_result($found, $foundRole);
                while($stmt->fetch()) { $names[] = ["name" => $found, "role" => $foundRole]; }
                $stmt->close();
            }
        header("Content-Type: application/json");
        echo json_encode($names);
        exit();
    }

if(array_key_exists('add-user', $_POST) && isset($conn))
    {
        $newName = trim($_POST["name"] ?? "");
        $newHash = hash("sha256", $_POST["password"] ?? "");
        $isAdmin = ($_POST["role"] ?? "") === "admin";

        $validOrg = validOrgUsername($newName, $orgDomains);

        $pw = $_POST["password"] ?? "";
        $strongPw = preg_match('/.{8,}/', $pw) && preg_match('/[0-9]/', $pw) && preg_match('/[a-z]/', $pw)
                 && preg_match('/[A-Z]/', $pw) && preg_match('/[^A-Za-z0-9]/', $pw);

        if(!$validOrg)
            {
                $_SESSION["err"] = "The username must be an email ending in: " . implode(", ", $orgDomains);
            }
        else if(!$strongPw)
            {
                $_SESSION["err"] = "The password needs 8+ characters with a number, lowercase, uppercase and special character";
            }
        else if($pw !== ($_POST["confirmPassword"] ?? ""))
            {
                $_SESSION["err"] = "The passwords don't match";
            }
        // dataconn.php decides admin or user by the username, so the name has to match the role
        else if($isAdmin && substr($newName, 0, 5) !== "admin")
            {
                $_SESSION["err"] = "Admin usernames must start with \"admin\"";
            }
        else if(!$isAdmin && substr($newName, 0, 5) === "admin")
            {
                $_SESSION["err"] = "User usernames can't start with \"admin\"";
            }
        else
            {
                try
                    {
                        $stmt = $conn->prepare($isAdmin ? "INSERT INTO Admins (Username, PasswordHash) VALUES (?, ?)"
                                                        : "INSERT INTO Users (Username, PasswordHash) VALUES (?, ?)");
                        $stmt->bind_param("ss", $newName, $newHash);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION["success"] = ($isAdmin ? "Admin " : "User ") . $newName . " added";
                    }
                catch(mysqli_sql_exception $e)
                    {
                        $_SESSION["err"] = $e->getCode() == 1062 ? "The username " . $newName . " already exists"
                                                                 : "The user could not be added to the database";
                    }
            }

        // Reload as a normal GET so refreshing the page doesn't add the user again
        header("Location: adminAgent.php");
        exit();
    }

if(array_key_exists('del-user', $_POST) && isset($conn))
    {
        $dname = trim($_POST["dname"]);
        $dIsAdmin = ($_POST["drole"] ?? "") === "admin";
        $dtable = $dIsAdmin ? "Admins" : "Users";    // only ever one of these two, never taken from the form directly
        $dlabel = $dIsAdmin ? "Admin " : "User ";

        try{

        $stmt = $conn->prepare("SELECT 1 FROM $dtable WHERE Username = ?");
        $stmt->bind_param("s", $dname);
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
            if($count < 1)
            {
                $_SESSION["err"] = "No user found with that name";
            }elseif($count > 1)
            {
                $_SESSION["err"] = "More than one user with that name, delete it manually via sql statement in MySQL workbench";
            }elseif($dIsAdmin && $dname === ($_SESSION["uname"] ?? ""))
            {
                $_SESSION["err"] = "You can't delete the admin account you're logged in with";
            }else{
                try{
                $stmt = $conn->prepare("DELETE FROM $dtable WHERE Username = ?");
                $stmt->bind_param("s", $dname);
                $stmt->execute();
                $stmt->close();
                $_SESSION["success"] = $dlabel . $dname . " deleted";
            }catch(mysqli_sql_exception $e)
            {
                $_SESSION["err"] = "Database connection error: " . $e;
            }
            }
        }catch(mysqli_sql_exception $e)
        {
            $_SESSION["err"] = "Database connection error: " . $e;
        }

        

        

            
            


    }

// Approve or reject a sign-up request from the Applications card
if(array_key_exists('app-action', $_POST) && isset($conn))
    {
        $appId = (int)($_POST["app-id"] ?? 0);
        try
            {
                $stmt = $conn->prepare("SELECT Username, PasswordHash, Role FROM UserApplication WHERE ApplicationId = ?");
                $stmt->bind_param("i", $appId);
                $stmt->execute();
                $stmt->bind_result($appName, $appHash, $appRole);
                $appFound = $stmt->fetch();
                $stmt->close();

                if(!$appFound)
                    {
                        $_SESSION["err"] = "That application no longer exists";
                    }
                else if($_POST["app-action"] === "approve")
                    {
                        $appIsAdmin = $appRole === "admin";
                        // dataconn.php decides admin or user by the username, so the name has to match the role
                        if($appIsAdmin && substr($appName, 0, 5) !== "admin")
                            {
                                $_SESSION["err"] = "Admin usernames must start with \"admin\", reject this application or have it resubmitted";
                            }
                        else if(!$appIsAdmin && substr($appName, 0, 5) === "admin")
                            {
                                $_SESSION["err"] = "User usernames can't start with \"admin\", reject this application or have it resubmitted";
                            }
                        else
                            {
                                $conn->begin_transaction();
                                $stmt = $conn->prepare($appIsAdmin ? "INSERT INTO Admins (Username, PasswordHash) VALUES (?, ?)"
                                                                   : "INSERT INTO Users (Username, PasswordHash) VALUES (?, ?)");
                                $stmt->bind_param("ss", $appName, $appHash);
                                $stmt->execute();
                                $stmt->close();
                                $stmt = $conn->prepare("DELETE FROM UserApplication WHERE ApplicationId = ?");
                                $stmt->bind_param("i", $appId);
                                $stmt->execute();
                                $stmt->close();
                                $conn->commit();
                                $_SESSION["success"] = ($appIsAdmin ? "Admin " : "User ") . $appName . " approved and added";
                            }
                    }
                else
                    {
                        $stmt = $conn->prepare("DELETE FROM UserApplication WHERE ApplicationId = ?");
                        $stmt->bind_param("i", $appId);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION["success"] = "Application from " . $appName . " rejected";
                    }
            }
        catch(mysqli_sql_exception $e)
            {
                $conn->rollback();
                $_SESSION["err"] = $e->getCode() == 1062 ? "The username " . $appName . " already exists, reject this application"
                                                         : "The application could not be processed";
            }

        // Reload as a normal GET so refreshing the page doesn't process it again
        header("Location: adminAgent.php");
        exit();
    }

if($q !== "")
    {
        set_time_limit(300);

        $promptId = null;

        if(isset($conn))
            {
                try
                    {
                        $uname = $_SESSION["uname"] ?? "";
                        $stmt = $conn->prepare("SELECT AdminId FROM Admins WHERE Username = ?");
                        $stmt->bind_param("s", $uname);
                        $stmt->execute();
                        $stmt->bind_result($adminId);
                        $stmt->fetch();
                        $stmt->close();

                        $stmt = $conn->prepare("INSERT INTO Prompts (AdminId, PromptText, CreatedAt) VALUES (?, ?, NOW())");
                        $stmt->bind_param("is", $adminId, $q);
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
            "content"       => http_build_query(["question" => $q, "role" => "admin", "ip" => $_SERVER["REMOTE_ADDR"], "user" => $_SESSION["uname"] ?? ""]),   // admins are answered before users
            "timeout"       => 300,
            "ignore_errors" => true,
        ]]));
        $a = $reply === false ? "The answer server isn't running. Start LocalAITest.py and try again." : $reply;

        if($promptId && $a !== null && trim($a) !== "")
            {
                try
                    {
                        $stmt = $conn->prepare("INSERT INTO Responses (PromptId, AdminId, ResponseText, CreatedAt) VALUES (?, ?, ?, NOW())");
                        $stmt->bind_param("iis", $promptId, $adminId, $a);
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
                        $stmt = $conn->prepare("INSERT INTO SourcedDocuments (PromptId, AdminId, Title, SourcedAt) VALUES (?, ?, ?, NOW())");
                        foreach(array_unique($titles) as $title)
                            {
                                $stmt->bind_param("iis", $promptId, $adminId, $title);
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

    // Every period is built once here, the dashboard's dropdown just shows one and hides the rest
    $periods = ["today", "Week", "Month", "Year"];
    $views = [];
    foreach($periods as $p)
        {
            if($p == "today")
                {
                    $from  = "CURDATE()";
                    $group = "HOUR(CreatedAt)";
                    $axis  = [];
                    for($h = 0; $h < 24; $h++) { $axis[$h] = sprintf("%02d", $h); }
                    $title = "Queries today (by hour)";
                    $xName = "Hour of day";
                }
            if($p == "Week")
                {
                    $from  = "DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
                    $group = "WEEKDAY(CreatedAt)";
                    $axis  = [0 => "Mon", 1 => "Tue", 2 => "Wed", 3 => "Thu", 4 => "Fri", 5 => "Sat", 6 => "Sun"];
                    $title = "Queries this week (by day)";
                    $xName = "Day of week";
                }
            if($p == "Month")
                {
                    $from  = "DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                    $group = "DAY(CreatedAt)";
                    $axis  = [];
                    for($d = 1; $d <= (int)date("t"); $d++) { $axis[$d] = (string)$d; }
                    $title = "Queries this month (by day)";
                    $xName = "Day of month";
                }
            if($p == "Year")
                {
                    $from  = "DATE_FORMAT(CURDATE(), '%Y-01-01')";
                    $group = "MONTH(CreatedAt)";
                    $axis  = [1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "Jun",
                              7 => "Jul", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec"];
                    $title = "Queries this year (by month)";
                    $xName = "Month";
                }

            $labels = [];
            $counts = [];
            $graphImg = "";
            $userD = 0;
            $TotQ = 0;

            if(isset($conn))
                {
                    $found = [];
                    $res = $conn->query("SELECT $group AS bucket, COUNT(*) AS total
                                         FROM Prompts
                                         WHERE CreatedAt >= $from
                                         GROUP BY bucket");
                    while($row = $res->fetch_assoc())
                        {
                            $found[(int)$row["bucket"]] = (int)$row["total"];
                        }

                    foreach($axis as $bucket => $label)
                        {
                            $labels[] = $label;
                            $counts[] = $found[$bucket] ?? 0;
                        }

                    $res = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT UserId) AS users, COUNT(DISTINCT AdminId) AS admins
                                         FROM Prompts
                                         WHERE CreatedAt >= $from");
                    $row = $res->fetch_assoc();
                    $userD = (int)$row["users"] + (int)$row["admins"];
                    $TotQ  = (int)$row["total"];
                }

            if(array_sum($counts) > 0)
                {
                    $chart = new Data();
                    $chart->addPoints($counts, "Queries");
                    $chart->addPoints($labels, "Labels");
                    $chart->setAbscissa("Labels");
                    $chart->setSerieDescription("Queries", $title);
                    $chart->setPalette("Queries", ["R" => 255, "G" => 193, "B" => 7]);
                    $chart->setAxisName(0, "Number of queries");
                    $chart->setAbscissaName($xName);

                    $nonZero = [];
                    foreach($counts as $c) { $nonZero[] = $c > 0 ? $c : VOID; }
                    $chart->addPoints($nonZero, "QueryLabels");
                    $chart->setPalette("QueryLabels", ["R" => 255, "G" => 193, "B" => 7]);

                    $img = new Image(1600, 170, $chart);   // shown at this exact size, wider than the box so the box scrolls sideways
                    $img->drawFilledRectangle(0, 0, 1600, 170, ["R" => 35, "G" => 35, "B" => 35]);
                    $img->setFontProperties(["FontName" => "MankSans.ttf", "FontSize" => 10, "R" => 255, "G" => 255, "B" => 255]);
                    $img->setGraphArea(80, 15, 1580, 120);
                    $chart->setSerieDrawable("QueryLabels", false);
                    $img->drawScale(["GridR" => 255, "GridG" => 255, "GridB" => 255, "GridAlpha" => 40, "Mode" => SCALE_MODE_START0,
                                     "DrawSubTicks" => false, "AxisR" => 255, "AxisG" => 255, "AxisB" => 255, "TickR" => 255, "TickG" => 255, "TickB" => 255]);
                    $img->drawLineChart(["Weight" => 3]);
                    $img->drawPlotChart(["PlotSize" => 4, "PlotBorder" => true, "BorderR" => 35, "BorderG" => 35, "BorderB" => 35]);

                    $chart->setSerieDrawable("Queries", false);
                    $chart->setSerieDrawable("QueryLabels", true);
                    $img->drawLineChart(["DisplayValues" => true, "DisplayColor" => DISPLAY_MANUAL, "DisplayR" => 255, "DisplayG" => 255, "DisplayB" => 255,
                                         "ForceColor" => true, "ForceAlpha" => 0]);

                    $tmp = tempnam(sys_get_temp_dir(), "pc");
                    $img->render($tmp);
                    $graphImg = base64_encode(file_get_contents($tmp));
                    unlink($tmp);
                }

            $views[$p] = ["users" => $userD, "total" => $TotQ, "title" => $title, "img" => $graphImg];
        }

    // Everyone in the Users and Admins tables, for the scrollable list under Delete User
    $allUsers = [];
    if(isset($conn))
        {
            $res = $conn->query("(SELECT Username, 'user' AS Role FROM Users) UNION ALL (SELECT Username, 'admin' AS Role FROM Admins) ORDER BY Username");
            while($row = $res->fetch_assoc()) { $allUsers[] = $row; }
        }
    function LogOut($conn, $userN)
    {
        $_SESSION["perm"] = "";    // "" means not logged in, the same as dataconn.php uses
        $_SESSION["uname"] = "";
        // validation.js sends reason=inactive when the 15 minutes ran out, a click on Log out sends nothing
        writeLog($conn, $userN, "admin", ($_POST["reason"] ?? "") === "inactive" ? "inactive" : "logout");
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
    // Pending sign-up requests, oldest first
    $applications = [];
    if(isset($conn))
        {
            try
                {
                    $res = $conn->query("SELECT ApplicationId, Username, Role, SubmittedAt FROM UserApplication ORDER BY SubmittedAt");
                    while($row = $res->fetch_assoc()) { $applications[] = $row; }
                }
            catch(mysqli_sql_exception) {}   // table is created the next time anyone logs in via dataconn.php
        }
    if(array_key_exists('logout', $_POST))
    {
        if(isset($conn)) { LogOut($conn, $userN); }
        $_SESSION["perm"] = "";   
        $_SESSION["uname"] = "";
        header("Location: login.php");
        exit();
    }

    $err = $_SESSION["err"] ?? "";
    $_SESSION["err"] = "";
    $success = $_SESSION["success"] ?? "";
    $_SESSION["success"] = "";

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
            background: linear-gradient(to bottom,  #7d7e7d 0%,#232323 73%);
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
            line-height: 1.5em;
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
            overflow-x: hidden;                    /* only the graph's own box scrolls sideways, never the dashboard */
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
        .panel-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .panel-box {
            flex: 1 1 400px;
            min-width: 0;                          /* a flex item grows to fit its content by default, the 1600px graph must not make it */
            padding: 20px;
            border-radius: 10px;
            background-color: #312f2f;
        }
        .panel-box.wide {
            flex: 1 1 100%;
        }
        .panel-box button[type="submit"], #reportBtn {
            display: block;
        }
        #password-rules {
            margin: 0 0 12px;
            padding: 0;
            list-style: none;
            font-size: 14px;
        }
        #password-rules li {
            color: #ff8a80;
        }
        #password-rules li::before {
            content: "\2717  ";
        }
        #password-rules li.checked {
            color: #81c784;
        }
        #password-rules li.checked::before {
            content: "\2713  ";
        }
        #applications {
            width: 100%;
            border-collapse: collapse;
        }
        #applications th, #applications td {
            padding: 8px;
            text-align: left;
        }
        #applications tbody tr {
            background-color: #232323;
        }
        #applications form button {
            display: inline-block;
        }
        #all-users {
            max-height: 200px;
            margin: 0;
            padding: 0;
            overflow-y: auto;
            list-style: none;
            border-radius: 8px;
            background-color: #232323;
        }
        #all-users li {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #312f2f;
        }
        #all-users .role {
            color: #bdbdbd;
        }
        #user-results {
            width: 100%;
            border-collapse: collapse;
        }
        #user-results th, #user-results td {
            padding: 8px;
            text-align: left;
        }
        #user-results tbody tr {
            cursor: pointer;
            background-color: #232323;
        }
        #user-results tbody tr:hover {
            background-color: #d32f2f;
        }
        .panel-box h3 {
            margin: 0 0 15px;
        }
        .stat-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .stat {
            flex: 1 1 120px;
            padding: 15px;
            border-radius: 8px;
            background-color: dimgrey;
        }
        .stat strong {
            display: block;
            font-size: 28px;
        }
        .panel-box .period {
            margin-bottom: 15px;
        }
        .panel-box .period select {
            width: 200px;
            margin-top: 0;
        }
        .graph {
            margin-top: 15px;
        }
        .graph-empty {
            min-height: 120px;
        }
        /* The graph is 1600 x 170. The box around it is exactly as tall as the graph, is never wider than its card and scrolls
           sideways when the card is narrower than the graph. On a card wider than 1600px the graph is stretched to fill it. */
        .graph-scroll {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }
        .graph-img {
            display: block;
            width: 1600px;
            min-width: 100%;
            height: 170px;
            max-width: none;
        }
        .panel-box label {
            display: block;
            margin-bottom: 12px;
        }
        .panel-box input[type="text"],
        .panel-box input[type="password"],
        .panel-box select {
            display: block;
            width: 100%;
            height: 40px;
            margin-top: 5px;
            padding: 8px;
            border-radius: 8px;
            border: none;
            outline: none;
            background-color: #232323;
            color: white;
            font: inherit;
        }
        .panel-box button[type="submit"], #reportBtn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: dimgrey;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        #errPopup, #successPopup {
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
        #successPopup {
            background: #2e7d32;
        }
        #errPopup.hidden, #successPopup.hidden {
            display: none;
        }
        #errPopup button, #successPopup button {
            position: absolute;
            top: 6px;
            right: 10px;
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }

        /* Transparent gray layer over the whole page, hidden until the page shows it */
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
        #inactiveBox button {
            margin-top: 20px;
            width: 100%;
            padding: 15px;
            border: 0;
            border-radius: 8px;
            background-color: dimgrey;
            color: white;
            font: inherit;
            text-transform: uppercase;
            cursor: pointer;
        }
        #inactiveBox button:hover {
            background-color: #7d7e7d;
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

<?php if ($success !== ""): ?>
    <div id = "successPopup">
        <?php echo htmlspecialchars($success); ?>
        <button type = "button" id = "closeSuccess" aria-label = "Close">&times;</button>
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
        <h2>Dashboard</h2>
        <div class="panel-grid">
            <div class="panel-box wide">
                <h3>Overview</h3>
                <div class="period">
                    <select id="period-select">
                        <option value="today">Today</option>
                        <option value="Week">This Week</option>
                        <option value="Month">This Month</option>
                        <option value="Year">This Year</option>
                    </select>
                </div>
                <?php foreach ($views as $p => $view): ?>
                <div class="period-view"<?php echo ' data-period="' . $p . '"'; ?> hidden>
                    <div class="stat-row">
                        <div class="stat"><strong><?php echo $view["users"]; ?></strong>Distinct Users</div>
                        <div class="stat"><strong><?php echo $view["total"]; ?></strong>Total Queries</div>
                    </div>
                    <div class="stat graph<?php if ($view["img"] === "") echo " graph-empty"; ?>">
                        <?php if ($view["img"] !== ""): ?>
                            <div class="graph-scroll">
                                <img class="graph-img" src="data:image/png;base64,<?php echo $view["img"]; ?>" alt="<?php echo htmlspecialchars($view["title"]); ?>">
                            </div>
                        <?php else: ?>
                            <strong>&mdash;</strong>Queries vs Time
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                        </br>
                <a href = "reports.php"><button id = "reportBtn" >Generate Reports</button></a>
            </div>

            <div class="panel-box">
                <h3>Add new user</h3>
                <form id="new-user-form" method="post" style="width: auto;">
                    <label>Username
                        <input type="text" id="new-username" name="name" maxlength="50" data-domains="<?php echo htmlspecialchars(json_encode($orgDomains)); ?>" required>
                    </label>
                    <label>Password
                        <input type="password" id="new-password" name="password" required>
                    </label>
                    <ul id="password-rules">
                        <li>At least 8 characters</li>
                        <li>At least one number</li>
                        <li>At least one lowercase letter</li>
                        <li>At least one uppercase letter</li>
                        <li>At least one special character</li>
                    </ul>
                    <label>Confirm Password
                    <input type="password" id="confirm-password" name="confirmPassword" required>
                    </label>
                    <label>Role
                        <select name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </label>
                    <button type="submit" name="add-user" value="add-user">Add user</button>
                </form>
            </div>
            <div class="panel-box">
                <h3>Delete User</h3>
                <label>Search username
                    <input type="text" id="user-search" maxlength="50" autocomplete="off">
                </label>
                <table id="user-results" hidden>
                    <thead><tr><th>Username (click to delete)</th><th>Role</th></tr></thead>
                    <tbody></tbody>
                </table>
                <form id="del-user" method="post" style="width: auto;">
                    <input type="hidden" name="dname" id="dname">
                    <input type="hidden" name="drole" id="drole">
                    <input type="hidden" name="del-user" value="del-user">
                </form>
                <h3 style="margin-top: 20px;">All users (<?php echo count($allUsers); ?>)</h3>
                <ul id="all-users">
                    <?php foreach ($allUsers as $u): ?>
                        <li><?php echo htmlspecialchars($u["Username"]); ?><span class="role"><?php echo $u["Role"]; ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="panel-box">
                <h3>Applications (<?php echo count($applications); ?>)</h3>
                <?php if (count($applications) === 0): ?>
                    <p>No applications waiting.</p>
                <?php else: ?>
                    <table id="applications">
                        <thead><tr><th>Username</th><th>Role</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app["Username"]); ?></td>
                                <td><?php echo htmlspecialchars($app["Role"]); ?></td>
                                <td><?php echo htmlspecialchars($app["SubmittedAt"]); ?></td>
                                <td>
                                    <form method="post" style="width: auto;">
                                        <input type="hidden" name="app-id" value="<?php echo (int)$app["ApplicationId"]; ?>">
                                        <button type="submit" name="app-action" value="approve">Approve</button>
                                        <button type="submit" name="app-action" value="reject">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class = "panel-box">
            <h3>Backup</h3>
            <form method="post" style="width: auto;">
                <button type = "submit" name = "backup" value = "backup">Database backup - locally</button>
            </form>
            <form method="post" style="width: auto; margin-top: 15px;">
                <button type = "submit" name = "backup-drive" value = "backup-drive">Database backup - Google Drive</button>
            </form>
            <form method="post" style="width: auto; margin-top: 15px;">
                <button type = "submit" name = "backup-system" value = "backup-system">System backup - locally</button>
            </form>
            <form method="post" style="width: auto; margin-top: 15px;">
                <button type = "submit" name = "backup-system-drive" value = "backup-system-drive">System backup - Google Drive</button>
            </form>
            <form method="post" style="width: auto; margin-top: 15px;">
                <button type = "submit" name = "logout" value = "logout">Log out</button>
            </form>
            </div>

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
