<?php
session_start();

require "config/db.php";

// ログイン確認
if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $game_id = $_POST["game_id"] ?? null;
    $game_name = $_POST["game_name"] ?? null;
    $content = $_POST["content"];

    // 空投稿防止
    if ($content === "") {
        header("Location: home.php");
        exit;
    }

$sql = "INSERT INTO posts(
        user_id,
        game_id,
        game_name,
        content
    )
    VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $_SESSION["id"],
    $game_id,
    $game_name,
    $content
]);

    // Xにも投稿できるようにする
    $_SESSION["x_share_url"] =
    "https://twitter.com/intent/tweet?text=" . urlencode($content);

    header("Location: home.php");
    exit;
}