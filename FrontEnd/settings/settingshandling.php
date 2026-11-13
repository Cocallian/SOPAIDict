<?php
// The Settings page: the limits of each setting, saving them through the Flask server (APIS/settingsapi.py) and loading
// the current values from it

const SETTINGS_URL = "http://127.0.0.1:5000/settings";   // the Flask server in LocalAIServ.py

// What each setting accepts. The page draws its hints and js/settings.js checks the form from this, and the save below
// checks it again, so the limits are only written here. kind: int, float, text, path or model.
function settingFields(): array
    {
        return [
            "org_name"        => ["label" => "Organisation name",  "kind" => "text",  "max" => 200],
            "backup_path"     => ["label" => "Backup folder",      "kind" => "path",  "max" => 260, "optional" => true],
            "context_window"  => ["label" => "Context window",     "kind" => "int",   "min" => 256, "max" => 32768],
            "temperature"     => ["label" => "Temperature",        "kind" => "float", "min" => 0,   "max" => 2],
            "length"          => ["label" => "Answer length",      "kind" => "int",   "min" => 16,  "max" => 4096],
            "sources"         => ["label" => "Sources",            "kind" => "int",   "min" => 1,   "max" => 10],
            "running_threads" => ["label" => "Parallel questions", "kind" => "int",   "min" => 1,   "max" => 8],
            "model_name"      => ["label" => "Model name",         "kind" => "model", "max" => 100],
            "embed_model"     => ["label" => "Embedding model",    "kind" => "model", "max" => 100],
        ];
    }

// The problem with a value, or "" when it is fine
function settingProblem($rule, $value)
    {
        $label = $rule["label"];
        if($value === "") { return ($rule["optional"] ?? false) ? "" : $label . " can't be empty."; }
        if(mb_strlen($value) > $rule["max"] && in_array($rule["kind"], ["text", "path", "model"])) { return $label . " can be at most " . $rule["max"] . " characters."; }
        switch($rule["kind"])
            {
                case "int":
                    if(!ctype_digit($value)) { return $label . " must be a whole number."; }
                    if((int)$value < $rule["min"] || (int)$value > $rule["max"]) { return $label . " must be between " . $rule["min"] . " and " . $rule["max"] . "."; }
                    break;
                case "float":
                    if(!preg_match('/^\d+(\.\d+)?$/', $value)) { return $label . " must be a number, like 0.1."; }
                    if((float)$value < $rule["min"] || (float)$value > $rule["max"]) { return $label . " must be between " . $rule["min"] . " and " . $rule["max"] . "."; }
                    break;
                case "path":
                    if(!preg_match('/^([A-Za-z]:[\\\\\/]|\\\\\\\\)[^<>"|?*]*$/', $value)) { return $label . " must be a full Windows path, like D:\\Backups."; }
                    break;
                case "model":
                    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]*$/', $value)) { return $label . " can only have letters, numbers and . _ : / -"; }
                    break;
                default:
                    if(preg_match('/[\x00-\x1F]/', $value)) { return $label . " can't have control characters."; }
            }
        return "";
    }

// Save: js/settings.js sends the form with fetch, this answers with a plain message (200 saved, otherwise the problem)
function handleSaveSettings(array $fields): void
    {
        if(!array_key_exists('save-settings', $_POST)) { return; }

        $send = [];
        foreach($fields as $name => $rule)
            {
                $value = trim((string)($_POST[$name] ?? ""));
                $problem = settingProblem($rule, $value);
                if($problem !== "") { http_response_code(400); header("Content-Type: text/plain; charset=utf-8"); echo $problem; exit(); }
                $send[$name] = $value;
            }

        session_write_close();
        $reply = @file_get_contents(SETTINGS_URL, false, stream_context_create(["http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/x-www-form-urlencoded",
            "content"       => http_build_query($send),
            "timeout"       => 15,
            "ignore_errors" => true,
        ]]));
        $status = 0;
        foreach($http_response_header ?? [] as $line)
            {
                if(preg_match('/^HTTP\/\S+ (\d+)/', $line, $m)) { $status = (int)$m[1]; }
            }

        header("Content-Type: text/plain; charset=utf-8");
        if($reply === false) { http_response_code(503); echo "The answer server isn't running. Start LocalAIServ.py and try again."; }
        elseif($status !== 200) { http_response_code($status >= 400 ? $status : 500); echo substr(strip_tags(trim($reply)), 0, 300); }
        else { echo "Settings saved. Stop and start the server on the dashboard for them to take effect."; }
        exit();
    }

// The current values, asked from the server when the page loads. null when the server isn't running.
function loadCurrentSettings(): ?array
    {
        $reply = @file_get_contents(SETTINGS_URL, false, stream_context_create(["http" => ["timeout" => 5, "ignore_errors" => true]]));
        $current = $reply === false ? null : json_decode($reply, true);
        return is_array($current) ? $current : null;
    }

// A short line under each input, from the same limits
function settingHint($rule)
    {
        switch($rule["kind"])
            {
                case "int":   return "Whole number, " . $rule["min"] . " to " . $rule["max"];
                case "float": return "Number, " . $rule["min"] . " to " . $rule["max"];
                case "path":  return "Full Windows path, like D:\\Backups. Left empty it is not changed.";
                case "model": return "Letters, numbers and . _ : / - only";
                default:      return "Up to " . $rule["max"] . " characters";
            }
    }

// The explanation card: what each setting does and what can go wrong when it is changed

// The explanation card: what each setting does and what can go wrong when it is changed
function settingsHelp(): array
    {
        return [
            "org_name"        => ["Is named in the instructions the model is given, so it answers as that organisation.",
                                  "A wrong or very long name makes answers refer to the wrong organisation and uses up room in the context window."],
            "backup_path"     => ["The folder the scheduled backups are saved to.",
                                  "If the folder doesn't exist, can't be written to, or is on a drive that is offline, the backups fail."],
            "context_window"  => ["How much text, in tokens, the model can read for one question: the sources and the question together.",
                                  "Higher fits more sources but uses a lot more memory (it is multiplied by the parallel questions) and slows answers. Too low cuts the sources off, so answers ignore them. Raise it when you raise the sources."],
            "temperature"     => ["How freely the model words its answer. Low sticks closely to the sources.",
                                  "Higher values make answers vary and make it more likely to state things the sources don't say. For SOP answers keep it low, around 0.1 to 0.3."],
            "length"          => ["The longest answer, in tokens, the model may write.",
                                  "Longer limits give slower, wordier answers. Too short cuts an answer off in the middle of a sentence."],
            "sources"         => ["How many passages from the documents are given to the model with the question.",
                                  "More sources give more to work from but need a bigger context window, are slower, and can bury the useful passage. Fewer may miss the one that matters."],
            "running_threads" => ["How many questions are answered at the same time.",
                                  "More means shorter waits when many people ask at once, but each running question uses memory and shares the processor, so each one is slower. A question from an admin goes first in the line but doesn't interrupt one already running."],
            "model_name"      => ["The Ollama model that writes the answers.",
                                  "It must already be downloaded in Ollama (ollama pull), or every question fails. Bigger models answer better but need more memory and are slower."],
            "embed_model"     => ["The model that turns the documents and each question into numbers so the right passages can be found.",
                                  "It must be the same one EmbedScript.py used. Changing it without running EmbedScript.py again makes the search return the wrong passages. It also has to be downloaded on this PC already."],
        ];
    }
