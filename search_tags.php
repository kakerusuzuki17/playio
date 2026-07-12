<?php
require "config/db.php";

header("Content-Type: application/json; charset=utf-8");

$q = $_GET["q"] ?? "";

if (trim($q) === "") {
    echo json_encode([]);
    exit;
}

$sql = "SELECT
        id,
        name
    FROM tags
    WHERE name LIKE ?
    ORDER BY name ASC
    LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%" . $q . "%"]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);