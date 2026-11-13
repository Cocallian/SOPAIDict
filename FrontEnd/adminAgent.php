<?php
// The admin page: ask the agent questions, the dashboard (graphs, users, applications, backups, server) and the way to the
// reports and settings pages. Each part of the work is a function in one of the files below, this file only runs them in order.
session_start();

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/common/config.php";
require __DIR__ . "/common/session.php";
require __DIR__ . "/common/csrf.php";
require __DIR__ . "/common/database.php";
require __DIR__ . "/common/popups.php";
require __DIR__ . "/common/assets.php";
require __DIR__ . "/common/inactivitybox.php";
require __DIR__ . "/common/orgdomains.php";
require __DIR__ . "/common/validation.php";
require __DIR__ . "/common/activeusers.php";
require __DIR__ . "/common/logouthandling.php";
require __DIR__ . "/common/feedback.php";
require __DIR__ . "/common/questionhandling.php";
require __DIR__ . "/adminAgent/backuphandling.php";
require __DIR__ . "/adminAgent/serverhandling.php";
require __DIR__ . "/adminAgent/userhandling.php";
require __DIR__ . "/adminAgent/applicationhandling.php";
require __DIR__ . "/adminAgent/dashboarddata.php";

$userN = requireRole("admin");
verifyCsrf();
$secrets = loadSecrets();
$orgDomains = loadOrgDomains();
$q = $_POST['user-input'] ?? '';
$a = "";
$feedbackId = null;   // set when this answer got its feedback row

handleBackupRequests($secrets, $userN);
handleServerStatus();
handleStartServer($secrets);
handleChangeServerPath();
handleStopServer();

$conn = connectDatabase($secrets, "Database connection error, contact your system administrator for assistance");

handleTouchRequest($conn, $userN);
handleLogoutRequest($conn, $userN, "admin");
handleFeedbackSave($conn);

handleUserSearch($conn);
handleAddUser($conn, $orgDomains);
handleDeleteUser($conn);
handleApplication($conn);
handleForceLogout($conn);

if($q !== "") { [$a, $feedbackId] = answerQuestion($conn, $q, "admin"); }

$views = buildPeriodViews($conn);
$activeUsers = loadActiveUsers($conn);
$allUsers = loadAllUsers($conn);
$serverUp = serverRunning();
$applications = loadApplications($conn);

