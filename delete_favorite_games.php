<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";

$userId = $_SESSION["id"];
$deleteFavoriteGamesId = $_POST["delete_favorite_games_id"] ?? "";

// お気に入りゲームを削除
$stmt = $pdo->prepare("
    DELETE FROM favorite_games
    WHERE user_id = ?
        AND game_id = ?
");
$stmt->execute([
    $userId, $deleteFavoriteGamesId
]);

header("Location: profile.php?account_id=" . $_SESSION["account_id"]);
exit;