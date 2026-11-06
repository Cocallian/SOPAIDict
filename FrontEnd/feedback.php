<?php
// Satisfaction score and extra feedback for an answer, shared by Agent.php and adminAgent.php.
// A Feedback row is made as soon as the answer is saved (no score, "N/A" as the extra feedback), so an untouched
// feedback window still leaves "N/A" in the table. The window fills the same row in when the user uses it.

// Makes the row for a saved answer, returns its FeedbackId (null when it could not be made)
function createFeedbackRow($conn, $responseId, $promptId, $uname)
    {
        try
            {
                $stmt = $conn->prepare("INSERT INTO Feedback (ResponseId, PromptId, Username, Rating, ExtraFeedback) VALUES (?, ?, ?, NULL, 'N/A')");
                $stmt->bind_param("iis", $responseId, $promptId, $uname);
                $stmt->execute();
                $id = $conn->insert_id;
                $stmt->close();
                return $id;
            }
        catch(mysqli_sql_exception)
            {
                return null;
            }
    }

// validation.js sends feedback_id, rating (1-5 or empty) and extra with fetch. Only the signed in user's own row is changed.
function handleFeedbackSave($conn)
    {
        if(!array_key_exists('feedback_id', $_POST)) { return; }
        $id     = (int)$_POST["feedback_id"];
        $rating = (string)($_POST["rating"] ?? "");
        $rating = ($rating !== "" && ctype_digit($rating) && $rating >= 1 && $rating <= 5) ? (int)$rating : null;
        $extra  = mb_substr(trim((string)($_POST["extra"] ?? "")), 0, 2000);
        if($extra === "") { $extra = "N/A"; }
        $uname  = $_SESSION["uname"] ?? "";
        try
            {
                $stmt = $conn->prepare("UPDATE Feedback SET Rating = COALESCE(?, Rating), ExtraFeedback = ? WHERE FeedbackId = ? AND Username = ?");
                $stmt->bind_param("isis", $rating, $extra, $id, $uname);
                $stmt->execute();
                $stmt->close();
                echo "ok";
            }
        catch(mysqli_sql_exception)
            {
                http_response_code(500);
            }
        exit();
    }

// The card shown to the right of the answer
function renderFeedback($feedbackId)
    {
        if(!$feedbackId) { return; }
        ?>
        <aside id="feedback" data-feedback-id="<?php echo (int)$feedbackId; ?>">
            <h3>How was this answer?</h3>
            <label for="fbRating">Satisfaction score</label>
            <select id="fbRating">
                <option value="">Choose a score...</option>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Okay</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Very poor</option>
            </select>
            <label for="fbText">Anything to add?</label>
            <textarea id="fbText" maxlength="2000" placeholder="Tell us what worked or what didn't (optional)"></textarea>
            <button type="button" id="fbSend">Send feedback</button>
            <p id="fbStatus"></p>
        </aside>
        <?php
    }
