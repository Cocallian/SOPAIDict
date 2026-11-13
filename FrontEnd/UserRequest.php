<?php
// The page someone without an account uses to ask for one. An admin approves or rejects it on the dashboard.
// Each part of the work is a function in one of the files below, this file only runs them in order.
session_start();

require __DIR__ . "/common/config.php";
require __DIR__ . "/common/popups.php";
require __DIR__ . "/common/assets.php";
require __DIR__ . "/common/orgdomains.php";
require __DIR__ . "/common/validation.php";
require __DIR__ . "/UserRequest/applicationhandling.php";

$secrets = loadSecrets();
$orgDomains = loadOrgDomains();

handleApplicationRequest($secrets, $orgDomains);

$err = takeError();
$success = takeSuccess();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php scriptTag("js/common.js"); scriptTag("js/accountvalidation.js"); ?>
    <style>
        /* Layout from Colorlib's login form (codepen.io/colorlib/pen/rxddKy), in the Agent pages' colours */
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
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .login-page {
            width: 360px;
            max-width: calc(100% - 32px);   /* keeps a 16px gap each side on phones */
            padding: 8% 0 0;
            margin: auto;
        }
        .form {
            position: relative;
            z-index: 1;
            background-color: #312f2f;
            max-width: 360px;
            margin: 0 auto 100px;
            padding: 45px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
            color: white;
        }
        @media (max-width: 420px) {
            .form {
                padding: 30px 20px;   /* less padding on phones, so the fields stay wide */
            }
        }
        .form h1 {
            margin: 0 0 30px;
            font-size: 24px;
            font-weight: normal;
        }
        .form input {
            font-family: inherit;
            outline: 0;
            background-color: #232323;
            color: white;
            width: 100%;
            border: 2px solid transparent;
            border-radius: 8px;
            margin: 0 0 15px;
            padding: 15px;
            font-size: 14px;
        }
        .form input:focus {
            border-color: dimgrey;
        }
        .form input.invalid {
            border-color: #d32f2f;
        }
        .form button {
            font-family: inherit;
            text-transform: uppercase;
            outline: 0;
            background-color: dimgrey;
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 15px;
            color: white;
            font-size: 14px;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        .form button:hover,
        .form button:active,
        .form button:focus {
            background-color: #7d7e7d;
        }
        #fieldError {
            color: #ef5350;
            font-size: 13px;
            min-height: 1.2em;
            margin: 0 0 10px;
        }
        .form .message {
            margin: 15px 0 0;
            color: #b3b3b3;
            font-size: 12px;
        }

        .form select {
            font-family: inherit;
            background-color: #232323;
            color: white;
            width: 100%;
            border: 2px solid transparent;
            border-radius: 8px;
            margin: 0 0 15px;
            padding: 15px;
            font-size: 14px;
        }
        #password-rules {
            margin: 0 0 15px;
            padding: 0;
            list-style: none;
            font-size: 13px;
            text-align: left;
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

        /* Popups for the result of an application */
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
            font-family: Arial, sans-serif;
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
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOP Agent</title>
</head>
<body>
<?php renderPopups($err, $success); ?>

    <div class = "login-page">
        <div class = "form">
            <h1>Request an account</h1>
            <form method = "POST" id = "new-user-form">
                <input type = "text" placeholder = "username (your work email)" name = "name" id = "new-username" aria-label = "Username" maxlength = "50"
                       data-domains = "<?php echo htmlspecialchars(json_encode($orgDomains)); ?>" required autofocus>
                <input type = "password" placeholder = "password" name = "password" id = "new-password" aria-label = "Password" required>
                <ul id = "password-rules">
                    <li>At least 8 characters</li>
                    <li>At least one number</li>
                    <li>At least one lowercase letter</li>
                    <li>At least one uppercase letter</li>
                    <li>At least one special character</li>
                </ul>
                <input type = "password" placeholder = "confirm password" name = "confirmPassword" id = "confirm-password" aria-label = "Confirm password" required>
                <select name = "role" aria-label = "Role">
                    <option value = "user">User</option>
                    <option value = "admin">Admin</option>
                </select>
                <button type = "submit" name = "apply" value = "apply">Submit application</button>
                <p class = "message">An administrator reviews every application.</p>
                <p class = "message"><a href = "login.php" style = "color: #b3b3b3;">Back to log in</a></p>
            </form>
        </div>
    </div>

</body>
</html>
