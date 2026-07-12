<?php

$host = "localhost";
$dbname = "playio";
$user = "YOUR_DB_USER";
$password = "YOUR_DB_PASSWORD";

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

$pdo = new PDO(
    $dsn,
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);