$err = takeError();
$success = takeSuccess();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo csrfMeta(); ?>
    <?php scriptTag("idle.js"); scriptTag("js/common.js"); scriptTag("js/menu.js"); scriptTag("js/accountvalidation.js"); scriptTag("js/dashboard.js"); scriptTag("js/backup.js"); scriptTag("js/server.js"); scriptTag("js/inactivity.js"); scriptTag("js/feedback.js"); styleTag("common/feedback.css"); ?>
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
        #refresh-btn {
            position: absolute;
            top: 20px;
            right: 40px;
            width: 44px;
            height: 44px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
        }
        #refresh-btn:hover {
            color: #ffc107;
        }
        #refresh-btn.spinning svg {
            animation: refresh-spin 0.8s linear infinite;
        }
        @keyframes refresh-spin {
            to { transform: rotate(360deg); }
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
        /* A labelled group of buttons inside a card (Backup: Database / System, Server: Control / Settings / Session) */
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
            padding: 12px 14px;
            border-radius: 8px;
            background-color: #232323;
        }
        .button-group:last-child {
            margin-bottom: 0;
        }
        .button-group h4 {
            margin: 0;
            color: #bdbdbd;
            font-size: 13px;
            font-weight: normal;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .button-group form {
            margin: 0;
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
        #active-users {
            max-height: 200px;
            margin: 0;
            padding: 0;
            overflow-y: auto;
            list-style: none;
            border-radius: 8px;
            background-color: #232323;
        }
        #active-users li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-bottom: 1px solid #312f2f;
        }
        #active-users .seen {
            color: #bdbdbd;
            font-size: 13px;
        }
        #active-users form {
            width: auto;
            margin: 0;
        }
        #active-users button {
            margin: 0;
            padding: 4px 10px;
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
        .panel-box button[type="submit"], #reportBtn, #settingsBtn {
            display: block;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: dimgrey;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        #errPopup, #successPopup, #loadingPopup {
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
        /* Server card: green text when the server answers, red when it doesn't */
        #server-status {
            margin: 0;
            font-weight: bold;
        }
        #server-status.up {
            color: #4caf50;
        }
        #server-status.down {
            color: #ef5350;
        }
        /* Server starting: dark while it loads (with the throbber), then green when it is up or red when it failed */
        #loadingPopup {
            background: #232323;
            font-family: Consolas, 'Courier New', monospace;
        }
        #loadingPopup.done {
            background: #2e7d32;
        }
        #loadingPopup.failed {
            background: #d32f2f;
        }
        #errPopup.hidden, #successPopup.hidden, #loadingPopup.hidden {
            display: none;
        }
        #errPopup button, #successPopup button, #loadingPopup button {
            position: absolute;
            top: 6px;
            right: 10px;
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }

        /* Password box shown when a backup button is pressed */
        #backup-confirm {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(35, 35, 35, 0.7);
            z-index: 1500;
        }
        #backup-confirm.open {
            display: block;
        }
        #backup-confirm-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            color: #222;
            padding: 20px 24px;
            border-radius: 8px;
            width: 320px;
            max-width: 90vw;
            box-sizing: border-box;
        }
        #backup-confirm-box input {
            width: 100%;
            box-sizing: border-box;
            margin: 10px 0;
        }

        #backup-confirm-error {
            color: #d32f2f;
            font-size: 13px;
            min-height: 16px;
        }

        /* Loading popup for the Google Drive backups: a solid layer that hides the dashboard and the password box */
        #backup-loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #232323;
            z-index: 1800;
        }
        #backup-loading.open {
            display: block;
        }
        #backup-loading-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 420px;
            max-width: calc(100% - 32px);
            padding: 40px 30px;
            text-align: center;
            border-radius: 10px;
            background-color: #312f2f;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
            color: white;
            line-height: 1.6;
        }
        #backup-loading-text {
            font-family: Consolas, 'Courier New', monospace;
            font-size: 16px;
        }
        #backup-loading-note {
            margin-top: 14px;
            color: #bdbdbd;
            font-size: 13px;
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

<?php renderPopups($err, $success); ?>

<div id = "loadingPopup" class = "hidden">
    <span id = "loadingText"></span>
    <button type = "button" id = "closeLoading" aria-label = "Close">&times;</button>
