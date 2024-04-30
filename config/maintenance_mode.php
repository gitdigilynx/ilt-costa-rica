<?php

$uri = $_SERVER['REQUEST_URI'];

$databaseUrl = $_SERVER['DATABASE_URL'] ?? false;

if (!empty($databaseUrl)
    && strpos($uri, '/admin') !== 0
    && strpos($uri, '/_wdt') !== 0) {
    // Parse the database URL
    $urlParts = parse_url($databaseUrl);

    // Extract the connection details
    $servername = $urlParts['host'];
    $username = $urlParts['user'];
    $password = $urlParts['pass'];
    $dbname = ltrim($urlParts['path'], '/');

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // SQL query
    $sql = "SELECT value FROM SystemConfiguration WHERE `key` = 'maintenance_mode'";

    // Execute query
    $result = $conn->query($sql);

    // Check if query was successful
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $isMaintenanceModeActivated = (int) $row["value"];

        if ($isMaintenanceModeActivated === 1) {
            echo <<<HTML
            <html>
            <head>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <link href='https://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet'>
            </head>
            <body>
            <style>
            div {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                font-size: 2rem;
                width: 850px;
            }

            @media only screen and (max-width: 850px) {
                div {
                width: 100%;
                }
            }
            </style>
            <div>We are presently undergoing maintenance on our website. For any inquiries or booking requests, kindly contact us at <a href='mailto:info@iltcostarica.com'>info@iltcostarica.com</a>. We expect to be back online shortly. Thank you for your understanding.</div>
            </body>
            </html>
HTML;
            exit();
        }
    } else {
        echo "Error executing query: " . $conn->error;
    }

    // Close connection
    $conn->close();
}