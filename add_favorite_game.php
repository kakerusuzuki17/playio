<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";

$userId = (int)$_SESSION["id"];

$igdbId = (int)($_POST["igdb_id"] ?? 0);
$gameName = trim($_POST["game_name"] ?? "");
$gameCover = trim($_POST["game_cover"] ?? "");
$gameGenres = trim($_POST["game_genres"] ?? "");

if ($igdbId <= 0 || $gameName === "") {
    $_SESSION["error"] =
        "ゲームを検索結果から選択してください。";

    header("Location: edit_profile.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // gamesに登録済みか確認
    $stmt = $pdo->prepare("
        SELECT id
        FROM games
        WHERE igdb_id = ?
    ");

    $stmt->execute([$igdbId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($game) {
        $gameId = (int)$game["id"];
    } else {
        // gamesへ新規登録
        $stmt = $pdo->prepare("
            INSERT INTO games (
                igdb_id,
                name,
                cover,
                genres
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $igdbId,
            $gameName,
            $gameCover,
            $gameGenres
        ]);

        $gameId = (int)$pdo->lastInsertId();
    }

    // すでにお気に入り登録済みか確認
    $stmt = $pdo->prepare("
        SELECT id
        FROM favorite_games
        WHERE user_id = ?
            AND game_id = ?
    ");

    $stmt->execute([
        $userId,
        $gameId
    ]);

    $favorite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$favorite) {
        $stmt = $pdo->prepare("
            INSERT INTO favorite_games (
                user_id,
                game_id
            )
            VALUES (?, ?)
        ");

        $stmt->execute([
            $userId,
            $gameId
        ]);
    }

    $pdo->commit();

    header("Location: profile.php");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}