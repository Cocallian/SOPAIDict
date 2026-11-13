<?php
// The red and green messages at the top of a page. A page leaves its message in the session, and the next page that is
// shown reads it, so it appears once. js/common.js closes them after a few seconds.

// Reads the error message and clears it, so it only shows once
function takeError(): string
    {
        $err = $_SESSION["err"] ?? "";
        $_SESSION["err"] = "";
        return $err;
    }

// Reads the success message and clears it, so it only shows once
function takeSuccess(): string
    {
        $success = $_SESSION["success"] ?? "";
        $_SESSION["success"] = "";
        return $success;
    }

// Writes the popups for whichever message is not empty
function renderPopups(string $err, string $success = ""): void
    {
        if($err !== "")
            { ?>
    <div id = "errPopup">
        <?php echo htmlspecialchars($err); ?>
        <button type = "button" id = "closePopup" aria-label = "Close">&times;</button>
    </div>
<?php   }
        if($success !== "")
            { ?>
    <div id = "successPopup">
        <?php echo htmlspecialchars($success); ?>
        <button type = "button" id = "closeSuccess" aria-label = "Close">&times;</button>
    </div>
<?php   }
    }
