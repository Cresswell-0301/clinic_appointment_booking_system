<?php
require_once __DIR__ . '/config.php';

function getDbConnection()
{
    $connectionInfo = [
        "Database" => DB_DATABASE,
        "UID"      => DB_USER,
        "PWD"      => DB_PASSWORD,
    ];

    $conn = sqlsrv_connect(DB_SERVER, $connectionInfo);

    if ($conn === false) {
        die("Database connection failed." . print_r(sqlsrv_errors(), true));
    }

    return $conn;
}
