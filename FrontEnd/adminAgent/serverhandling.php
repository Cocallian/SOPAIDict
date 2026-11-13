<?php
// The Server card of the dashboard. The server is LocalAIServ.py, the same one that answers questions on port 5000.
// Where the file is lives under "paths" in FrontEndConfig.json as server_path (with / slashes); when it isn't set the
// file window opens to choose it.

const SERVER_PORT = 5000;   // PORT in config.json

// True when something is answering on the server's port
function serverRunning(): bool
    {
        $socket = @fsockopen("127.0.0.1", SERVER_PORT, $errno, $errstr, 1);
        if($socket === false) { return false; }
        fclose($socket);
        return true;
    }

// Runs a PowerShell script (base64 encoded, so no quoting problems) and returns what it printed
function runPowerShell(string $script): string
    {
        return trim(shell_exec("powershell -NoProfile -STA -EncodedCommand " . base64_encode(iconv("UTF-8", "UTF-16LE", $script))) ?? "");
    }

// Opens a file window on this PC's desktop to choose LocalAIServ.py, returns the path with / slashes ("" when cancelled)
function chooseServerFile(): string
    {
        $pick = 'Add-Type -AssemblyName System.Windows.Forms; '
              . '$f = New-Object System.Windows.Forms.OpenFileDialog; '
              . '$f.Title = "Choose LocalAIServ.py"; '
              . '$f.Filter = "LocalAIServ.py|LocalAIServ.py|Python files (*.py)|*.py"; '
              . 'if($f.ShowDialog((New-Object System.Windows.Forms.Form -Property @{TopMost = $true})) -eq "OK") { $f.FileName }';
        return str_replace("\\", "/", runPowerShell($pick));
    }

// Writes server_path under "paths" in FrontEndConfig.json
function saveServerPath(string $path): void
    {
        $config = readFrontEndConfig();
        $config["paths"]["server_path"] = $path;
        saveFrontEndConfig($config);
    }

// Answers "up" or "down" and nothing else, js/server.js asks it every couple of seconds while the server is starting
function handleServerStatus(): void
    {
        if(!array_key_exists('server-status', $_POST)) { return; }

        echo serverRunning() ? "up" : "down";
        exit();
    }

// Start server. js/server.js sends it with fetch and shows the loading popup, so this answers with one word:
// "running" (already was), "started" (the window was opened, the server is now loading) or "error: <what went wrong>"
function handleStartServer(array $secrets): void
    {
        if(!array_key_exists('start-server', $_POST)) { return; }

        if(serverRunning()) { echo "running"; exit(); }

        $serverFile = trim($secrets["server_path"] ?? "");
        if($serverFile === "" || !is_file($serverFile))
            {
                $serverFile = chooseServerFile();            // first start (or the file moved): choose it once and remember it
                if($serverFile === "") { echo "error: No server file was chosen"; exit(); }
                saveServerPath($serverFile);
            }

        $folder = str_replace("/", "\\", dirname($serverFile));
        $python = $folder . "\\.venv\\Scripts\\python.exe";
        if(!is_file($python)) { echo "error: " . $python . " wasn't found"; exit(); }

        // Opens in its own window, so its log lines can be watched; the Start-Process call returns straight away
        runPowerShell("Start-Process -FilePath '" . $python . "' -ArgumentList '" . basename($serverFile) . "' -WorkingDirectory '" . $folder . "'");
        echo "started";
        exit();
    }

// Change server file: opens the file window again and remembers the new choice
function handleChangeServerPath(): void
    {
        if(!array_key_exists('change-server-path', $_POST)) { return; }

        $serverFile = chooseServerFile();
        if($serverFile !== "")
            {
                saveServerPath($serverFile);
                $_SESSION["success"] = "Server file set to: " . $serverFile;
            }
        header("Location: adminAgent.php");
        exit();
    }

// Stop server: LocalAIServ.py stops Ollama and then itself (its window closes with it)
function handleStopServer(): void
    {
        if(!array_key_exists('stop-server', $_POST)) { return; }

        if(!serverRunning()) { $_SESSION["err"] = "The server isn't running"; }
        else
            {
                session_write_close();   // don't hold the session while the server shuts down
                @file_get_contents("http://127.0.0.1:" . SERVER_PORT . "/shutdown", false, stream_context_create(["http" => [
                    "method"        => "POST",
                    "timeout"       => 30,
                    "ignore_errors" => true,
                ]]));
                for($i = 0; $i < 10 && serverRunning(); $i++) { usleep(500000); }   // up to 5 seconds for the port to close
                session_start();
                if(serverRunning()) { $_SESSION["err"] = "The server couldn't be stopped"; }
                else { $_SESSION["success"] = "Ollama and the server have been stopped"; }
            }
        header("Location: adminAgent.php");
        exit();
    }
