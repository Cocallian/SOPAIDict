<?php
// The log in itself: read what was typed, check it against the database and send the person to their page

// Sends the person back to the log in page with a message
function refuseLogin(string $message): void
    {
        $_SESSION["err"] = $message;
        header("Location: login.php");
        exit;
    }

// The username and the hashed password from the log in form. Sends the person back when either box was left empty.
function readLoginForm(): array
    {
        if (isset($_POST["uname"]) && isset($_POST["pass"]) && trim($_POST["uname"]) != "" && trim($_POST["pass"]) != "")
            {
                $_SESSION["username"] = $_POST["uname"];
                return [$_POST["uname"], hash("sha256", $_POST["pass"])];
            }
            else{refuseLogin("Please enter details into the fields for log in validation");}
        
    }

// Stores the session's lastseen time in $_SESSION and posts it to ActiveUsers
function recordLastSeen($conn, $uname)
    {
        $_SESSION["LastSeen"] = time();   // same key and format Agent.php sets
        $sid = session_id();
        $seen = $_SESSION["LastSeen"];
        $stmt = $conn -> prepare("INSERT INTO ActiveUsers (SessionId, Username, LastSeen) VALUES (?, ?, ?)
                                  ON DUPLICATE KEY UPDATE Username = VALUES(Username), LastSeen = VALUES(LastSeen)");
        $stmt -> bind_param("sss", $sid, $uname, $seen);
        $stmt -> execute();
        $stmt -> close();
    }

// Logs in an admin (a username starting with "admin") or a user. Either way the person is sent on, or back to log in with a message.
function attemptLogin(mysqli $conn, string $uname, string $pass): void
    {
        $isAdmin = substr($uname, 0, 5) == "admin";
        $table = $isAdmin ? "Admins" : "Users";          // fixed names, never taken from the form
        $role = $isAdmin ? "admin" : "user";

        $stmt = $conn -> prepare("SELECT * FROM $table WHERE Username = ? AND PasswordHash = ?");
        $stmt -> bind_param("ss", $uname, $pass);
        $stmt -> execute();
        $stmt -> store_result();

        $active = $conn -> prepare("SELECT * FROM ActiveUsers WHERE Username = ?");
        $active -> bind_param("s", $uname);
        $active -> execute();
        $active -> store_result();

        if($stmt -> num_rows() < 1)
            {
                refuseLogin("Incorrect Login details");
            }
        if($active -> num_rows() > 0)
            {
                refuseLogin("Already logged in on another device");
            }

        session_regenerate_id(true);   // a new session id for the logged in person, so one handed out before the login is useless
        unset($_SESSION["csrf"]);      // and a new CSRF token with it
        $_SESSION["perm"] = $role;
        $_SESSION["uname"] = $uname;   // used to look up who sent each prompt
        $_SESSION["state"] = "active";
        recordLastSeen($conn, $uname);
        writeLog($conn, $uname, $role, "login");
        if($isAdmin) { removeSeedAccounts($conn, $uname); }   // the first real admin to log in replaces the starting accounts
        header("Location: " . ($isAdmin ? "adminAgent.php" : "Agent.php"));
        exit;
    }
