<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";

$deletePostId = $_POST["delete_post_id"] ?? "";

// highscoreを削除
$stmt = $pdo->prepare("
    DELETE FROM highscore
    WHERE post_id = ?
");
$stmt->execute([
    $deletePostId
]);

// cleartimeを削除
$stmt = $pdo->prepare("
    DELETE FROM cleartime
    WHERE post_id = ?
");
$stmt->execute([
    $deletePostId
]);

// highscoreを削除
$stmt = $pdo->prepare("
    DELETE FROM highscore
    WHERE post_id = ?
");
$stmt->execute([
    $deletePostId
]);

// likesを削除
$stmt = $pdo->prepare("
    DELETE FROM likes
    WHERE post_id = ?
");
$stmt->execute([
    $deletePostId
]);

// post_mediaを削除
$stmt = $pdo->prepare("
    DELETE FROM post_media
    WHERE post_id = ?
");
$stmt->execute([
    $deletePostId
]);

// post_tagsを削除
$stmt = $pdo->prepare("
    DELETE FROM post_tags
    WHERE post_id = ?
");

$stmt->execute([
    $deletePostId
]);

// postsを削除
$stmt = $pdo->prepare("
    DELETE FROM posts
    WHERE id = ?
");

$stmt->execute([
    $deletePostId
]);

header("Location: profile.php");
exit;