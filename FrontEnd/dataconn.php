<?php
session_start();
$secrets = parse_ini_file(__DIR__ . "/passwords.txt");

$PYTH = "C:\\Users\\tygra\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
$SCRIPT = "C:\\Users\\tygra\\Documents\\Code\\Pythom\\LLM\\LocalAITest.py";

// Gets the answer from the Colab notebook when colab_url is set in passwords.txt,
// otherwise (or when Colab can't be reached) from LocalAITest.py on this PC
function ask_model($q, $colabUrl, &$err)
    {
        global $PYTH, $SCRIPT;

        if($colabUrl !== "")
            {
                $context = stream_context_create(["http" => [
                    "method"        => "POST",
                    "header"        => "Content-Type: application/x-www-form-urlencoded",
                    "content"       => http_build_query(["question" => $q]),
                    "timeout"       => 300,
                    "ignore_errors" => true,   // still read the reply when Colab sends an error code
                ]]);
                $reply = @file_get_contents($colabUrl, false, $context);
                $data = $reply ? json_decode($reply, true) : null;
                if(isset($data["answer"]))
                    {
                        return $data["answer"];
                    }
                $err = "The Colab notebook couldn't be reached, so the local model answered instead";
            }

        $command = escapeshellarg($PYTH) . " " . escapeshellarg($SCRIPT) . " " . escapeshellarg($q) . " 2>&1"; //executes the RAG pipeline via command line
        return shell_exec($command) ?? "";
    }

// ---- Questions posted from Agent.php or adminAgent.php ----
if(isset($_POST["user-input"]))
    {
        $role = $_SESSION["perm"] ?? "";
        if($role != "user" && $role != "admin")
            {
                header("Location: login.php");
                exit;
            }
        $back = $role == "admin" ? "adminAgent.php" : "Agent.php";

        $q = trim($_POST["user-input"]);
        if($q === "")
            {
                header("Location: " . $back);
                exit;
            }

        set_time_limit(300);   // finding passages + Qwen's answer can take over a minute
        $uname = $_SESSION["uname"] ?? "";
        session_write_close(); // release the session so the user's other pages don't freeze while the model works

        // admins' prompts go in the AdminId column, users' in the UserId column
        $table  = $role == "admin" ? "Admins" : "Users";
        $column = $role == "admin" ? "AdminId" : "UserId";
        $err = "";
        $promptId = null;

        // save the prompt with the current date and time before the model starts working on it
        try
            {
                $conn = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"], "SOPAgent");
                $stmt = $conn->prepare("SELECT $column FROM $table WHERE Username = ?");
                $stmt->bind_param("s", $uname);
                $stmt->execute();
                $stmt->bind_result($ownerId);
                $stmt->fetch();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO Prompts ($column, PromptText, CreatedAt) VALUES (?, ?, NOW())");
                $stmt->bind_param("is", $ownerId, $q);
                $stmt->execute();
                $promptId = $conn->insert_id;
                $stmt->close();
            }
        catch(mysqli_sql_exception)
            {
                $err = "Your prompt could not be saved to the database";
            }

        $a = ask_model($q, $secrets["colab_url"] ?? "", $err);   // blocks here until the answer is back

        // the response is back: save it against the prompt, again with the current date and time
        if($promptId && trim($a) !== "")
            {
                try
                    {
                        $stmt = $conn->prepare("INSERT INTO Responses (PromptId, $column, ResponseText, CreatedAt) VALUES (?, ?, ?, NOW())");
                        $stmt->bind_param("iis", $promptId, $ownerId, $a);
                        $stmt->execute();
                        $stmt->close();
                    }
                catch(mysqli_sql_exception)
                    {
                        $err = "The response could not be saved to the database";
                    }
            }

        // hand the answer back to the page that asked
        session_start();
        $_SESSION["answer"] = $a;
        if($err !== "")
            {
                $_SESSION["err"] = $err;
            }
        header("Location: " . $back);
        exit;
    }

// ---- Logins posted from login.php ----
$_SESSION["perm"] = "";
$uname = "";
$pass = "";
if (isset($_POST["uname"]) && isset($_POST["pass"]) && trim($_POST["uname"]) != "" && trim($_POST["pass"]) != "")
    {
        $uname = $_POST["uname"];
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

    $conn -> query("CREATE TABLE IF NOT EXISTS Queue (
    QueueId   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    PromptId  INT UNSIGNED NOT NULL,
    Username  VARCHAR(50)  NOT NULL,
    Priority  TINYINT      NOT NULL,                  -- 1 = admin, 2 = user
    Status    ENUM('waiting','running','done') NOT NULL DEFAULT 'waiting',
    Answer    MEDIUMTEXT   NULL,
    CreatedAt TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LastSeen  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- updated while the page is open
    PRIMARY KEY (QueueId)
) ENGINE=InnoDB");

    //Delete this code block after first run or if you manually set admin details
    $adminUser = $secrets["admin_user"];
    $adminHash = hash('sha256', $secrets["admin_pass"]);
    $stmt = $conn -> prepare("INSERT IGNORE INTO Admins (Username, PasswordHash) VALUES (?, ?)");
    $stmt -> bind_param("ss", $adminUser, $adminHash);
    $stmt -> execute();

    $dumUser = $secrets["user_user"];
    $userPass = hash("sha256", $secrets["user_pass"]);
    $stmt2 = $conn -> prepare("INSERT IGNORE INTO Users (Username, PasswordHash) VALUES (?, ?)");
    $stmt2 -> bind_param("ss", $dumUser, $userPass);
    $stmt2 -> execute();

    if(substr($uname, 0, 5) == "admin")
        {
            $stmt = $conn -> prepare("SELECT * FROM Admins WHERE Username = ? AND PasswordHash = ?");
            $stmt -> bind_param("ss", $uname, $pass);
            $stmt -> execute();
            $stmt -> store_result();

            if($stmt -> num_rows() < 1)
                {
                    $_SESSION["err"] = "Incorrect Login details";
                    header("Location: login.php");
                    exit;
                }
                else{
                    $_SESSION["perm"] = "admin";
                    $_SESSION["uname"] = $uname;   // used to look up who sent each prompt
                    header("Location: adminAgent.php");
                    exit;
                }
        }
        else{
            $stmt2 = $conn -> prepare("SELECT * FROM Users WHERE Username = ? AND PasswordHash = ?");
            $stmt2 -> bind_param("ss", $uname, $pass);
            $stmt2 -> execute();
            $stmt2 -> store_result();

                if($stmt2 -> num_rows() < 1)
                {
                    
                    $_SESSION["err"] = "Incorrect Login details";
                    header("Location: login.php");
                    exit;
                }
                else{
                    $_SESSION["perm"] = "user";
                    $_SESSION["uname"] = $uname;   // used to look up who sent each prompt
                    header("Location: Agent.php");
                    exit;
                }
        }


    
    }

?>
<html>
    <br>
<a href = "login.php"><button >Go Back</button></a>
</html>
