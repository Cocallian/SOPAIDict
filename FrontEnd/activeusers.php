<?php
// Keeps the ActiveUsers table (one row per logged in session, written by dataconn.php) in step with the pages.

// Run on every load of a logged in page: refreshes this session's LastSeen, or logs the session out
// when its row is gone (the MySQL event deletes rows nobody has refreshed for 15 minutes)
function touchActiveUser($conn, $uname)
    {
        $sid = session_id();
        try
            {
                $stmt = $conn->prepare("SELECT 1 FROM ActiveUsers WHERE SessionId = ?");
                $stmt->bind_param("s", $sid);
                $stmt->execute();
                $stmt->store_result();
                $known = $stmt->num_rows > 0;
                $stmt->close();

                if(!$known)
                    {
                        $_SESSION["perm"] = "";
                        $_SESSION["uname"] = "";
                        header("Location: Inactive.php");
                        exit();
                    }

                $stmt = $conn->prepare("UPDATE ActiveUsers SET LastSeen = UNIX_TIMESTAMP(), Username = ? WHERE SessionId = ?");
                $stmt->bind_param("ss", $uname, $sid);
                $stmt->execute();
                $stmt->close();
            }
        catch(mysqli_sql_exception) {}   // table missing: don't lock everyone out, dataconn.php creates it at the next login
    }

// Adds a line to the Logs table. $role is "admin" or "user", $event is "login", "logout" or "inactive".
// The line is linked to the Users or Admins row when it still exists, the name and role are kept either way.
function writeLog($conn, $uname, $role, $event)
    {
        $isAdmin = $role === "admin";
        $table = $isAdmin ? "Admins" : "Users";        // fixed names, never taken from the request
        $idColumn = $isAdmin ? "AdminId" : "UserId";
        $role = $isAdmin ? "admin" : "user";
        try
            {
                $stmt = $conn->prepare("INSERT INTO Logs ($idColumn, Username, Role, Event, LoggedAt)
                                        SELECT $idColumn, Username, ?, ?, NOW() FROM $table WHERE Username = ?");
                $stmt->bind_param("sss", $role, $event, $uname);
                $stmt->execute();
                $linked = $stmt->affected_rows;
                $stmt->close();

                if($linked < 1)   // the account is already deleted, keep the line without a link
                    {
                        $stmt = $conn->prepare("INSERT INTO Logs (Username, Role, Event, LoggedAt) VALUES (?, ?, ?, NOW())");
                        $stmt->bind_param("sss", $uname, $role, $event);
                        $stmt->execute();
                        $stmt->close();
                    }
            }
        catch(mysqli_sql_exception) {}   // a missing Logs table must never stop someone logging in or out
    }

// Ends the session: writes the "logout" (or "inactive") line to Logs, removes the ActiveUsers row, clears the session and
// goes to login.php. $reason is "inactive" when validation.js sent it because the 15 minutes ran out.
function logOutSession($conn, $uname, $role, $reason)
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
