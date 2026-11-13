<?php
// Generate report button: asks the Flask server (LocalAIServ.py) for the report and passes the file on to the browser

const REPORT_URL = "http://127.0.0.1:5000/report";   // the Flask server makes the report and sends the file back

// Handles the Generate report request. Does nothing for any other request.
function handleGenerateRequest(): void
    {
        if(!array_key_exists('generate', $_POST)) { return; }

        set_time_limit(330);   // the server waits up to 5 minutes for the report
        $post = http_build_query([
            "date_from" => $_POST["report_date_from"] ?? "",
            "date_to"   => $_POST["report_date_to"] ?? "",
            "week"     => $_POST["report_week"] ?? "",
            "month"    => $_POST["report_month"] ?? "",
            "year"     => $_POST["report_year"] ?? "",
            "sections" => implode(",", array_map("strval", (array)($_POST["sections"] ?? []))),
        ]);

        session_write_close();   // don't hold the session for the whole wait, the admin's other pages must keep loading
        $body = @file_get_contents(REPORT_URL, false, stream_context_create(["http" => [
            "method"        => "POST",
            "header"        => "Content-Type: application/x-www-form-urlencoded",
            "content"       => $post,
            "timeout"       => 330,
            "ignore_errors" => true,
        ]]));

        $status = 0;
        $filename = "report.pdf";
        $type = "application/octet-stream";
        foreach($http_response_header ?? [] as $line)
            {
                if(preg_match('/^HTTP\/\S+ (\d+)/', $line, $m)) { $status = (int)$m[1]; }
                if(preg_match('/^Content-Disposition:.*filename="?([^";\r\n]+)"?/i', $line, $m)) { $filename = basename($m[1]); }
                if(preg_match('/^Content-Type:\s*([^;\r\n]+)/i', $line, $m)) { $type = trim($m[1]); }
            }

        if($body !== false && $status === 200)
            {
                header("Content-Type: " . $type);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header("Content-Length: " . strlen($body));
                echo $body;
                exit();
            }

        $message = $body === false ? "The answer server isn't running. Start LocalAIServ.py and try again."
                                   : substr(strip_tags(trim($body)), 0, 300);

        // js/reports.js sends the form with fetch and shows the message under the form, so it gets a plain error instead of a redirect
        if(($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "fetch")
            {
                http_response_code($status >= 400 ? $status : 503);
                header("Content-Type: text/plain; charset=utf-8");
                echo $message;
                exit();
            }

        session_start();
        $_SESSION["err"] = $message;
        header("Location: reports.php");
        exit();
    }
