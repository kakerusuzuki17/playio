<?php
session_start();

header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache"); // キャッシュを無効化

date_default_timezone_set("Asia/Tokyo");

require "config/db.php";

$env = require __DIR__ . "/config/env.php";

require __DIR__ . "/config/igdb_token.php";

$clientId = $env["IGDB_CLIENT_ID"];
$accessToken = getIgdbAccessToken();

$todayTimestamp = strtotime("today");

$query = "
fields
    date,
    human,
    game.id,
    game.name,
    game.cover.url,
    game.genres.name,
    platform.id,
    platform.name,
    region,
    status;

where date >= {$todayTimestamp}
    & game.game_type != 1
    & game.game_type != 2
    & game.game_type != 3
    & game.game_type != 13
    & game.game_type != 14;

sort date asc;

limit 500;
";

$ch = curl_init(
    "https://api.igdb.com/v4/release_dates"
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

$releaseDates = json_decode(
    $response,
    true
);

if (!is_array($releaseDates)) {
    exit(
        "IGDBのレスポンスを解析できませんでした。"
    );
}

$coverUrl = $game["cover"]["url"] ?? null;

foreach ($releaseDates as &$release) {
    $coverUrl =
        $release["game"]["cover"]["url"]
        ?? null;

    if (!$coverUrl) {
        continue;
    }

    if (str_starts_with($coverUrl, "//")) {
        $coverUrl = "https:" . $coverUrl;
    }

    $coverUrl = str_replace(
        "t_thumb",
        "t_cover_big",
        $coverUrl
    );

    $release["game"]["cover"]["url"] =
        $coverUrl;
}

unset($release);

// 同じゲーム名・同じ発売日を1つにまとめる
$groupedReleaseDates = [];

foreach ($releaseDates as $release) {
    $gameName =
        $release["game"]["name"]
        ?? "";

    $releaseTimestamp =
        $release["date"]
        ?? null;

    $platformName =
        $release["platform"]["name"]
        ?? null;

    if (
        $gameName === ""
        || $releaseTimestamp === null
    ) {
        continue;
    }

    // ゲーム名 + 発売日をグループ化用のキーにする
    $key =
        $gameName
        . "_"
        . date(
            "Y-m-d",
            $releaseTimestamp
        );

    // まだ同じゲーム・発売日が登録されていない場合
    if (
        !isset($groupedReleaseDates[$key])
    ) {
        $release["platforms"] = [];

        $groupedReleaseDates[$key] =
            $release;
    }

    // 機種名がある場合はplatformsへ追加
    if ($platformName !== null) {
        $groupedReleaseDates[$key]
            ["platforms"][] =
                $platformName;
    }
}

// 連番配列へ戻す
$releaseDates =
    array_values(
        $groupedReleaseDates
    );

// 発売日ごとにゲームをまとめる
$releasesByDate = [];

foreach ($releaseDates as $release) {
    $releaseTimestamp =
        $release["date"]
        ?? null;

    if ($releaseTimestamp === null) {
        continue;
    }

    $dateKey = date(
        "Y-m-d",
        $releaseTimestamp
    );

    if (
        !isset($releasesByDate[$dateKey])
    ) {
        $releasesByDate[$dateKey] = [];
    }

    $releasesByDate[$dateKey][] =
        $release;
}

$currentId = $_SESSION["id"];
// お気に入りゲーム一覧を取得
$sql = "SELECT
            favorite_games.game_id AS game_id,
            games.igdb_id AS igdb_id,
            games.name AS game_name,
            games.cover AS game_cover
        FROM favorite_games
        LEFT JOIN games
            ON favorite_games.game_id = games.id
        WHERE user_id = ?
        ORDER BY game_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$currentId]);
$favoriteGames = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <meta charset="UTF-8">
    <title>発売予定ゲーム</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/upcoming_games.css">
</head>

<body>

    <div class="page-layout">

        <?php require __DIR__ . "/includes/sidebar.php"; ?>

        <!-- メイン部分 -->
        <main class="release-main">

            <h1>
                これから発売するゲーム
            </h1>

            <div class="release-list">

                <?php if (empty($releasesByDate)): ?>

                    <p>
                        発売予定ゲームが見つかりませんでした。
                    </p>

                <?php else: ?>

                    <?php foreach (
                        $releasesByDate
                        as $dateKey => $dailyReleases
                    ): ?>

                        <?php
                        $dateTitle = date(
                            "n/j",
                            strtotime($dateKey)
                        );
                        ?>

                        <section class="release-date-group">

                            <h2 class="date-heading">
                                <?= htmlspecialchars($dateTitle) ?>
                            </h2>

                            <div class="daily-release-list">

                                <?php foreach (
                                    $dailyReleases
                                    as $release
                                ): ?>

                                    <?php
                                    $game =
                                        $release["game"]
                                        ?? [];

                                    $gameName =
                                        $game["name"]
                                        ?? "タイトル未定";

                                    $coverUrl =
                                        $game["cover"]["url"]
                                        ?? null;
                                    ?>

                                    <article class="release-game">

                                        <a
                                            class="release-game-link"
                                            href="game_detail.php?igdb_id=<?= urlencode($game["id"]) ?>"
                                        >

                                            <?php if ($coverUrl): ?>

                                                <div class="upcoming-game-cover-area">

                                                    <img
                                                        class="upcoming-game-cover"
                                                        src="<?= htmlspecialchars($coverUrl, ENT_QUOTES, "UTF-8") ?>"
                                                        alt="<?= htmlspecialchars($gameName, ENT_QUOTES, "UTF-8") ?>"
                                                    >

                                                </div>

                                            <?php else: ?>

                                                <div class="no-cover">
                                                    NO IMAGE
                                                </div>

                                            <?php endif; ?>

                                            <div class="release-game-info">

                                                <div class="release-game-title">
                                                    <?= htmlspecialchars($gameName) ?>
                                                </div>

                                                <?php if (!empty($release["platforms"])): ?>

                                                    <div class="release-platforms">
                                                        <?= htmlspecialchars(
                                                            implode(" / ", $release["platforms"])
                                                        ) ?>
                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        </a>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                        </section>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </main>

    </div>
</body>
</html>