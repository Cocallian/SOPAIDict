<?php
// Makes sure the SOPAgent database, its tables and the cleanup event exist. Safe to run at every login.

// The database and every table
function createDatabaseAndTables(mysqli $conn): void
    {
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

        // A 1 to 5 rating (empty until the user picks one) and extra comments for a response, "N/A" when none were given
        $conn -> query("CREATE TABLE IF NOT EXISTS Feedback (
            FeedbackId    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            ResponseId    INT UNSIGNED NOT NULL,
            PromptId      INT UNSIGNED NOT NULL,
            Username      VARCHAR(50)  NOT NULL,
            Rating        TINYINT UNSIGNED NULL,
            ExtraFeedback TEXT         NULL,
            CreatedAt     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (FeedbackId),
            FOREIGN KEY (ResponseId) REFERENCES Responses(ResponseId) ON DELETE CASCADE,
            FOREIGN KEY (PromptId)   REFERENCES Prompts(PromptId)     ON DELETE CASCADE,
            CHECK (Rating BETWEEN 1 AND 5)
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
    }

// Every minute MySQL removes the rows nobody has refreshed for the inactivity timeout (FrontEndConfig.json) and writes an 'inactive' line to Logs for each.
// The event is dropped and made again so an older version of it is replaced.
function createCleanupEvent(mysqli $conn): void
    {
        try{
            $conn -> query("SET GLOBAL event_scheduler = ON;");
        }catch(mysqli_sql_exception $e){}

        try{
            $conn -> query("DROP EVENT IF EXISTS SOPAgent.CleanActiveUsers");
            $conn -> query("CREATE EVENT SOPAgent.CleanActiveUsers
                ON SCHEDULE EVERY 1 MINUTE
                DO
                BEGIN
                    DECLARE cutoff BIGINT DEFAULT UNIX_TIMESTAMP() - " . (int)round(inactivityMinutes() * 60) . ";

                    INSERT INTO SOPAgent.Logs (UserId, AdminId, Username, Role, Event, LoggedAt)
                        SELECT u.UserId, a.AdminId, s.Username, IF(LEFT(s.Username, 5) = 'admin', 'admin', 'user'), 'inactive', NOW()
                        FROM SOPAgent.ActiveUsers s
                        LEFT JOIN SOPAgent.Users u  ON u.Username = s.Username
                        LEFT JOIN SOPAgent.Admins a ON a.Username = s.Username
                        WHERE s.LastSeen < cutoff;

                    DELETE FROM SOPAgent.ActiveUsers WHERE LastSeen < cutoff;
                END");
        }catch(mysqli_sql_exception $e){}   // the MySQL login may not be allowed to make events, logging in still works
    }

// The first accounts, so there is something to log in with: "seed_accounts" in FrontEndConfig.json.
// Does nothing once that section is gone (removeSeedAccounts() takes it out), so deleted starting accounts don't come back.
function seedAccounts(mysqli $conn): void
    {
        $seed = readFrontEndConfig()["seed_accounts"] ?? null;
        foreach(["admin_user", "admin_pass", "user_user", "user_pass"] as $key)
            {
                if(!is_array($seed) || !is_string($seed[$key] ?? null) || $seed[$key] === "") { return; }
            }

        $adminUser = $seed["admin_user"];
        $adminHash = hash('sha256', $seed["admin_pass"]);
        $stmt = $conn -> prepare("INSERT IGNORE INTO Admins (Username, PasswordHash) VALUES (?, ?)");
        $stmt -> bind_param("ss", $adminUser, $adminHash);
        $stmt -> execute();

        $dumUser = $seed["user_user"];
        $userPass = hash("sha256", $seed["user_pass"]);
        $stmt2 = $conn -> prepare("INSERT IGNORE INTO Users (Username, PasswordHash) VALUES (?, ?)");
        $stmt2 -> bind_param("ss", $dumUser, $userPass);
        $stmt2 -> execute();
    }

// The starting accounts only exist to get the first real admin in. Once an admin other than the starting admin has logged in,
// both starting accounts are deleted (with their questions and answers, the tables cascade) and "seed_accounts", with its
// plain text passwords, is taken out of FrontEndConfig.json so they are never made again. $loggedIn is the admin who just logged in.
function removeSeedAccounts(mysqli $conn, string $loggedIn): void
    {
        $config = readFrontEndConfig();
        $seed = $config["seed_accounts"] ?? null;
        if(!is_array($seed)) { return; }

        $adminName = (string)($seed["admin_user"] ?? "");
        $userName = (string)($seed["user_user"] ?? "");
        if($loggedIn === $adminName || $loggedIn === $userName) { return; }   // the starting accounts themselves don't count

        try
            {
                foreach([["Admins", $adminName], ["Users", $userName]] as [$table, $name])   // fixed table names, never taken from the config
                    {
                        if($name === "") { continue; }
                        $stmt = $conn -> prepare("DELETE FROM $table WHERE Username = ?");
                        $stmt -> bind_param("s", $name);
                        $stmt -> execute();
                        $stmt -> close();

                        $stmt = $conn -> prepare("DELETE FROM ActiveUsers WHERE Username = ?");   // end any session still using it
                        $stmt -> bind_param("s", $name);
                        $stmt -> execute();
                        $stmt -> close();
                    }
            }
        catch(mysqli_sql_exception) { return; }   // couldn't delete them: leave the config alone and try again at the next admin login

        unset($config["seed_accounts"]);
        saveFrontEndConfig($config);
        $_SESSION["success"] = "The starting accounts were removed, " . $loggedIn . " is now the admin";
    }
