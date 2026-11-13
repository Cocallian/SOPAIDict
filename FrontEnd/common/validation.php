<?php
// The rules for a new account, shared by the Add new user form (admin) and the account request form.
// validation.js checks the same rules in the browser; this is the check that can't be skipped.

// 8+ characters with a number, a lowercase letter, an uppercase letter and a special character
function strongPassword(string $pw): bool
    {
        return preg_match('/.{8,}/', $pw) && preg_match('/[0-9]/', $pw) && preg_match('/[a-z]/', $pw)
            && preg_match('/[A-Z]/', $pw) && preg_match('/[^A-Za-z0-9]/', $pw);
    }

// The first thing wrong with a new account as a message for the page to show, or "" when it is fine.
// $checkLength also refuses usernames over 50 characters (the account request form does, the admin form's box has a maxlength).
function accountProblem(string $name, string $pw, string $confirm, bool $isAdmin, array $orgDomains, bool $checkLength = false): string
    {
        if(!validOrgUsername($name, $orgDomains))
            {
                return "The username must be an email ending in: " . implode(", ", $orgDomains);
            }
        if($checkLength && strlen($name) > 50)
            {
                return "The username can be at most 50 characters";
            }
        if(!strongPassword($pw))
            {
                return "The password needs 8+ characters with a number, lowercase, uppercase and special character";
            }
        if($pw !== $confirm)
            {
                return "The passwords don't match";
            }
        // dataconn.php decides admin or user by the username, so the name has to match the role
        if($isAdmin && substr($name, 0, 5) !== "admin")
            {
                return "Admin usernames must start with \"admin\"";
            }
        if(!$isAdmin && substr($name, 0, 5) === "admin")
            {
                return "User usernames can't start with \"admin\"";
            }
        return "";
    }
