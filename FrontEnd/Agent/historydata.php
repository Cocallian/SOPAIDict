<?php
// The Prompt history table in the menu

// This user's questions and answers, newest first
function loadHistory(?mysqli $conn): array
    {
        $history = [];
        if($conn)
            {
                try
                    {
                        $uname = $_SESSION["uname"] ?? "";
                        $stmt = $conn->prepare("SELECT p.PromptText, r.ResponseText, p.CreatedAt
                                                FROM Prompts p
                                                JOIN Users u ON u.UserId = p.UserId
                                                LEFT JOIN Responses r ON r.PromptId = p.PromptId
                                                WHERE u.Username = ?
                                                ORDER BY p.CreatedAt DESC, p.PromptId DESC");
                        $stmt->bind_param("s", $uname);
                        $stmt->execute();
                        $stmt->bind_result($question, $answer, $askedAt);
                        while($stmt->fetch())
                            {
                                $history[] = ["question" => $question, "answer" => $answer, "askedAt" => $askedAt];
                            }
                        $stmt->close();
                    }
                catch(mysqli_sql_exception)
                    {
                        $_SESSION["err"] = "Your prompt history could not be loaded";
                    }
            }
        return $history;
    }
