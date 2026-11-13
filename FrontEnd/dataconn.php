<?php
// Where the log in form goes: checks the username and password and sends the person to the admin page or the user page.
// Each part of the work is a function in one of the files below, this file only runs them in order.
session_start();

require __DIR__ . "/common/config.php";
require __DIR__ . "/common/database.php";
require __DIR__ . "/common/activeusers.php";
require __DIR__ . "/dataconn/schema.php";
require __DIR__ . "/dataconn/loginhandling.php";

$secrets = loadSecrets();
$_SESSION["perm"] = "";

[$uname, $pass] = readLoginForm();

$conn = connectServer($secrets);
if(!$conn || $conn->connect_error)
    {
        refuseLogin("Database connection error, contact your system adminstrator for assistance");
    }

createDatabaseAndTables($conn);
createCleanupEvent($conn);
seedAccounts($conn);
attemptLogin($conn, $uname, $pass);
