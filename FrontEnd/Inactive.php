<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Layout from the login page, in the Agent pages' colours */
        * {
            box-sizing: border-box;
        }
        html, body {
            min-height: 100%;
            margin: 0;
        }
        html {
            background: linear-gradient(to bottom,  #7d7e7d 0%,#232323 73%);
            background-attachment: fixed;
        }
        body {
            font-family: Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .login-page {
            width: 360px;
            max-width: calc(100% - 32px);   /* keeps a 16px gap each side on phones */
            padding: 8% 0 0;
            margin: auto;
        }
        .form {
            position: relative;
            z-index: 1;
            background-color: #312f2f;
            max-width: 360px;
            margin: 0 auto 100px;
            padding: 45px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
            color: white;
        }
        @media (max-width: 420px) {
            .form {
                padding: 30px 20px;
            }
        }
        .form h1 {
            margin: 0 0 15px;
            font-size: 24px;
            font-weight: normal;
        }
        .form p {
            margin: 0 0 30px;
            color: #b3b3b3;
            font-size: 14px;
        }
        .form a {
            display: block;
            font-family: inherit;
            text-transform: uppercase;
            text-decoration: none;
            background-color: dimgrey;
            border-radius: 8px;
            padding: 15px;
            color: white;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .form a:hover,
        .form a:active,
        .form a:focus {
            background-color: #7d7e7d;
        }
    </style>
    <title>SOP Agent</title>
</head>
<body>
    <div class = "login-page">
        <div class = "form">
            <h1>You have been logged out</h1>
            <p>You were signed out because of inactivity.</p>
            <a href = "login.php">Back to log in</a>
        </div>
    </div>
</body>
</html>
