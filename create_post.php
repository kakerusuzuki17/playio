<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";

$content = $_POST["content"] ?? "";

$igdb_id = $_POST["igdb_id"] ?? null;
$game_name = $_POST["game_name"] ?? null;
$game_cover = $_POST["game_cover"] ?? null;
$game_genres = $_POST["game_genres"] ?? null;

$category_id = $_POST["category_select"] ?? null;

$division_id = $_POST["division_id"] ?? null;
$new_division_name = trim($_POST["new_division_name"] ?? "");

$spoiler = isset($_POST["spoiler"]) ? 1 : 0;

$high_score = $_POST["high_score"]?? null;

$clearHours = (int)($_POST["clear_time_hour"]) ?? 0;
$clearMinutes = (int)($_POST["clear_time_minute"]) ?? 0;
$clearSeconds = (int)($_POST["clear_time_second"]) ?? 0;
$clearCenciseconds = (int)($_POST["clear_time_cenci_seconds"]) ?? 0;

$clear_time_ms = // クリアタイムをミリ秒に変換
    ($clearHours * 3600000)
    + ($clearMinutes * 60000)
    + ($clearSeconds * 1000)
    + ($clearCenciseconds * 10);

    if ($content === "") {
    header("Location: new_post.php");
    exit;
}

if (empty($igdb_id) || empty($game_name)) {
    $_SESSION["error"] = "ゲームを検索結果から選択してください。";
    header("Location: new_post.php");
    exit;
}

if (empty($category_id)) {
    $_SESSION["error"] = "カテゴリを選択してください。";
    header("Location: new_post.php");
    exit;
}

// 既にgamesにあるか確認
$sql = "SELECT id FROM games
    WHERE igdb_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$igdb_id]);
$game = $stmt->fetch();

if ($game) {
    $game_id = $game["id"];
} else {
    $sql = "INSERT INTO games (
            igdb_id,
            name,
            cover,
            genres
        )
        VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $igdb_id,
        $game_name,
        $game_cover,
        $game_genres
    ]);

    $game_id = $pdo->lastInsertId();
}

$division_id =
    $division_id !== null && $division_id !== ""
        ? (int)$division_id
        : null;

if ($new_division_name !== "") {

    $sql = "
        INSERT INTO divisions (
            game_id,
            category_id,
            name,
            created_by
        )
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $game_id,
        $category_id,
        $new_division_name,
        $_SESSION["id"]
    ]);

    $division_id = (int)$pdo->lastInsertId();

} elseif ($division_id !== null) {

    /*
     * 選択された既存部門が、
     * 選択中のゲーム・カテゴリに属しているか確認
     */
    $sql = "
        SELECT id
        FROM divisions
        WHERE id = ?
        AND game_id = ?
        AND category_id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $division_id,
        $game_id,
        $category_id
    ]);

    if (!$stmt->fetchColumn()) {
        $_SESSION["error"] = "選択された部門が正しくありません。";
        header("Location: new_post.php");
        exit;
    }
}

// 投稿をpostsにINSERT
$sql = "
    INSERT INTO posts (
        user_id,
        game_id,
        category_id,
        division_id,
        content,
        spoiler
    )
    VALUES (?, ?, ?, ?, ?, ?)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $_SESSION["id"],
    $game_id,
    $category_id,
    $division_id,
    $content,
    $spoiler
]);

$post_id = $pdo->lastInsertId();

// タグ保存
$tagsText = $_POST["tags"] ?? "";

if ($tagsText !== "") {
    $tagNames = explode(",", $tagsText);

    foreach ($tagNames as $tagName) {
        $tagName = trim($tagName);

        if ($tagName === "") {
            continue;
        }

        // 既存タグ確認
        $sql = "SELECT id
            FROM tags
            WHERE name = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tagName]);

        $tag = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tag) {
            $tagId = $tag["id"];
        } else {
            // 新規作成
            $sql = "INSERT INTO tags (name)
                VALUES (?)
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tagName]);

            $tagId = $pdo->lastInsertId();
        }

        // 投稿とタグを紐付け
        $sql = "INSERT IGNORE INTO post_tags (
                post_id,
                tag_id
            )
            VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $post_id,
            $tagId
        ]);
    }
}

if ($category_id == 1 && $high_score !== null) {
    // ハイスコアをhighScoreにINSERT
    $sql= "INSERT INTO highScore(
            post_id,
            score
        )
        VALUES(?,?)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $post_id,
        $high_score
    ]);
} 
elseif ($category_id == 2 && $clear_time_ms !== null) {
    // クリアタイムをclearTimeにINSERT
    $sql = "INSERT INTO clearTime(
            post_id,
            time_ms
        )
        VALUES(?,?)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $post_id,
        $clear_time_ms
    ]);
}

// メディアファイルの保存
foreach ($_FILES["media"]["tmp_name"] as $i => $tmpName) {

    if ($tmpName === "") {
        continue;
    }

    $fileName = uniqid() . "_" . basename($_FILES["media"]["name"][$i]);

    move_uploaded_file(
        $tmpName,
        "uploads/" . $fileName
    );

    $mime = mime_content_type("uploads/" . $fileName);

    $type = str_starts_with($mime, "video/")
        ? "video"
        : "image";

    $sql = "INSERT INTO post_media
        (
            post_id,
            file_name,
            file_type,
            display_order
        )
        VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $post_id,
        $fileName,
        $type,
        $i + 1
    ]);
}

header("Location: home.php");
exit;