</div>

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
        <h2>Dashboard</h2>
        <button id="refresh-btn" type="button" aria-label="Refresh dashboard" title="Refresh dashboard">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 12a8 8 0 1 1-2.34-5.66"></path>
                <polyline points="20 4 20 9 15 9"></polyline>
            </svg>
        </button>
        <div class="panel-grid">
            <div class="panel-box wide">
                <h3>Overview</h3>
                <div class="period">
                    <select id="period-select">
                        <option value="7days">Last 7 days</option>
                        <option value="30days">Last 30 days</option>
                        <option value="365days">Last 365 days</option>
                    </select>
                </div>
                <div id="period-views">
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
                </div>

                        </br>
                <a href = "reports.php"><button id = "reportBtn" >Generate Reports</button></a>
                <a href = "settings.php"><button type = "button" id = "settingsBtn">Settings</button></a>
            </div>

            <div class="panel-box">
                <h3>Add new user</h3>
                <form id="new-user-form" method="post" style="width: auto;"><?php echo csrfField(); ?>
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
                <form id="del-user" method="post" style="width: auto;"><?php echo csrfField(); ?>
                    <input type="hidden" name="dname" id="dname">
                    <input type="hidden" name="drole" id="drole">
                    <input type="hidden" name="del-user" value="del-user">
                </form>
                <div id="all-users-wrap">
                <h3 style="margin-top: 20px;">All users (<?php echo count($allUsers); ?>)</h3>
                <ul id="all-users">
                    <?php foreach ($allUsers as $u): ?>
                        <li><?php echo htmlspecialchars($u["Username"]); ?><span class="role"><?php echo $u["Role"]; ?></span></li>
                    <?php endforeach; ?>
                </ul>
                </div>
            </div>
            <div class="panel-box" id="active-box">
                <h3>Active users (<?php echo count($activeUsers); ?>)</h3>
                <?php if (count($activeUsers) === 0): ?>
                    <p>Nobody is logged in.</p>
                <?php else: ?>
                    <ul id="active-users">
                        <?php foreach ($activeUsers as $active): ?>
                            <li>
                                <span><?php echo htmlspecialchars($active["Username"]); ?></span>
                                <span class="seen"><?php echo seenAgo($active["LastSeen"]); ?></span>
                                <?php if ($active["SessionId"] === session_id()): ?>
                                    <span class="seen">(you)</span>
                                <?php else: ?>
                                    <form method="post"><?php echo csrfField(); ?>
                                        <input type="hidden" name="sid" value="<?php echo htmlspecialchars($active["SessionId"]); ?>">
                                        <button type="submit" name="force-logout" value="force-logout">Log out</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="panel-box wide" id="apps-box">
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
                                    <form method="post" style="width: auto;"><?php echo csrfField(); ?>
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
            <div class="panel-box">
                <h3>Backup</h3>
                <div class="button-group">
                    <h4>Database</h4>
                    <form method="post" style="width: auto;"><?php echo csrfField(); ?>
                        <button type = "submit" name = "backup" value = "backup">Database backup - locally</button>
                    </form>
                    <form method="post" style="width: auto;"><?php echo csrfField(); ?>
                        <button type = "submit" name = "backup-drive" value = "backup-drive">Database backup - Google Drive</button>
                    </form>
                </div>
                <div class="button-group">
                    <h4>System</h4>
                    <form method="post" style="width: auto;"><?php echo csrfField(); ?>
                        <button type = "submit" name = "backup-system" value = "backup-system">System backup - locally</button>
                    </form>
                    <form method="post" style="width: auto;"><?php echo csrfField(); ?>
                        <button type = "submit" name = "backup-system-drive" value = "backup-system-drive">System backup - Google Drive</button>
                    </form>
                </div>
            </div>
            <div class="panel-box">
                <h3>RAG Pipeline Server</h3>
                <div class="button-group">
                    <h4>Control</h4>
                    <p id = "server-status" class = "<?php echo $serverUp ? "up" : "down"; ?>">
                        <?php echo $serverUp ? "Server running" : "Server not running"; ?>
                    </p>
                    <form id = "start-server-form" method="post" style="width: auto;"><?php echo csrfField(); ?>
                        <button type = "submit" name = "start-server" value = "start-server">Start server</button>
                    </form>
                    <form method="post" style="width: auto;"><?php echo csrfField(); ?>
                        <button type = "submit" name = "stop-server" value = "stop-server">Stop server</button>
                    </form>
                </div>
                <div class="button-group">
                    <h4>Settings</h4>
                    <form method="post" style="width: auto;"><?php echo csrfField(); ?>
                        <button type = "submit" name = "change-server-path" value = "change-server-path">Change server file (hosting computer only)</button>
                    </form>
                </div>
                <div class="button-group">
                    <h4>Session</h4>
                    <form method="post" style="width: auto;"><?php echo csrfField(); ?>
                        <button type = "submit" name = "logout" value = "logout">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id = "backup-confirm">
        <div id = "backup-confirm-box">
            Enter your password to make this backup
            <input type = "password" id = "backup-confirm-password" autocomplete = "current-password">
            <div id = "backup-confirm-error"></div>
            <button type = "button" id = "backup-confirm-ok">Confirm</button>
            <button type = "button" id = "backup-confirm-cancel">Cancel</button>
        </div>
    </div>

    <div id = "backup-loading">
        <div id = "backup-loading-box">
            <div id = "backup-loading-text"></div>
            <div id = "backup-loading-note" data-drive-only>
                The first Google Drive backup opens a Google sign in in the browser on the server PC.
                Finish it there within 2 minutes. Please don't close or refresh this page.
            </div>
        </div>
    </div>

<?php renderInactivityBox(); ?>
</body>
</html>
