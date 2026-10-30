<?php
session_start();
$secrets = parse_ini_file(__DIR__ . "/passwords.txt");   // MySQL login and Python paths (kept out of the code)
require __DIR__ . "/activeusers.php";   // writeLog()
$_SESSION["perm"] = "";
$uname = "";
$pass = "";
if (isset($_POST["uname"]) && isset($_POST["pass"]) && trim($_POST["uname"]) != "" && trim($_POST["pass"]) != "")
    {
        $uname = $_POST["uname"];
        $_SESSION["username"] = $uname;
        $pass = hash("sha256", $_POST["pass"]);
    }
    else{
         $_SESSION["err"] = "Please enter details into the fields for log in validation";
         header("Location: login.php");
         exit;
    }

    try
        {
            $conn = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"]);
        }
        catch(mysqli_sql_exception)
        {
            $_SESSION["err"] = "Database connection error, contact your system adminstrator for assistance";            
            header("Location: login.php");
            exit;
        }

    
    

if($conn->connect_error)
    {
        $_SESSION["err"] = "Database connection error, contact your system adminstrator for assistance";
        header("Location: login.php");
        exit;
    }else{

    $conn -> query("CREATE DATABASE IF NOT EXISTS SOPAgent");
    $conn -> select_db("SOPAgent");

    $conn -> query("CREATE TABLE IF NOT EXISTS Admins (
        AdminId      INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Username     VARCHAR(50)  NOT NULL UNIQUE,
        PasswordHash VARCHAR(255) NOT NULL,
        PRIMARY KEY (AdminId)
    ) ENGINE=InnoDB");

    $conn -> query("CREATE TABLE IF NOT EXISTS Users (
        UserId       INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Username     VARCHAR(50)  NOT NULL UNIQUE,
        PasswordHash VARCHAR(255) NOT NULL,
        PRIMARY KEY (UserId)
    ) ENGINE=InnoDB");

    
    $conn -> query("CREATE TABLE IF NOT EXISTS Prompts (
        PromptId   INT UNSIGNED NOT NULL AUTO_INCREMENT,
        UserId     INT UNSIGNED NULL,
        AdminId    INT UNSIGNED NULL,
        PromptText TEXT         NOT NULL,
        CreatedAt  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (PromptId),
        FOREIGN KEY (UserId)  REFERENCES Users(UserId)   ON DELETE CASCADE,
        FOREIGN KEY (AdminId) REFERENCES Admins(AdminId) ON DELETE CASCADE,
        CHECK ((UserId IS NULL) <> (AdminId IS NULL))
    ) ENGINE=InnoDB");

    $conn -> query("CREATE TABLE IF NOT EXISTS Responses (
        ResponseId   INT UNSIGNED NOT NULL AUTO_INCREMENT,
        PromptId     INT UNSIGNED NOT NULL,
        UserId       INT UNSIGNED NULL,
        AdminId      INT UNSIGNED NULL,
        ResponseText MEDIUMTEXT   NOT NULL,
        CreatedAt    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (ResponseId),
        FOREIGN KEY (PromptId) REFERENCES Prompts(PromptId) ON DELETE CASCADE,
        FOREIGN KEY (UserId)   REFERENCES Users(UserId)     ON DELETE CASCADE,
        FOREIGN KEY (AdminId)  REFERENCES Admins(AdminId)   ON DELETE CASCADE,
        CHECK ((UserId IS NULL) <> (AdminId IS NULL))
    ) ENGINE=InnoDB");

    // The documents each answer was sourced from, one row per document per prompt
    $conn -> query("CREATE TABLE IF NOT EXISTS SourcedDocuments (
        SourceId   INT UNSIGNED NOT NULL AUTO_INCREMENT,
        PromptId   INT UNSIGNED NOT NULL,
        UserId     INT UNSIGNED NULL,
        AdminId    INT UNSIGNED NULL,
        Title      VARCHAR(255) NOT NULL,
        SourcedAt  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (SourceId),
        FOREIGN KEY (PromptId) REFERENCES Prompts(PromptId) ON DELETE CASCADE,
        FOREIGN KEY (UserId)   REFERENCES Users(UserId)     ON DELETE CASCADE,
        FOREIGN KEY (AdminId)  REFERENCES Admins(AdminId)   ON DELETE CASCADE,
        CHECK ((UserId IS NULL) <> (AdminId IS NULL))
    ) ENGINE=InnoDB");

    // Every login, logout and inactivity logout. The link to Users / Admins is set to NULL when the account is deleted,
    // so the history stays; Username and Role are kept on every line for that reason
    $conn -> query("CREATE TABLE IF NOT EXISTS Logs (
        LogId     INT UNSIGNED NOT NULL AUTO_INCREMENT,
        UserId    INT UNSIGNED NULL,
        AdminId   INT UNSIGNED NULL,
        Username  VARCHAR(50)  NOT NULL,
        Role      ENUM('user','admin') NOT NULL,
        Event     ENUM('login','logout','inactive') NOT NULL,
        LoggedAt  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (LogId),
        FOREIGN KEY (UserId)  REFERENCES Users(UserId)   ON DELETE SET NULL,
        FOREIGN KEY (AdminId) REFERENCES Admins(AdminId) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    // Sign-up requests waiting for an admin to approve or reject them (shown on adminAgent.php)
    $conn -> query("CREATE TABLE IF NOT EXISTS UserApplication (
        ApplicationId INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Username      VARCHAR(50)  NOT NULL UNIQUE,
        PasswordHash  VARCHAR(255) NOT NULL,
        SubmittedAt   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        Role          ENUM('user','admin') NOT NULL DEFAULT 'user',
        PRIMARY KEY (ApplicationId)
    ) ENGINE=InnoDB");

    // One row per logged in session, so the same username showing up twice means it is signed in in two places
    $conn -> query("CREATE TABLE IF NOT EXISTS ActiveUsers (
        SessionId VARCHAR(128) NOT NULL,
        Username  VARCHAR(50)  NOT NULL,
        LastSeen  BIGINT UNSIGNED  NOT NULL,
        PRIMARY KEY (SessionId)
    ) ENGINE=InnoDB");
    try{
        $conn -> query("SET GLOBAL event_scheduler = ON;");
    }catch(mysqli_sql_exception $e){}

    

    // Every minute MySQL removes the rows nobody has refreshed for 15 minutes and writes an 'inactive' line to Logs for each.
    // The event is dropped and made again so an older version of it is replaced.
    try{
        $conn -> query("DROP EVENT IF EXISTS SOPAgent.CleanActiveUsers");
        $conn -> query("CREATE EVENT SOPAgent.CleanActiveUsers
            ON SCHEDULE EVERY 1 MINUTE
            DO
            BEGIN
                DECLARE cutoff BIGINT DEFAULT UNIX_TIMESTAMP() - 900;

                INSERT INTO SOPAgent.Logs (UserId, AdminId, Username, Role, Event, LoggedAt)
                    SELECT u.UserId, a.AdminId, s.Username, IF(LEFT(s.Username, 5) = 'admin', 'admin', 'user'), 'inactive', NOW()
                    FROM SOPAgent.ActiveUsers s
                    LEFT JOIN SOPAgent.Users u  ON u.Username = s.Username
                    LEFT JOIN SOPAgent.Admins a ON a.Username = s.Username
                    WHERE s.LastSeen < cutoff;

                DELETE FROM SOPAgent.ActiveUsers WHERE LastSeen < cutoff;
            END");
    }catch(mysqli_sql_exception $e){}   // the MySQL login may not be allowed to make events, logging in still works

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

    //Delete this code after first run or if you manually set admin details
    $adminUser = 'admin';
    $adminHash = hash('sha256', "123");
    $stmt = $conn -> prepare("INSERT IGNORE INTO Admins (Username, PasswordHash) VALUES (?, ?)");
    $stmt -> bind_param("ss", $adminUser, $adminHash);
    $stmt -> execute();

    $dumUser = "user";
    $userPass = hash("sha256", "123");
    $stmt2 = $conn -> prepare("INSERT IGNORE INTO Users (Username, PasswordHash) VALUES (?, ?)");
    $stmt2 -> bind_param("ss", $dumUser, $userPass);
    $stmt2 -> execute();

    if(substr($uname, 0, 5) == "admin")
        {
            $stmt = $conn -> prepare("SELECT * FROM Admins WHERE Username = ? AND PasswordHash = ?");
            $stmt -> bind_param("ss", $uname, $pass);
            $stmt -> execute();
            $stmt -> store_result();

            $active = $conn -> prepare("SELECT * FROM ActiveUsers WHERE Username = ?");
            $active -> bind_param("s", $uname);
            $active -> execute();
            $active -> store_result();

            
            if($stmt -> num_rows() < 1)
                {
                    $_SESSION["err"] = "Incorrect Login details";
                    header("Location: login.php");
                    exit;
                }
                elseif($active->num_rows() > 0){
                    $_SESSION["err"] = "Already logged in on another device";
                    header("Location: login.php");
                    exit;
                }else{
                    $_SESSION["perm"] = "admin";
                    $_SESSION["uname"] = $uname;
                    $_SESSION["state"] = "active"; 
                    recordLastSeen($conn, $uname);
                    writeLog($conn, $uname, "admin", "login");
                    header("Location: adminAgent.php");
                    exit;
                }
        }
        else{
            $stmt2 = $conn -> prepare("SELECT * FROM Users WHERE Username = ? AND PasswordHash = ?");
            $stmt2 -> bind_param("ss", $uname, $pass);
            $stmt2 -> execute();
            $stmt2 -> store_result();

            $active = $conn -> prepare("SELECT * FROM ActiveUsers WHERE Username = ?");
            $active -> bind_param("s", $uname);
            $active -> execute();
            $active -> store_result();

                if($stmt2 -> num_rows() < 1)
                {
                    $_SESSION["err"] = "Incorrect Login details";
                    header("Location: login.php");
                    exit;
                }
                elseif($active->num_rows() > 0){
                    $_SESSION["err"] = "Already logged in on another device";
                    header("Location: login.php");
                    exit;
                }else{
                    $_SESSION["perm"] = "user";
                    $_SESSION["uname"] = $uname;
                    $_SESSION["state"] = "active";   // used to look up who sent each prompt
                    recordLastSeen($conn, $uname);
                    writeLog($conn, $uname, "user", "login");
                    header("Location: Agent.php");
                    exit;
                }
        }

    


    }


    

?>
<html>
    <br>
    <h3>How did you get here?</h3>
</br>
<a href = "login.php"><button >Go Back</button></a>
</html>
