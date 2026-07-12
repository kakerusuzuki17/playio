<?php
    session_start();
    require "config/db.php";

    header("Content-Type: application/json");

    if (!isset($_SESSION["id"])) {
        echo json_encode(["success" => false]);
        exit;
    }

    $id = $_SESSION["id"];
    $postId = $_POST["post_id"] ?? null;

    if (!$postId) {
        echo json_encode(["success" => false]);
        exit;
    }

    // いいね済みか確認
    $sql = "SELECT id FROM likes WHERE user_id = ? AND post_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $postId]);

    if ($stmt->fetch()) {
        // いいね解除
        $sql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $postId]);
        $liked = false;
    } else {
        // いいね追加
        $sql = "INSERT INTO likes(user_id, post_id) VALUES(?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $postId]);
        $liked = true;
    }

    // 最新いいね数
    $sql = "SELECT COUNT(*) FROM likes WHERE post_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$postId]);
    $likeCount = $stmt->fetchColumn();

    echo json_encode([
        "success" => true,
        "liked" => $liked,
        "like_count" => $likeCount
    ]);
    exit;
?>