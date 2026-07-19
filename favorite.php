<?php
    session_start();
    require "config/db.php";

    header("Content-Type: application/json");

    if (!isset($_SESSION["id"])) {
        echo json_encode(["success" => false]);
        exit;
    }

    $id = $_SESSION["id"];
    $gameId = $_POST["game_id"] ?? null;

    if (!$gameId) {
        echo json_encode(["success" => false]);
        exit;
    }

    // お気に入り済みか確認
    $sql = "SELECT id FROM favorite_games WHERE user_id = ? AND game_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $gameId]);

    if ($stmt->fetch()) {
        // お気に入り解除
        $sql = "DELETE FROM favorite_games WHERE user_id = ? AND game_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $gameId]);
        $favorite = false;
    } else {
        // お気に入り追加
        $sql = "INSERT INTO favorite_games(user_id, game_id) VALUES(?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $gameId]);
        $favorite = true;
    }

    // 最新お気に入り数
    $sql = "SELECT COUNT(*) FROM favorite_games WHERE game_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$gameId]);
    $favoriteCount = $stmt->fetchColumn();

    echo json_encode([
        "success" => true,
        "favorite" => $favorite,
        "favorite_count" => $favoriteCount
    ]);
    exit;
?>