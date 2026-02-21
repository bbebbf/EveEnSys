<?php
declare(strict_types=1);

function db_connect(): mysqli
{
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_USER') ?: '',
        getenv('DB_PASSWORD') ?: '',
        getenv('DB_NAME') ?: ''
    );
    $conn->set_charset('utf8mb4');
    return $conn;
}
