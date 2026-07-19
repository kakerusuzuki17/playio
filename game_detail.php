<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache"); // キャッシュを無効化

// ログインしていなければログイン画面へ
if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";
$currentId = $_SESSION["id"];

date_default_timezone_set("Asia/Tokyo");

$env = require __DIR__ . "/config/env.php";

require __DIR__ . "/config/igdb_token.php";

$clientId = $env["IGDB_CLIENT_ID"];
$accessToken = getIgdbAccessToken();

$todayTimestamp = strtotime("today");

$igdbId = filter_input(
    INPUT_GET,
    "igdb_id",
    FILTER_VALIDATE_INT
);

if (!$igdbId) {
    http_response_code(400);
    exit("正しいigdb_idを指定してください。");
}

$query = "
fields
    id,
    name,
    summary,
    storyline,
    cover.url,
    genres.name,
    platforms.name,
    first_release_date,
    screenshots.url,
    videos.video_id,
    involved_companies.company.name,
    involved_companies.developer,
    involved_companies.publisher,
    game_type.type;

where id = {$igdbId};

limit 1;
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
        "Accept: application/json",
    ],

    CURLOPT_POSTFIELDS => $query,
]);

$response = curl_exec($ch);

if ($response === false) {
    $curlError = curl_error($ch);
    curl_close($ch);

    exit(
        "IGDB通信エラー: "
        . htmlspecialchars($curlError)
    );
}

$statusCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

curl_close($ch);

if ($statusCode !== 200) {
    exit(
        "IGDB APIエラー HTTP "
        . htmlspecialchars((string)$statusCode)
        . "<pre>"
        . htmlspecialchars($response)
        . "</pre>"
    );
}

$gameResults = json_decode(
    $response,
    true
);

if (
    !is_array($gameResults)
    || empty($gameResults)
) {
    http_response_code(404);
    exit("ゲームが見つかりませんでした。");
}

// limit 1なので、先頭の1件を取り出す
$gameDetail = $gameResults[0];

$gameName =
    $gameDetail["name"]
    ?? "タイトル不明";

$summary =
    $gameDetail["summary"]
    ?? "ゲーム説明は登録されていません。";

$storyline =
    $gameDetail["storyline"]
    ?? null;

$coverUrl =
    $gameDetail["cover"]["url"]
    ?? "";

if (
    $coverUrl !== "" &&
    str_starts_with($coverUrl, "//")
) {
    $coverUrl = "https:" . $coverUrl;
}

if ($coverUrl !== "") {
    $coverUrl = str_replace(
        "t_thumb",
        "t_cover_big",
        $coverUrl
    );
}

if (
    $coverUrl
    && str_starts_with($coverUrl, "//")
) {
    $coverUrl = "https:" . $coverUrl;
}

// 高解像度のカバーへ変更
if ($coverUrl) {
    $coverUrl = str_replace(
        "t_thumb",
        "t_cover_big",
        $coverUrl
    );
}

$genres = array_column(
    $gameDetail["genres"] ?? [],
    "name"
);

$genreText =
    !empty($genres)
        ? implode(" / ", $genres)
        : "ジャンル不明";

$platforms = array_column(
    $gameDetail["platforms"] ?? [],
    "name"
);

$platformText =
    !empty($platforms)
        ? implode(" / ", $platforms)
        : "対応機種不明";

$releaseTimestamp =
    $gameDetail["first_release_date"]
    ?? null;

$releaseDate =
    $releaseTimestamp
        ? date("Y年n月j日", $releaseTimestamp)
        : "発売日未定";

$gameType =
    $gameDetail["game_type"]["type"]
    ?? null;

    $developers = [];
$publishers = [];

foreach (
    $gameDetail["involved_companies"] ?? []
    as $involvedCompany
) {
    $companyName =
        $involvedCompany["company"]["name"]
        ?? null;

    if (!$companyName) {
        continue;
    }

    if (
        !empty($involvedCompany["developer"])
    ) {
        $developers[] = $companyName;
    }

    if (
        !empty($involvedCompany["publisher"])
    ) {
        $publishers[] = $companyName;
    }
}

$developers = array_values(
    array_unique($developers)
);

$publishers = array_values(
    array_unique($publishers)
);

$screenshots = [];

foreach (
    $gameDetail["screenshots"] ?? []
    as $screenshot
) {
    $url =
        $screenshot["url"]
        ?? null;

    if (!$url) {
        continue;
    }

    if (str_starts_with($url, "//")) {
        $url = "https:" . $url;
    }

    $url = str_replace(
        "t_thumb",
        "t_screenshot_big",
        $url
    );

    $screenshots[] = $url;
}

// お気に入りゲーム一覧を取得
$sql = "SELECT
            favorite_games.game_id AS game_id,
            games.igdb_id AS igdb_id,
            games.name AS game_name,
            games.cover AS game_cover,
            (
                SELECT COUNT(*)
                FROM favorite_games
                WHERE favorite_games.game_id = games.id
            ) AS favorite_count,
            EXISTS (
                SELECT 1
                FROM favorite_games
                WHERE favorite_games.game_id = games.id
                    AND favorite_games.user_id = ?
            ) AS favorite_by_me
        FROM favorite_games
        LEFT JOIN games
            ON favorite_games.game_id = games.id
        WHERE user_id = ?
        ORDER BY game_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$currentId,$currentId]);
$favoriteGames = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| gamesテーブルへUPSERT
|--------------------------------------------------------------------------
*/

$releaseDateForDb =
    $releaseTimestamp
        ? date("Y-m-d", $releaseTimestamp)
        : null;

