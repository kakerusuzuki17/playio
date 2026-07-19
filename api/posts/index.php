<?php

header("Content-Type: application/json; charset=UTF-8");

require "../../config/db.php";

$sql = "SELECT
            posts.id,
            posts.content,
            posts.created_at,

            users.account_id,
            users.user_name,

            games.name AS game_name,
            games.cover AS game_cover,
            games.genres AS game_genres,
            games.release_date AS game_released,

            categories.id AS category_id,
            categories.name AS category_name,

            highscore.score AS score,
            cleartime.time_ms AS time_ms,

            (
                SELECT COUNT(*)
                FROM likes
                WHERE likes.post_id = posts.id
            ) AS like_count,

            EXISTS (
                SELECT 1
                FROM likes
                WHERE likes.post_id = posts.id
                AND likes.user_id = 3
            ) AS liked_by_me

        FROM posts

        JOIN users
            ON posts.user_id = users.id

        LEFT JOIN games
            ON posts.game_id = games.id

        LEFT JOIN categories
            ON posts.category_id = categories.id

        LEFT JOIN highscore
            ON posts.id = highscore.post_id

        LEFT JOIN cleartime
            ON posts.id = cleartime.post_id

        WHERE posts.spoiler = 0

        ORDER BY posts.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($posts as &$post) {
    if (
        !empty($post["game_cover"])
        && str_starts_with($post["game_cover"], "//")
    ) {
        $post["game_cover"] =
            "https:" . $post["game_cover"];
    }

    // タグ
    $tagStmt = $pdo->prepare("
        SELECT tags.id, tags.name
        FROM post_tags
        JOIN tags
            ON post_tags.tag_id = tags.id
        WHERE post_tags.post_id = ?
        ORDER BY tags.name
    ");

    $tagStmt->execute([$post["id"]]);

    $post["tags"] =
        $tagStmt->fetchAll(PDO::FETCH_ASSOC);

    // 投稿画像・動画
    $mediaStmt = $pdo->prepare("
        SELECT
            id,
            file_name,
            file_type,
            display_order
        FROM post_media
        WHERE post_id = ?
        ORDER BY display_order
    ");

    $mediaStmt->execute([$post["id"]]);

    $mediaList =
        $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($mediaList as &$media) {
        $media["url"] =
            "http://192.168.1.3/playio/uploads/"
            . rawurlencode($media["file_name"]);
    }

    unset($media);

    $post["media"] = $mediaList;
}

unset($post);

echo json_encode(
    [
        "success" => true,
        "posts" => $posts
    ],
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);