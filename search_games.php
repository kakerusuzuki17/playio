<?php

header("Content-Type: application/json; charset=utf-8");

$env = require __DIR__ . "/config/env.php";

// DB接続
require __DIR__ . "/config/db.php";

// IGDBアクセストークン取得処理
require __DIR__ . "/config/igdb_token.php";

$clientId = $env["IGDB_CLIENT_ID"];
$accessToken = getIgdbAccessToken();

$keyword = trim($_GET["keyword"] ?? "");

// 連続する半角・全角空白を1個の半角空白にする
$keyword = preg_replace(
    "/[\s　]+/u",
    " ",
    $keyword
);

if ($keyword === "") {
    echo json_encode([]);
    exit;
}

$searchKeyword = json_encode(
    $keyword,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

/*
 * IGDB検索
 */
$query = "
search {$searchKeyword};

fields
    id,
    name,
    cover.url,
    genres.name,
    first_release_date,
    game_type,
    game_type.type,
    version_parent;

where game_type != 1
    & game_type != 2
    & game_type != 3
    & game_type != 13
    & game_type != 14;

limit 10;
";

$ch = curl_init(
    "https://api.igdb.com/v4/games"
);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Client-ID: " . $clientId,
        "Authorization: Bearer " . $accessToken,
        "Content-Type: text/plain",
    ],
    CURLOPT_POSTFIELDS => $query,
]);

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(502);

    echo json_encode([
        "error" => "IGDBとの通信に失敗しました",
    ], JSON_UNESCAPED_UNICODE);

    curl_close($ch);
    exit;
}

$httpStatus = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

curl_close($ch);

if ($httpStatus < 200 || $httpStatus >= 300) {
    http_response_code(502);

    echo json_encode([
        "error" => "IGDB APIでエラーが発生しました",
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$games = json_decode($response, true);

if (!is_array($games)) {
    http_response_code(502);

    echo json_encode([
        "error" => "IGDBの応答を読み取れませんでした",
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
 * gamesテーブルへのUPSERT
 *
 * 新規ゲームの場合：
 *   INSERTされ、そのIDがlastInsertId()に入る
 *
 * 登録済みゲームの場合：
 *   UPDATEされ、LAST_INSERT_ID(id)によって
 *   既存のgames.idがlastInsertId()に入る
 */
$upsertSql = "
    INSERT INTO games (
        igdb_id,
        name,
        cover,
        genres,
        release_date
    )
    VALUES (
        :igdb_id,
        :name,
        :cover,
        :genres,
        :release_date
    )
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        cover = VALUES(cover),
        genres = VALUES(genres),
        release_date = VALUES(release_date),
        id = LAST_INSERT_ID(id)
";

$upsertStmt = $pdo->prepare($upsertSql);

$result = [];

$excludedGameTypes = [
    1,   // DLC
    2,   // Expansion
    3,   // Bundle
    13,  // Pack / Addon
    14,  // Update
];

foreach ($games as $game) {
    if (empty($game["id"])) {
        continue;
    }

    $gameTypeId =
        $game["game_type"]["id"]
        ?? $game["game_type"]
        ?? null;

    if (
        $gameTypeId !== null &&
        in_array(
            (int)$gameTypeId,
            $excludedGameTypes,
            true
        )
    ) {
        continue;
    }

    // 別エディションなどを除外
    if (!empty($game["version_parent"])) {
        continue;
    }

    $igdbId = (int)$game["id"];

    $gameName =
        trim($game["name"] ?? "");

    if ($gameName === "") {
        $gameName = "タイトル不明";
    }

    /*
     * カバー画像
     */
    $image = null;

    if (!empty($game["cover"]["url"])) {
        $coverUrl = $game["cover"]["url"];

        if (str_starts_with($coverUrl, "//")) {
            $coverUrl = "https:" . $coverUrl;
        }

        $image = str_replace(
            "t_thumb",
            "t_cover_big",
            $coverUrl
        );
    }

    /*
     * ジャンル
     *
     * JSONとして返すときは配列、
     * DBへ保存するときは文字列に変換します。
     */
    $genres = array_column(
        $game["genres"] ?? [],
        "name"
    );

    $genres = array_values(
        array_filter(
            $genres,
            static fn ($genre) =>
                is_string($genre) &&
                trim($genre) !== ""
        )
    );

    $genresText = implode(
        ", ",
        $genres
    );

    /*
     * 発売日
     */
    $releaseDate = null;

    if (!empty($game["first_release_date"])) {
        $releaseDate = date(
            "Y-m-d",
            (int)$game["first_release_date"]
        );
    }

    /*
     * gamesテーブルへUPSERT
     */
    try {
        $upsertStmt->execute([
            ":igdb_id" => $igdbId,
            ":name" => $gameName,
            ":cover" => $image,
            ":genres" => $genresText,
            ":release_date" => $releaseDate,
        ]);

        /*
         * 新規登録でも既存更新でも、
         * games.idが取得できます。
         */
        $gameId = (int)$pdo->lastInsertId();

        /*
         * 念のためlastInsertId()が0だった場合の対策
         */
        if ($gameId === 0) {
            $findStmt = $pdo->prepare(
                "SELECT id
                FROM games
                WHERE igdb_id = ?"
            );

            $findStmt->execute([
                $igdbId,
            ]);

            $gameId = (int)$findStmt->fetchColumn();
        }

    } catch (PDOException $e) {
        error_log(
            "ゲーム登録エラー: " .
            $e->getMessage()
        );
        continue;
    }

    $result[] = [
        "game_id" => $gameId,

        "id" => $igdbId,

        "name" => $gameName,

        "image" => $image,

        "genres" => $genres,

        "first_release_date" => $releaseDate,

        "game_type" =>
            $game["game_type"]["type"]
            ?? null,
    ];
}

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);