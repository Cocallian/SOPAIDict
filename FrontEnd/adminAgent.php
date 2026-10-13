<?php
session_start();
require __DIR__ . "/vendor/autoload.php";
use CpChart\Data;
use CpChart\Image;
if($_SESSION["perm"] != "admin" )
    {
        header("Location: login.php");
        exit();
    }
$PYTH = "C:\\Users\\tygra\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
$SCRIPT = "C:\\Users\\tygra\\Documents\\Code\\Pythom\\LLM\\LocalAITest.py";


$q = $_POST['user-input'] ?? '';
$a = "";

        try
        {
            $secrets = parse_ini_file(__DIR__ . "/passwords.txt");
            $conn = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"]);
            $conn->select_db("SOPAgent");
        }
        catch(mysqli_sql_exception)
        {
            $_SESSION["err"] = "Database connection error, contact your system administrator for assistance";
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

        $command = escapeshellarg($PYTH) . " " . escapeshellarg($SCRIPT) . " " . escapeshellarg($q) . " 2>&1";
    $a = shell_exec($command);

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
            }
    }

    $userD = 0;
    $TotQ = 0;
    $periods = ["today", "Week", "Month", "Year"];
    $period = in_array($_GET["period"] ?? "", $periods, true) ? $_GET["period"] : "today";
    if($period == "today")
        {
            $from  = "CURDATE()";
            $group = "HOUR(CreatedAt)";
            $axis  = [];
            for($h = 0; $h < 24; $h++) { $axis[$h] = sprintf("%02d", $h); }
            $title = "Queries today (by hour)";

        }
    if($period == "Week")
        {
            $from  = "DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
            $group = "WEEKDAY(CreatedAt)";
            $axis  = [0 => "Mon", 1 => "Tue", 2 => "Wed", 3 => "Thu", 4 => "Fri", 5 => "Sat", 6 => "Sun"];
            $title = "Queries this week (by day)";
        }
    if($period == "Month")
        {
            $from  = "DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            $group = "DAY(CreatedAt)";
            $axis  = [];
            for($d = 1; $d <= (int)date("t"); $d++) { $axis[$d] = (string)$d; }
            $title = "Queries this month (by day)";
        }
    if($period == "Year")
        {
            $from  = "DATE_FORMAT(CURDATE(), '%Y-01-01')";
            $group = "MONTH(CreatedAt)";
            $axis  = [1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "Jun",
                      7 => "Jul", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec"];
            $title = "Queries this year (by month)";
        }

    $labels = [];
    $counts = [];
    $graphImg = "";

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
            $chart->setPalette("Queries", ["R" => 79, "G" => 195, "B" => 247]);

            $nonZero = [];
            foreach($counts as $c) { $nonZero[] = $c > 0 ? $c : VOID; }
            $chart->addPoints($nonZero, "QueryLabels");
            $chart->setPalette("QueryLabels", ["R" => 79, "G" => 195, "B" => 247]);

            $img = new Image(700, 260, $chart);
            $img->drawFilledRectangle(0, 0, 700, 260, ["R" => 105, "G" => 105, "B" => 105]);
            $img->setFontProperties(["FontName" => "MankSans.ttf", "FontSize" => 9, "R" => 255, "G" => 255, "B" => 255]);
            $img->setGraphArea(50, 20, 680, 220);
            $chart->setSerieDrawable("QueryLabels", false);
            $img->drawScale(["GridR" => 255, "GridG" => 255, "GridB" => 255, "GridAlpha" => 15, "Mode" => SCALE_MODE_START0]);
            $img->drawLineChart();

            $chart->setSerieDrawable("Queries", false);
            $chart->setSerieDrawable("QueryLabels", true);
            $img->drawLineChart(["DisplayValues" => true, "DisplayColor" => DISPLAY_MANUAL, "DisplayR" => 255, "DisplayG" => 255, "DisplayB" => 255,
                                 "ForceColor" => true, "ForceAlpha" => 0]);

            $tmp = tempnam(sys_get_temp_dir(), "pc");
            $img->render($tmp);
            $graphImg = base64_encode(file_get_contents($tmp));
            unlink($tmp);
        }

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
            e.preventDefault();
            if(!document.getElementById("user-input").value.trim()) return;
            throb();
            setTimeout(() => e.target.submit(), 100);
        }

        async function throb()
        {
            const ta = document.getElementById("ta");
            ta.style.fontFamily = "Consolas, 'Courier New', monospace";
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
            padding: 20px;
            border-radius: 10px;
            background-color: #312f2f;
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
        #graph {
            margin-top: 15px;
            min-height: 280px;
        }
        .panel-box label {
            display: block;
            margin-bottom: 12px;
        }
        .panel-box input[type="text"],
        .panel-box input[type="email"],
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
        .panel-box button[type="submit"] {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: dimgrey;
            color: white;
            font: inherit;
            cursor: pointer;
        }

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
        <h2>Dashboard</h2>
        <div class="panel-grid">
            <div class="panel-box">
                <h3>Overview</h3>
                <div class="period">
                    <form method="get" style="width: auto;">
                    <select name="period" onchange="this.form.submit()">
                        <option value="today"<?php if ($period === "today") echo " selected"; ?>>Today</option>
                        <option value="Week"<?php if ($period === "Week") echo " selected"; ?>>This Week</option>
                        <option value="Month"<?php if ($period === "Month") echo " selected"; ?>>This Month</option>
                        <option value="Year"<?php if ($period === "Year") echo " selected"; ?>>This Year</option>
                    </select>
                    </form>
                </div>
                <div class="stat-row">
                    <div class="stat"><strong><?php echo $userD; ?></strong>Distinct Users</div>
                    <div class="stat"><strong><?php echo $TotQ; ?></strong>Total Queries</div>
                </div>
                <div class="stat" id = "graph">
                    <?php if ($graphImg !== ""): ?>
                        <img src="data:image/png;base64,<?php echo $graphImg; ?>" alt="<?php echo htmlspecialchars($title); ?>" style="max-width: 100%;">
                    <?php else: ?>
                        <strong>&mdash;</strong>Queries vs Time
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel-box">
                <h3>Add new user</h3>
                <form id="new-user-form" onsubmit="event.preventDefault()">
                    <label>Name
                        <input type="text" name="name" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" required>
                    </label>
                    <label>Role
                        <select name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </label>
                    <button type="submit">Add user</button>
                </form>
            </div>
        </div>
    </div>
<script>
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
