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
        form{
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        input{
            width: 28vw;
            height: 10vh;
        }
        input.invalid{
            border: 2px solid #d32f2f;
        }
        #fieldError{
            color: #d32f2f;
            min-height: 1.2em;
        }

        /* Popup for login errors coming back from dataconn.php */
        #errPopup{
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #d32f2f;
            color: #fff;
            padding: 14px 44px 14px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            font-family: sans-serif;
            z-index: 1000;
        }
        #errPopup.hidden{
            display: none;
        }
        #errPopup button{
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
    <title>Document</title>
</head>
<body>
    <?php if ($err !== ""): ?>
        <div id = "errPopup">
            <?php echo htmlspecialchars($err); ?>
            <button type = "button" id = "closePopup" aria-label = "Close">&times;</button>
        </div>
    <?php endif; ?>

    <form method = "POST" action = "dataconn.php" id = "loginForm" novalidate>
        <label for = "uname">Username:</label>
        <input type = "text" placeholder="username" name = "uname" id = "uname">
        <label for = "pass">Password:</label>
        <input type = "password" placeholder="password" name = "pass" id = "pass">
        <div id = "fieldError"></div>
        <button type = "submit">Log In</button>
    </form>

<script>
    const form = document.getElementById("loginForm");
    const uname = document.getElementById("uname");
    const pass = document.getElementById("pass");
    const fieldError = document.getElementById("fieldError");

    // Block submission unless both fields have a non-blank value
    form.addEventListener("submit", function (e) {
        let valid = true;

        [uname, pass].forEach(function (input) {
            if (input.value == null || input.value.trim() === "") {
                input.classList.add("invalid");
                valid = false;
            } else {
                input.classList.remove("invalid");
            }
        });

        if (!valid) {
            e.preventDefault();
            fieldError.textContent = "Please enter your username and password.";
        } else {
            fieldError.textContent = "";
        }
    });

    // Clear the error for a field as soon as the user types in it
    [uname, pass].forEach(function (input) {
        input.addEventListener("input", function () {
            if (input.value.trim() !== "") {
                input.classList.remove("invalid");
            }
        });
    });

    // Close the popup on click, or automatically after 5 seconds
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
