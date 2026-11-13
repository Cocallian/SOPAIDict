<?php
// Who is logged in. Every page that needs a login starts with this.

// Returns the logged in username when the session has the given role ("admin" or "user"), otherwise sends the visitor to login.php
function requireRole(string $role): string
    {
        if(($_SESSION["perm"] ?? "") != $role)
            {
                header("Location: login.php");
                exit();
            }
        return $_SESSION["uname"] ?? "";
    }
