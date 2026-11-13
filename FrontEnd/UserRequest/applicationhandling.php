<?php
// Submit application button: checks the account that is asked for (common/validation.php) and saves it for an admin to review

// Makes sure the table the applications go into exists, in case nobody has logged in yet (dataconn.php makes the same table)
function createApplicationTable(mysqli $conn): void
    {
        $conn->query("CREATE DATABASE IF NOT EXISTS SOPAgent");
        $conn->select_db("SOPAgent");
        $conn->query("CREATE TABLE IF NOT EXISTS UserApplication (
            ApplicationId INT UNSIGNED NOT NULL AUTO_INCREMENT,
            Username      VARCHAR(50)  NOT NULL UNIQUE,
            PasswordHash  VARCHAR(255) NOT NULL,
            SubmittedAt   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            Role          ENUM('user','admin') NOT NULL DEFAULT 'user',
            PRIMARY KEY (ApplicationId)
        ) ENGINE=InnoDB");
    }

// A name can't be applied for if it is already taken or already waiting
function usernameTaken(mysqli $conn, string $name): bool
    {
        $taken = false;
        foreach(["Users", "Admins", "UserApplication"] as $table)
            {
                $stmt = $conn->prepare("SELECT 1 FROM $table WHERE Username = ?");
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $stmt->store_result();
                if($stmt->num_rows > 0) { $taken = true; }
                $stmt->close();
            }
        return $taken;
    }

// Handles the form, then reloads the page as a normal GET so refreshing doesn't send the application again.
// Does nothing for any other request.
function handleApplicationRequest(array $secrets, array $orgDomains): void
    {
        if(!array_key_exists('apply', $_POST)) { return; }

        $newName = trim($_POST["name"] ?? "");
        $pw = $_POST["password"] ?? "";
        $isAdmin = ($_POST["role"] ?? "") === "admin";

        $problem = accountProblem($newName, $pw, $_POST["confirmPassword"] ?? "", $isAdmin, $orgDomains, true);
        if($problem !== "")
            {
                $_SESSION["err"] = $problem;
            }
        else
            {
                try
                    {
                        $conn = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"]);
                        createApplicationTable($conn);

                        if(usernameTaken($conn, $newName))
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

        header("Location: UserRequest.php");
        exit();
    }
