<?php
// The Settings page: shows the settings kept in config.json and sends the changes to the Flask server (LocalAIServ.py).
// Each part of the work is a function in one of the files below, this file only runs them in order.
session_start();

require __DIR__ . "/common/config.php";
require __DIR__ . "/common/session.php";
require __DIR__ . "/common/database.php";
require __DIR__ . "/common/popups.php";
require __DIR__ . "/common/assets.php";
require __DIR__ . "/common/inactivitybox.php";
require __DIR__ . "/common/activeusers.php";
require __DIR__ . "/common/logouthandling.php";
require __DIR__ . "/settings/settingshandling.php";

$userN = requireRole("admin");
$secrets = loadSecrets();
$conn = connectDatabase($secrets);

handleTouchRequest($conn, $userN);
handleLogoutRequest($conn, $userN, "admin");

$fields = settingFields();
handleSaveSettings($fields);

$current = loadCurrentSettings();
$serverDown = $current === null;
$help = settingsHelp();

$err = takeError();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php scriptTag("idle.js"); scriptTag("js/common.js"); scriptTag("js/inactivity.js"); scriptTag("js/settings.js"); ?>
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOP Agent</title>
</head>
<body>
<?php renderPopups($err); ?>

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

<?php renderInactivityBox(); ?>
</body>
</html>
