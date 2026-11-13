<?php
// What the admin page and the user page both do with a question: ask the answer server, save the answer and read the
// sources out of it

const ANSWER_SERVER_URL = "http://127.0.0.1:5000/ask";   // the Flask server in LocalAIServ.py

// Sends the question to the answer server and waits here until its answer comes back. $role is "admin" or "user":
// admins are answered before users.
function askAnswerServer(string $question, string $role): string
    {
        $reply = @file_get_contents(ANSWER_SERVER_URL, false, stream_context_create(["http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/x-www-form-urlencoded",
            "content"       => http_build_query(["question" => $question, "role" => $role, "ip" => $_SERVER["REMOTE_ADDR"], "user" => $_SESSION["uname"] ?? ""]),
            "timeout"       => 300,
            "ignore_errors" => true,
        ]]));
        return $reply === false ? "The answer server isn't running. Start LocalAIServ.py and try again." : $reply;
    }

// LocalAIServ.py ends every answer with "Sources:" and one "[n] document number: title, p. x" line per passage.
// What is kept is "document number: title", without the [n] and the page numbers.
function sourcedTitles(string $answer): array
    {
        $titles = [];
        if(preg_match('/\n\nSources:\n(.+)$/s', $answer, $block))
            {
                foreach(explode("\n", trim($block[1])) as $line)
                    {
                        if(preg_match('/^\[\d+\] (.+), pp?\. [\d-]+$/', trim($line), $parts)) { $titles[] = $parts[1]; }
                    }
            }
        return array_unique($titles);
    }

// Saves a question for the logged in person and gets its answer. $role is "admin" or "user": it decides which table the
// question is linked to and how it is queued. Returns [answer, FeedbackId of the answer's feedback row or null].
function answerQuestion(?mysqli $conn, string $q, string $role): array
    {
        set_time_limit(300);   // finding passages + the model's answer can take over a minute

        $isAdmin = $role === "admin";
        $table = $isAdmin ? "Admins" : "Users";          // fixed names, never taken from the request
        $idColumn = $isAdmin ? "AdminId" : "UserId";
        $personId = null;
        $promptId = null;

        // save the prompt with the current date and time before the model starts working on it
        if($conn)
            {
                try
                    {
                        $uname = $_SESSION["uname"] ?? "";
                        $stmt = $conn->prepare("SELECT $idColumn FROM $table WHERE Username = ?");
                        $stmt->bind_param("s", $uname);
                        $stmt->execute();
                        $stmt->bind_result($personId);
                        $stmt->fetch();
                        $stmt->close();

                        $stmt = $conn->prepare("INSERT INTO Prompts ($idColumn, PromptText, CreatedAt) VALUES (?, ?, NOW())");
                        $stmt->bind_param("is", $personId, $q);
                        $stmt->execute();
                        $promptId = $conn->insert_id;
                        $stmt->close();
                    }
                catch(mysqli_sql_exception)
                    {
                        $_SESSION["err"] = "Your prompt could not be saved to the database";
                    }
            }

        // blocks here until the answer comes back
        $a = askAnswerServer($q, $role);

        // the response is back: save it against the prompt, again with the current date and time
        $feedbackId = null;
        if($promptId && trim($a) !== "")
            {
                $feedbackId = saveAnswer($conn, $idColumn, $promptId, $personId, $a);
            }

        return [$a, $feedbackId];
    }

// Saves the answer against the prompt, then the documents it was sourced from (one row for each document), then the
// feedback row the feedback window fills in. Returns the FeedbackId (null when it could not be made).
function saveAnswer(mysqli $conn, string $idColumn, int $promptId, ?int $personId, string $a): ?int
    {
        $feedbackId = null;
        try
            {
                $stmt = $conn->prepare("INSERT INTO Responses (PromptId, $idColumn, ResponseText, CreatedAt) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $promptId, $personId, $a);
                $stmt->execute();
                $responseId = $conn->insert_id;
                $stmt->close();
                $feedbackId = createFeedbackRow($conn, $responseId, $promptId, $_SESSION["uname"] ?? "");
            }
        catch(mysqli_sql_exception)
            {
                $_SESSION["err"] = "The response could not be saved to the database";
            }

        try
            {
                $stmt = $conn->prepare("INSERT INTO SourcedDocuments (PromptId, $idColumn, Title, SourcedAt) VALUES (?, ?, ?, NOW())");
                foreach(sourcedTitles($a) as $title)
                    {
                        $stmt->bind_param("iis", $promptId, $personId, $title);
                        $stmt->execute();
                    }
                $stmt->close();
            }
        catch(mysqli_sql_exception)
            {
                $_SESSION["err"] = "The source documents could not be saved to the database";
            }
        return $feedbackId;
    }
