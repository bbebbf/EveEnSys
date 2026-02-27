<?php
declare(strict_types=1);

function db_connect(): mysqli
{
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }


    $config = [
        "host" => '',
        "username" => '',
        "password" => '',
        "database" => ''
    ];
 
    $configPath = dirname(APP_ROOT) . '/_config/database-config.json';

    if (file_exists($configPath)) {
        $config_file_contents = file_get_contents($configPath);
        $config_from_file = json_decode($config_file_contents, true);
        if ($config_from_file !== null) {
            $config = $config_from_file;
        }
    }
    else {
        $config["host"] = getenv('DB_HOST');
        $config["username"] = getenv('DB_USER');
        $config["password"] = getenv('DB_PASSWORD');
        $config["database"] = getenv('DB_NAME');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database']
    );
    $conn->set_charset('utf8mb4');
    return $conn;
}
