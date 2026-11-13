<?php
// The MySQL connections the pages share

// Connects to the MySQL server only, before the SOPAgent database is known to exist. Returns null when it can't.
function connectServer(array $secrets): ?mysqli
    {
        try
            {
                return new mysqli($secrets["db_host"], $secrets["db_user"], $secrets["db_pass"]);
            }
        catch(mysqli_sql_exception)
            {
                return null;
            }
    }

// Connects to the SOPAgent database. Returns null when it can't, and leaves $errorMessage for the page to show when one is given.
function connectDatabase(array $secrets, string $errorMessage = ""): ?mysqli
    {
        $conn = connectServer($secrets);
        if($conn)
            {
                try
                    {
                        $conn->select_db("SOPAgent");
                        return $conn;
                    }
                catch(mysqli_sql_exception) {}
            }
        if($errorMessage !== "") { $_SESSION["err"] = $errorMessage; }
        return null;
    }
