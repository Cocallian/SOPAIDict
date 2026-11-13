<?php
// The backup buttons in the Backup card of the dashboard. LocalAIServ.py (APIS/backupapi.py) makes the backups.

const BACKUP_SERVER_URL = "http://127.0.0.1:5000/";   // the Flask server in LocalAIServ.py

// Posts to a backupapi.py route in the Flask server and sets the success or error popup from its answer
function callBackupApi(string $route): void
    {
        session_write_close();   // don't hold the session while the backup runs (a first Drive backup also waits for the Google sign in)
        $reply = @file_get_contents(BACKUP_SERVER_URL . $route, false, stream_context_create(["http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/x-www-form-urlencoded",
            "content"       => "",
            "timeout"       => 600,
            "ignore_errors" => true,
        ]]));
        $status = 0;
        foreach($http_response_header ?? [] as $line)
            {
                if(preg_match('/^HTTP\/\S+ (\d+)/', $line, $m)) { $status = (int)$m[1]; }
            }
        session_start();

        if($reply === false) { $_SESSION["err"] = "The backup server isn't running. Start LocalAIServ.py and try again"; }
        elseif($status === 200) { $_SESSION["success"] = $reply; }
        else { $_SESSION["err"] = $reply; }
    }

// True when $password is the logged in admin's password
function adminPasswordOk(array $secrets, string $userN, string $password): bool
    {
        try
            {
                $check = new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"], "SOPAgent");
                $stmt = $check->prepare("SELECT 1 FROM Admins WHERE Username = ? AND PasswordHash = ?");
                $hash = hash("sha256", $password);
                $stmt->bind_param("ss", $userN, $hash);
                $stmt->execute();
                $stmt->store_result();
                return $stmt->num_rows() > 0;
            }
        catch(mysqli_sql_exception) { return false; }
    }

// The two local buttons: the Flask server makes the backup and this page passes the file on to the browser of whoever
// pressed the button, so it downloads there instead of being saved on the host PC
function sendLocalBackup(string $route): void
    {
        session_write_close();   // don't hold the session while the backup is made
        $stream = @fopen(BACKUP_SERVER_URL . $route, "r", false, stream_context_create(["http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/x-www-form-urlencoded",
            "content"       => "",
            "timeout"       => 600,
            "ignore_errors" => true,
        ]]));
        $status = 0;
        foreach($http_response_header ?? [] as $line)
            {
                if(preg_match('/^HTTP\/\S+ (\d+)/', $line, $m)) { $status = (int)$m[1]; }
            }

        if($stream !== false && $status === 200)
            {
                foreach($http_response_header as $line)
                    {
                        if(preg_match('/^(Content-Type|Content-Disposition|Content-Length):/i', $line)) { header($line); }
                    }
                fpassthru($stream);   // streamed, so a big system zip doesn't have to fit in PHP's memory
                fclose($stream);
                exit();
            }

        $problem = $stream === false ? false : stream_get_contents($stream);
        session_start();
        $_SESSION["err"] = $problem === false ? "The backup server isn't running. Start LocalAIServ.py and try again" : $problem;

        // js/backup.js sent this with fetch: it reloads the page when it sees the 500, and the popup shows the error
        http_response_code(500);
        exit();
    }

// Handles the four backup buttons and the password check before them. Does nothing for any other request.
function handleBackupRequests(array $secrets, string $userN): void
    {
        // js/backup.js asks this first, so the password box can show a wrong password and the loading popup can start
        // as soon as the password is right. Answers "ok" or "no" and draws nothing else.
        if(array_key_exists('check-backup-password', $_POST))
            {
                echo adminPasswordOk($secrets, $userN, $_POST["confirm-password"] ?? "") ? "ok" : "no";
                exit();
            }

        // Every backup button asks the admin for their password first (js/backup.js sends it as confirm-password)
        foreach(["backup", "backup-drive", "backup-system", "backup-system-drive"] as $button)
            {
                if(!array_key_exists($button, $_POST)) { continue; }

                if(!adminPasswordOk($secrets, $userN, $_POST["confirm-password"] ?? ""))
                    {
                        $_SESSION["err"] = "Incorrect password, the backup was not made";
                        header("Location: adminAgent.php");
                        exit();
                    }
                break;
            }

        foreach(["backup" => "local/backup-database", "backup-system" => "local/backup-system"] as $button => $route)
            {
                if(array_key_exists($button, $_POST)) { sendLocalBackup($route); }
            }

        // The two Google Drive buttons
        foreach(["backup-drive" => "backup-database", "backup-system-drive" => "backup-system"] as $button => $route)
            {
                if(!array_key_exists($button, $_POST)) { continue; }

                callBackupApi("drive/" . $route);

                header("Location: adminAgent.php");
                exit();
            }
    }
