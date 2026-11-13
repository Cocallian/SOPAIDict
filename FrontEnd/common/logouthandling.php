<?php
// Everything about logging out, for the admin pages and the user page

// Ends the session: writes the "logout" (or "inactive") line to Logs, removes the ActiveUsers row, clears the session and
// goes to login.php. $reason is "inactive" when js/inactivity.js sent it because the inactivity time ran out,
// a click on Log out sends nothing.
function logOutSession($conn, string $uname, string $role, string $reason): void
    {
        writeLog($conn, $uname, $role, $reason === "inactive" ? "inactive" : "logout");
        try
            {
                $stmt = $conn->prepare("DELETE FROM ActiveUsers WHERE Username = ?");
                $stmt->bind_param("s", $uname);
                $stmt->execute();
                $stmt->close();
            }
        catch(mysqli_sql_exception) {}
        $_SESSION["perm"] = "";    // "" means not logged in, the same as dataconn.php uses
        $_SESSION["uname"] = "";
        header("Location: login.php");
        exit();
    }

// Handles the Log out button and the logout js/inactivity.js sends. Does nothing for any other request.
function handleLogoutRequest(?mysqli $conn, string $user, string $role): void
    {
        if(!array_key_exists('logout', $_POST)) { return; }

        if($conn) { logOutSession($conn, $user, $role, $_POST["reason"] ?? ""); }
        $_SESSION["perm"] = "";    // no database to tidy up, just log out
        $_SESSION["uname"] = "";
        header("Location: login.php");
        exit();
    }
