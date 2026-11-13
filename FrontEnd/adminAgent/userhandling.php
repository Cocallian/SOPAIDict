<?php
// The Add new user, Delete User and Active users cards of the dashboard

// Live user search for the Delete User card, answers with a JSON list of usernames and ends the request
function handleUserSearch(?mysqli $conn): void
    {
        if(!array_key_exists('search-user', $_POST)) { return; }

        $names = [];
        $term = trim($_POST["search-user"]);
        if($conn && $term !== "")
            {
                $like = "%" . addcslashes($term, "%_\\") . "%";
                $stmt = $conn->prepare("(SELECT Username, 'user' AS Role FROM Users WHERE Username LIKE ?)
                                        UNION ALL
                                        (SELECT Username, 'admin' AS Role FROM Admins WHERE Username LIKE ?)
                                        ORDER BY Username LIMIT 20");
                $stmt->bind_param("ss", $like, $like);
                $stmt->execute();
                $stmt->bind_result($found, $foundRole);
                while($stmt->fetch()) { $names[] = ["name" => $found, "role" => $foundRole]; }
                $stmt->close();
            }
        header("Content-Type: application/json");
        echo json_encode($names);
        exit();
    }

// The Add new user form. Checks the new account (common/validation.php), adds it and reloads the dashboard.
function handleAddUser(?mysqli $conn, array $orgDomains): void
    {
        if(!array_key_exists('add-user', $_POST) || !$conn) { return; }

        $newName = trim($_POST["name"] ?? "");
        $pw = $_POST["password"] ?? "";
        $newHash = hash("sha256", $pw);
        $isAdmin = ($_POST["role"] ?? "") === "admin";

        $problem = accountProblem($newName, $pw, $_POST["confirmPassword"] ?? "", $isAdmin, $orgDomains);
        if($problem !== "")
            {
                $_SESSION["err"] = $problem;
            }
        else
            {
                try
                    {
                        $stmt = $conn->prepare($isAdmin ? "INSERT INTO Admins (Username, PasswordHash) VALUES (?, ?)"
                                                        : "INSERT INTO Users (Username, PasswordHash) VALUES (?, ?)");
                        $stmt->bind_param("ss", $newName, $newHash);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION["success"] = ($isAdmin ? "Admin " : "User ") . $newName . " added";
                    }
                catch(mysqli_sql_exception $e)
                    {
                        $_SESSION["err"] = $e->getCode() == 1062 ? "The username " . $newName . " already exists"
                                                                 : "The user could not be added to the database";
                    }
            }

        // Reload as a normal GET so refreshing the page doesn't add the user again
        header("Location: adminAgent.php");
        exit();
    }

// The user (or admin) picked in the Delete User card. The dashboard is shown again afterwards.
function handleDeleteUser(?mysqli $conn): void
    {
        if(!array_key_exists('del-user', $_POST) || !$conn) { return; }

        $dname = trim($_POST["dname"]);
        $dIsAdmin = ($_POST["drole"] ?? "") === "admin";
        $dtable = $dIsAdmin ? "Admins" : "Users";    // only ever one of these two, never taken from the form directly
        $dlabel = $dIsAdmin ? "Admin " : "User ";

        try
            {
                $stmt = $conn->prepare("SELECT 1 FROM $dtable WHERE Username = ?");
                $stmt->bind_param("s", $dname);
                $stmt->execute();
                $stmt->store_result();
                $count = $stmt->num_rows;
                $stmt->close();

                if($count < 1)
                    {
                        $_SESSION["err"] = "No user found with that name";
                    }
                elseif($count > 1)
                    {
                        $_SESSION["err"] = "More than one user with that name, delete it manually via sql statement in MySQL workbench";
                    }
                elseif($dIsAdmin && $dname === ($_SESSION["uname"] ?? ""))
                    {
                        $_SESSION["err"] = "You can't delete the admin account you're logged in with";
                    }
                else
                    {
                        try
                            {
                                $stmt = $conn->prepare("DELETE FROM $dtable WHERE Username = ?");
                                $stmt->bind_param("s", $dname);
                                $stmt->execute();
                                $stmt->close();
                                $_SESSION["success"] = $dlabel . $dname . " deleted";
                            }
                        catch(mysqli_sql_exception $e)
                            {
                                $_SESSION["err"] = "Database connection error: " . $e;
                            }
                    }
            }
        catch(mysqli_sql_exception $e)
            {
                $_SESSION["err"] = "Database connection error: " . $e;
            }
    }

// The Log out button in the Active users card: removes that session's row from ActiveUsers, so the next page it
// opens sends it to Inactive.php, and writes a "logout" line to Logs
function handleForceLogout(?mysqli $conn): void
    {
        if(!array_key_exists('force-logout', $_POST) || !$conn) { return; }

        $sid = $_POST["sid"] ?? "";
        if($sid === session_id()) { $_SESSION["err"] = "You can't log yourself out here, use the Log out button"; }
        else
            {
                try
                    {
                        $stmt = $conn->prepare("SELECT Username FROM ActiveUsers WHERE SessionId = ?");
                        $stmt->bind_param("s", $sid);
                        $stmt->execute();
                        $stmt->bind_result($who);
                        $found = $stmt->fetch();
                        $stmt->close();

                        if($found)
                            {
                                writeLog($conn, $who, substr($who, 0, 5) == "admin" ? "admin" : "user", "logout");
                                $stmt = $conn->prepare("DELETE FROM ActiveUsers WHERE SessionId = ?");
                                $stmt->bind_param("s", $sid);
                                $stmt->execute();
                                $stmt->close();
                                $_SESSION["success"] = $who . " was logged out";
                            }
                        else { $_SESSION["err"] = "That user is no longer logged in"; }
                    }
                catch(mysqli_sql_exception) { $_SESSION["err"] = "The user could not be logged out"; }
            }
        header("Location: adminAgent.php");
        exit();
    }
