<?php

header("Content-Type: application/json; charset=UTF-8");

require "../../config/db.php";

$input = json_decode(
    file_get_contents("php://input"),
    true
);

$postId = $input["post_id"] ?? null;
$userId = $input["user_id"] ?? null;

if (!$postId || !$userId) {
    http_response_code(400);

    echo json_encode(
        [
            "success" => false,
            "message" => "post_idとuser_idが必要です"
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

$sql = "SELECT id
        FROM likes
        WHERE post_id = ?
        AND user_id = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $postId,
    $userId
]);

$like = $stmt->fetch(PDO::FETCH_ASSOC);

if ($like) {

    // いいね解除
    $sql = "DELETE FROM likes
            WHERE post_id = ?
            AND user_id = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $postId,
        $userId
    ]);

    $liked = false;

} else {

    // いいね
    $sql = "INSERT INTO likes (
                post_id,
                user_id
            )
            VALUES (?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $postId,
        $userId
    ]);

    $liked = true;
}

$countSql = "SELECT COUNT(*)
             FROM likes
             WHERE post_id = ?";

$countStmt = $pdo->prepare($countSql);

$countStmt->execute([
    $postId
]);

$likeCount = (int)$countStmt->fetchColumn();

echo json_encode(
    [
        "success" => true,
        "liked" => $liked,
        "like_count" => $likeCount
    ],
    JSON_UNESCAPED_UNICODE
);