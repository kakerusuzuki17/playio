<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "ログインが必要です"
    ]);

    exit;
}

require "config/db.php";

$gameId = filter_input(
    INPUT_GET,
    "game_id",
    FILTER_VALIDATE_INT
);

$categoryId = filter_input(
    INPUT_GET,
    "category_id",
    FILTER_VALIDATE_INT
);

if (!$gameId || !$categoryId) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "ゲームまたはカテゴリが選択されていません"
    ]);

    exit;
}

$sql = "SELECT
            id,
            name
        FROM divisions
        WHERE game_id = ?
        AND category_id = ?
        ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $gameId,
    $categoryId
]);

$divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "divisions" => $divisions
], JSON_UNESCAPED_UNICODE);