$genreTextForDb =
    !empty($genres)
        ? implode(", ", $genres)
        : "";

$coverForDb = $coverUrl ?? "";

$sql = "
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

$stmt = $pdo->prepare($sql);

$stmt->execute([

    ":igdb_id" => $igdbId,

    ":name" => $gameName,

    ":cover" => $coverForDb,

    ":genres" => $genreTextForDb,

    ":release_date" => $releaseDateForDb,

]);

$gameId = (int)$pdo->lastInsertId();


/*
|--------------------------------------------------------------------------
| このゲームのお気に入り情報を取得
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        COUNT(*) AS favorite_count,
        MAX(
            CASE
                WHEN user_id = :user_id THEN 1
                ELSE 0
            END
        ) AS favorite_by_me
    FROM favorite_games
    WHERE game_id = :game_id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":user_id" => $currentId,
    ":game_id" => $gameId,
]);

$favoriteInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$favoriteInfo) {
    $favoriteInfo = [
        "favorite_count" => 0,
        "favorite_by_me" => 0,
    ];
}

$favoriteInfo["favorite_count"] =
    (int)$favoriteInfo["favorite_count"];

$favoriteInfo["favorite_by_me"] =
    (bool)$favoriteInfo["favorite_by_me"];
?>

<!DOCTYPE html>
<html lang="ja">

    <head>
        <link rel="icon" type="image/png" href="assets/images/favicon.png">
        <meta charset="UTF-8">
        <title>ゲーム詳細</title>
        <link rel="stylesheet" href="assets/css/common.css">
        <link rel="stylesheet" href="assets/css/layout.css">
        <link rel="stylesheet" href="assets/css/components.css">
        <link rel="stylesheet" href="assets/css/game_detail.css">
    </head>

    <body>

    <div class="container">

        <!-- 左メニュー -->
        <?php require __DIR__ . "/includes/sidebar.php"; ?>

        <!-- メイン -->
        <main class="main game-detail-main">

            <section class="game-detail-header">

                <div class="game-detail-cover-area">

                    <?php if ($coverUrl): ?>

                        <img
                            class="game-detail-cover"
                            src="<?= htmlspecialchars($coverUrl) ?>"
                            alt="<?= htmlspecialchars($gameName) ?>"
                        >

                    <?php else: ?>

                        <div class="game-detail-no-cover">
                            NO IMAGE
                        </div>

                    <?php endif; ?>

                </div>

                <div class="game-detail-info">

                    <h1>
                        <?= htmlspecialchars($gameName) ?>
                    </h1>
                    <button
                        type="button"
                        class="favorite-btn <?= $favoriteInfo["favorite_by_me"] ? "favorite" : "" ?>"
                        data-game-id="<?= $gameId ?>"
                    >
                        <span class="star">
                            <?= $favoriteInfo["favorite_by_me"] ? "⭐" : "☆" ?>
                        </span>

                        <span class="favorite-count">
                            <?= $favoriteInfo["favorite_count"] ?>
                        </span>
                    </button>

                    <?php if ($gameType): ?>
                        <span class="game-type-badge">
                            <?= htmlspecialchars($gameType) ?>
                        </span>
                    <?php endif; ?>
                        <a href="search_posts.php?igdb_id=<?= $igdbId ?>&game_name=<?= $gameName ?>&game_cover=<?= $coverUrl ?>&game_genres=<?= $genreText ?>">
                    みんなの投稿へ >>
                    <dl class="game-meta">

                        <div>
                            <dt>発売日</dt>
                            <dd>
                                <?= htmlspecialchars($releaseDate) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>ジャンル</dt>
                            <dd>
                                <?= htmlspecialchars($genreText) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>対応機種</dt>
                            <dd>
                                <?= htmlspecialchars($platformText) ?>
                            </dd>
                        </div>

                        <?php if (!empty($developers)): ?>
                            <div>
                                <dt>開発</dt>
                                <dd>
                                    <?= htmlspecialchars(
                                        implode(" / ", $developers)
                                    ) ?>
                                </dd>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($publishers)): ?>
                            <div>
                                <dt>販売</dt>
                                <dd>
                                    <?= htmlspecialchars(
                                        implode(" / ", $publishers)
                                    ) ?>
                                </dd>
                            </div>
                        <?php endif; ?>

                    </dl>

                </div>

            </section>

            <section class="game-detail-section">

                <h2>ゲーム概要</h2>

                <p class="game-summary">
                    <?= nl2br(
                        htmlspecialchars($summary)
                    ) ?>
                </p>

            </section>

            <?php if ($storyline): ?>

                <section class="game-detail-section">

                    <h2>ストーリー</h2>

                    <p class="game-summary">
                        <?= nl2br(
                            htmlspecialchars($storyline)
                        ) ?>
                    </p>

                </section>

            <?php endif; ?>

            <?php if (!empty($screenshots)): ?>

                <section class="game-detail-section">

                    <h2>スクリーンショット</h2>

                    <div class="screenshot-grid">

                        <?php foreach (
                            $screenshots
                            as $screenshotUrl
                        ): ?>

                            <img
                                src="<?= htmlspecialchars($screenshotUrl) ?>"
                                alt="<?= htmlspecialchars($gameName) ?>のスクリーンショット"
                                class="zoom-image"
                            >

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endif; ?>

        </main>

    </div>

    <div id="imageModal" class="image-modal">

        <span id="closeImageModal">&times;</span>

        <img
            id="modalImage"
            class="image-modal-content"
            src=""
            alt=""
        >

    </div>

    <script src="./assets/js/favorite.js"></script>
    <script src="assets/js/imageModal.js"></script>

    </body>
</html>