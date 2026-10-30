<?php
    session_start();
    $secrets = parse_ini_file(__DIR__ . "/passwords.txt");   // MySQL login (kept out of the code)
    // Every value under [org_name] in passwords.txt is an allowed email domain, whatever its key is called
    require __DIR__ . "/orgdomains.php";
    $orgDomains = loadOrgDomains();

    if(array_key_exists('apply', $_POST))
        {
            $newName = trim($_POST["name"] ?? "");
            $pw = $_POST["password"] ?? "";
            $isAdmin = ($_POST["role"] ?? "") === "admin";

            $validOrg = validOrgUsername($newName, $orgDomains);
            $strongPw = preg_match('/.{8,}/', $pw) && preg_match('/[0-9]/', $pw) && preg_match('/[a-z]/', $pw)
                     && preg_match('/[A-Z]/', $pw) && preg_match('/[^A-Za-z0-9]/', $pw);

            if(!$validOrg)
                {
                    $_SESSION["err"] = "The username must be an email ending in: " . implode(", ", $orgDomains);
                }
            else if(strlen($newName) > 50)
                {
                    $_SESSION["err"] = "The username can be at most 50 characters";
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
                            $conn = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"]);
                            $conn->query("CREATE DATABASE IF NOT EXISTS SOPAgent");
                            $conn->select_db("SOPAgent");
                            // same table dataconn.php creates, in case nobody has logged in yet
                            $conn->query("CREATE TABLE IF NOT EXISTS UserApplication (
                                ApplicationId INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                Username      VARCHAR(50)  NOT NULL UNIQUE,
                                PasswordHash  VARCHAR(255) NOT NULL,
                                SubmittedAt   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                Role          ENUM('user','admin') NOT NULL DEFAULT 'user',
                                PRIMARY KEY (ApplicationId)
                            ) ENGINE=InnoDB");

                            // a name can't be applied for if it is already taken or already waiting
                            $taken = false;
                            foreach(["Users", "Admins", "UserApplication"] as $table)
                                {
                                    $stmt = $conn->prepare("SELECT 1 FROM $table WHERE Username = ?");
                                    $stmt->bind_param("s", $newName);
                                    $stmt->execute();
                                    $stmt->store_result();
                                    if($stmt->num_rows > 0) { $taken = true; }
                                    $stmt->close();
                                }

                            if($taken)
                                {
                                    $_SESSION["err"] = "The username " . $newName . " is already taken or already has an application waiting";
                                }
                            else
                                {
                                    $hash = hash("sha256", $pw);
                                    $role = $isAdmin ? "admin" : "user";
                                    $stmt = $conn->prepare("INSERT INTO UserApplication (Username, PasswordHash, Role) VALUES (?, ?, ?)");
                                    $stmt->bind_param("sss", $newName, $hash, $role);
                                    $stmt->execute();
                                    $stmt->close();
                                    $_SESSION["success"] = "Application submitted, an administrator will review it";
                                }
                        }
                    catch(mysqli_sql_exception)
                        {
                            $_SESSION["err"] = "Database connection error, contact your system administrator for assistance";
                        }
                }

            // Reload as a normal GET so refreshing the page doesn't send the application again
            header("Location: UserRequest.php");
            exit();
        }

    // Read the messages set above, then clear them so they only show once
    $err = $_SESSION["err"] ?? "";
    $_SESSION["err"] = "";
    $success = $_SESSION["success"] ?? "";
    $_SESSION["success"] = "";

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

    <?php if ($success !== ""): ?>
        <div id = "successPopup">
            <?php echo htmlspecialchars($success); ?>
            <button type = "button" id = "closeSuccess" aria-label = "Close">&times;</button>
        </div>
    <?php endif; ?>

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
