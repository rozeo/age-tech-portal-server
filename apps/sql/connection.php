<?php

require_once __DIR__ . "/../index.php";

function createConnection(): \PDO {
    $host = getenv('DB_HOST');
    $database = getenv('DB_DATABASE');
    $username = getenv('DB_USER');
    $password = getenv('DB_`PASSWORD');

    $dsn = "pgsql:host=$host;dbname=$database;user=$username;password=$password";

    return new \PDO($dsn);
}