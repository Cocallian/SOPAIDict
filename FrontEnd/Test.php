<?php

$PYTH = "C:\\Users\\tygra\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
$SCRIPT = "C:\\Users\\tygra\\Documents\\Code\\Pythom\\LLM\\LocalAITest.py";

$q = $_POST['user-input'] ?? '';
$a = "";

if($q !== "")
    {
        $command = escapeshellarg($PYTH) . " " . escapeshellarg($SCRIPT) . " " . escapeshellarg($q) . " 2>&1";
    $a = shell_exec($command);
    }

        

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"] {
            width: 300px;
            padding: 5px;
            margin-bottom: 10px;
        }
        button {
            padding: 5px 10px;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method = "post">
        <label for="user-input">Enter your message:</label>
        <input type="text" id="user-input" name="user-input">
        <button type="submit">Send</button>
    </form>
</body>
</html>
<p><?php echo nl2br(htmlspecialchars($a)); ?></p >