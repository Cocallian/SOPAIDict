<?php
// CSRF protection. Every form carries this session's token as a hidden "csrf_token" field (a name ZAP's Anti-CSRF scanner recognises), and js/common.js sends it as an
// X-CSRF-Token header on fetch calls. verifyCsrf() refuses any POST that doesn't have it, so a page on another site
// can't make a logged in person's browser submit one of these forms.

// This session's token, made the first time it is asked for
function csrfToken(): string
    {
        if(empty($_SESSION["csrf"])) { $_SESSION["csrf"] = bin2hex(random_bytes(32)); }
        return $_SESSION["csrf"];
    }

// The hidden input that goes inside every <form>
function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
    }

// The <meta> tag that goes in <head>, which js/common.js reads for its fetch calls
function csrfMeta(): string
    {
        return '<meta name="csrf" content="' . htmlspecialchars(csrfToken()) . '">';
    }

// Call once per page, after session_start(). Stops any POST that doesn't carry this session's token.
function verifyCsrf(): void
    {
        if(($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") { return; }

        $sent = $_POST["csrf_token"] ??($_SERVER["HTTP_X_CSRF_TOKEN"] ?? "");
        if(!is_string($sent) || !hash_equals((string)($_SESSION["csrf"] ?? ""), $sent))
            {
                http_response_code(403);
                header("Content-Type: text/plain; charset=utf-8");
                exit("Invalid request, reload the page and try again");
            }
    }
