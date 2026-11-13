<?php
// The Applications card of the dashboard: account requests from UserRequest.php waiting to be approved or rejected

// Approve or reject a request, then reload the dashboard. Approving adds the account with the hash the request was saved with.
function handleApplication(?mysqli $conn): void
    {
        if(!array_key_exists('app-action', $_POST) || !$conn) { return; }

        $appId = (int)($_POST["app-id"] ?? 0);
        try
            {
                $stmt = $conn->prepare("SELECT Username, PasswordHash, Role FROM UserApplication WHERE ApplicationId = ?");
                $stmt->bind_param("i", $appId);
                $stmt->execute();
                $stmt->bind_result($appName, $appHash, $appRole);
                $appFound = $stmt->fetch();
                $stmt->close();

                if(!$appFound)
                    {
                        $_SESSION["err"] = "That application no longer exists";
                    }
                else if($_POST["app-action"] === "approve")
                    {
                        $appIsAdmin = $appRole === "admin";
                        // dataconn.php decides admin or user by the username, so the name has to match the role
                        if($appIsAdmin && substr($appName, 0, 5) !== "admin")
                            {
                                $_SESSION["err"] = "Admin usernames must start with \"admin\", reject this application or have it resubmitted";
                            }
                        else if(!$appIsAdmin && substr($appName, 0, 5) === "admin")
                            {
                                $_SESSION["err"] = "User usernames can't start with \"admin\", reject this application or have it resubmitted";
                            }
                        else
                            {
                                $conn->begin_transaction();
                                $stmt = $conn->prepare($appIsAdmin ? "INSERT INTO Admins (Username, PasswordHash) VALUES (?, ?)"
                                                                   : "INSERT INTO Users (Username, PasswordHash) VALUES (?, ?)");
                                $stmt->bind_param("ss", $appName, $appHash);
                                $stmt->execute();
                                $stmt->close();
                                $stmt = $conn->prepare("DELETE FROM UserApplication WHERE ApplicationId = ?");
                                $stmt->bind_param("i", $appId);
                                $stmt->execute();
                                $stmt->close();
                                $conn->commit();
                                $_SESSION["success"] = ($appIsAdmin ? "Admin " : "User ") . $appName . " approved and added";
                            }
                    }
                else
                    {
                        $stmt = $conn->prepare("DELETE FROM UserApplication WHERE ApplicationId = ?");
                        $stmt->bind_param("i", $appId);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION["success"] = "Application from " . $appName . " rejected";
                    }
            }
        catch(mysqli_sql_exception $e)
            {
                $conn->rollback();
                $_SESSION["err"] = $e->getCode() == 1062 ? "The username " . $appName . " already exists, reject this application"
                                                         : "The application could not be processed";
            }

        // Reload as a normal GET so refreshing the page doesn't process it again
        header("Location: adminAgent.php");
        exit();
    }

// Pending sign-up requests, oldest first
function loadApplications(?mysqli $conn): array
    {
        $applications = [];
        if($conn)
            {
                try
                    {
                        $res = $conn->query("SELECT ApplicationId, Username, Role, SubmittedAt FROM UserApplication ORDER BY SubmittedAt");
                        while($row = $res->fetch_assoc()) { $applications[] = $row; }
                    }
                catch(mysqli_sql_exception) {}   // table is created the next time anyone logs in via dataconn.php
            }
        return $applications;
    }
