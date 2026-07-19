<?php

header("Content-Type: application/json; charset=UTF-8");

require "../../config/db.php";

$userId = filter_input(
    INPUT_GET,
    "user_id",
    FILTER_VALIDATE_INT
);

if (!$userId) {
    http_response_code(400);

    echo json_encode(
        [
            "success" => false,
            "message" => "正しいuser_idを指定してください"
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

try {
    // ユーザー基本情報
    $userSql = "SELECT
                    users.id,
                    users.account_id,
                    users.user_name,

                    (
                        SELECT COUNT(*)
                        FROM posts
                        WHERE posts.user_id = users.id
                    ) AS post_count,

                    (
                        SELECT COUNT(*)
                        FROM likes

                        JOIN posts
                            ON posts.id = likes.post_id

                        WHERE posts.user_id = users.id
                    ) AS received_like_count

                FROM users

                WHERE users.id = ?";

    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([$userId]);

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);

        echo json_encode(
            [
                "success" => false,
                "message" => "ユーザーが見つかりません"
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    // お気に入りゲーム
    $favoriteSql = "SELECT
                        games.id,
                        games.igdb_id,
                        games.name,
                        games.cover,
                        games.genres,
                        games.release_date

                    FROM favorite_games

                    JOIN games
                        ON games.id = favorite_games.game_id

                    WHERE favorite_games.user_id = ?

                    ORDER BY favorite_games.created_at DESC

                    LIMIT 10";

    $favoriteStmt = $pdo->prepare($favoriteSql);
    $favoriteStmt->execute([$userId]);

    $favoriteGames =
        $favoriteStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($favoriteGames as &$game) {
        if (
            !empty($game["cover"])
            && str_starts_with($game["cover"], "//")
        ) {
            $game["cover"] =
                "https:" . $game["cover"];
        }
    }

    unset($game);

    $postSql = "SELECT
                posts.id,
                posts.content,
                posts.created_at,

                users.account_id,
                users.user_name,

                games.name AS game_name,
                games.cover AS game_cover,
                games.genres AS game_genres,
                games.release_date AS game_released,

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
                    AND likes.user_id = ?
                ) AS liked_by_me

            FROM posts

            JOIN users
                ON users.id = posts.user_id

            LEFT JOIN games
                ON games.id = posts.game_id

            LEFT JOIN categories
                ON categories.id = posts.category_id

            LEFT JOIN highscore
                ON highscore.post_id = posts.id

            LEFT JOIN cleartime
                ON cleartime.post_id = posts.id

            WHERE posts.user_id = ?

            ORDER BY posts.created_at DESC";

$postStmt = $pdo->prepare($postSql);

$postStmt->execute([
    $userId,
    $userId
]);

$posts = $postStmt->fetchAll(
    PDO::FETCH_ASSOC
);

foreach ($posts as &$post) {
    if (
        !empty($post["game_cover"])
        && str_starts_with(
            $post["game_cover"],
            "//"
        )
    ) {
        $post["game_cover"] =
            "https:" . $post["game_cover"];
    }

    $tagStmt = $pdo->prepare("
        SELECT
            tags.id,
            tags.name

        FROM post_tags

        JOIN tags
            ON tags.id = post_tags.tag_id

        WHERE post_tags.post_id = ?

        ORDER BY tags.name
    ");

    $tagStmt->execute([
        $post["id"]
    ]);

    $post["tags"] =
        $tagStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

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

    $mediaStmt->execute([
        $post["id"]
    ]);

    $mediaList =
        $mediaStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    foreach ($mediaList as &$media) {
        $media["url"] =
            "http://192.168.1.3/playio/uploads/"
            . rawurlencode(
                $media["file_name"]
            );
    }

    unset($media);

    $post["media"] = $mediaList;
}

    unset($post);

    echo json_encode(
    [
        "success" => true,
        "user" => $user,
        "favorite_games" => $favoriteGames,
        "posts" => $posts
    ],
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);

} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode(
        [
            "success" => false,
            "message" =>
                "プロフィールの取得に失敗しました"
        ],
        JSON_UNESCAPED_UNICODE
    );
}