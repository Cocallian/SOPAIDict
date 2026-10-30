<?php
    session_start();

    // Read the error set by dataconn.php, then clear it so it only shows once
    $err = $_SESSION["err"] ?? "";
    $_SESSION["err"] = "";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        /* Popup for login errors coming back from dataconn.php */
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
            font-family: Arial, sans-serif;
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
    <title>SOP Agent</title>
    <script src="validation.js?v=<?php echo filemtime(__DIR__ . "/validation.js"); ?>" defer></script>
</head>
<body>
    <?php if ($err !== ""): ?>
        <div id = "errPopup">
            <?php echo htmlspecialchars($err); ?>
            <button type = "button" id = "closePopup" aria-label = "Close">&times;</button>
        </div>
    <?php endif; ?>

    <div class = "login-page">
        <div class = "form">
            <h1>SOP Agent</h1>
            <form method = "POST" action = "dataconn.php" id = "loginForm" novalidate>
                <input type = "text" placeholder = "username" name = "uname" id = "uname" aria-label = "Username" autofocus>
                <input type = "password" placeholder = "password" name = "pass" id = "pass" aria-label = "Password">
                <div id = "fieldError"></div>
                <button type = "submit">Log In</button>
                <p class = "message">Need an account? <a href = "UserRequest.php">Ask your administrator.</a></p>
            </form>
        </div>
    </div>

</body>
</html>
