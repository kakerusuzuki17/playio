<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";

$userId = $_SESSION["id"];
$profileAccountId = $_GET["account_id"];

// ユーザー情報
$sql = "SELECT
        id,
        user_name,
        account_id
    FROM users
    WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch();

// ユーザー情報(プロフィール用)
$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE account_id = ?
");

$stmt->execute([$profileAccountId]);

$profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileUser) {
    http_response_code(404);
    exit("ユーザーが見つかりません");
}

$profileUserId = $profileUser["id"];
$profileUserName = $profileUser["user_name"];

// お気に入りゲーム一覧 (左メニュー)
$sql = "SELECT
        games.id AS game_id,
        games.igdb_id AS igdb_id,
        games.name AS game_name,
        games.cover AS game_cover,
        games.genres AS game_genres
    FROM favorite_games
    JOIN games
        ON favorite_games.game_id = games.id
    WHERE favorite_games.user_id = ?
    ORDER BY game_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$favoriteGames = $stmt->fetchAll();

// お気に入りゲーム一覧 (プロフィール)
$sql = "SELECT
        games.id AS game_id,
        games.igdb_id AS igdb_id,
        games.name AS game_name,
        games.cover AS game_cover,
        games.genres AS game_genres
    FROM favorite_games
    JOIN games
        ON favorite_games.game_id = games.id
    WHERE favorite_games.user_id = ?
    ORDER BY game_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$profileUserId]);
$profileFavoriteGames = $stmt->fetchAll();

// 並び替え条件
$sort = $_GET["sort"] ?? "new";

switch ($sort) {
    case "likes":
        $orderBy = "like_count DESC, posts.created_at DESC";
        break;

    case "score":
        $orderBy = "
            highscore.score IS NULL ASC,
            highscore.score DESC,
            posts.created_at DESC
        ";
        break;

    case "time":
        $orderBy = "
            cleartime.time_ms IS NULL ASC,
            cleartime.time_ms ASC,
            posts.created_at DESC
        ";
        break;

    case "new":
    default:
        $sort = "new";
        $orderBy = "posts.created_at DESC";
        break;
}

// 投稿一覧
$sql = "SELECT
            posts.id,
            posts.content,
            posts.created_at,
            posts.game_id AS game_id,
            games.igdb_id AS igdb_id,
            games.name AS game_name,
            games.cover AS game_cover,
            games.genres AS game_genres,
            games.release_date AS game_released,
            categories.id AS category_id,
            divisions.id AS division_id,
            divisions.name AS division_name,
            users.id AS user_id,
            users.account_id AS account_id,
            users.user_name,
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
            ) AS liked_by_me,
            highscore.score AS score,
            cleartime.time_ms AS time_ms,
            score_ranking.score_rank AS score_rank,
            time_ranking.time_rank AS time_rank,
            categories.name AS category_name
        FROM posts
        JOIN users
            ON posts.user_id = users.id
        LEFT JOIN games
            ON posts.game_id = games.id
        LEFT JOIN divisions
            ON posts.division_id = divisions.id
        LEFT JOIN highscore
            ON posts.id = highscore.post_id
        LEFT JOIN cleartime
            ON posts.id = cleartime.post_id
        LEFT JOIN categories
            ON posts.category_id = categories.id
        LEFT JOIN (
                SELECT
                    ranked_scores.post_id,
                    DENSE_RANK() OVER (
                        PARTITION BY
                            ranked_scores.game_id,
                            ranked_scores.category_id,
                            COALESCE(ranked_scores.division_id, 0)
                        ORDER BY
                            ranked_scores.score DESC
                    ) AS score_rank
                FROM (
                    SELECT
                        posts.id AS post_id,
                        posts.game_id,
                        posts.category_id,
                        posts.division_id,
                        highscore.score
                    FROM posts
                    JOIN highscore
                        ON posts.id = highscore.post_id
                    WHERE highscore.score IS NOT NULL
                ) AS ranked_scores
            ) AS score_ranking
                ON posts.id = score_ranking.post_id

            LEFT JOIN (
                SELECT
                    ranked_times.post_id,
                    DENSE_RANK() OVER (
                        PARTITION BY
                            ranked_times.game_id,
                            ranked_times.category_id,
                            COALESCE(ranked_times.division_id, 0)
                        ORDER BY
                            ranked_times.time_ms ASC
                    ) AS time_rank
                FROM (
                    SELECT
                        posts.id AS post_id,
                        posts.game_id,
                        posts.category_id,
                        posts.division_id,
                        cleartime.time_ms
                    FROM posts
                    JOIN cleartime
                        ON posts.id = cleartime.post_id
                    WHERE cleartime.time_ms IS NOT NULL
                ) AS ranked_times
            ) AS time_ranking
                ON posts.id = time_ranking.post_id
        WHERE posts.user_id = ?
        ORDER BY {$orderBy}";

$stmt = $pdo->prepare($sql);
$stmt->execute([$profileUserId, $profileUserId]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// タグ取得
$postTags = [];

if (!empty($posts)) {
    $postIds = array_column($posts, "id");

    $placeholders = implode(
        ",",
        array_fill(0, count($postIds), "?")
    );

    $sql = "SELECT
            post_tags.post_id,
            tags.id AS tag_id,
            tags.name AS tag_name
        FROM post_tags
        JOIN tags
            ON post_tags.tag_id = tags.id
        WHERE post_tags.post_id IN ($placeholders)
        ORDER BY
            post_tags.post_id,
            tags.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($postIds);

    $tagRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tagRows as $tag) {
        $postTags[$tag["post_id"]][] = [
            "id" => $tag["tag_id"],
            "name" => $tag["tag_name"]
        ];
    }
}

// 画像・動画取得
$postMedia = [];

if (!empty($posts)) {
    $postIds = array_column($posts, "id");

    $placeholders = implode(
        ",",
        array_fill(0, count($postIds), "?")
    );

    $sql = "SELECT id,
            post_id,
            file_name,
            file_type,
            display_order
        FROM post_media
        WHERE post_id IN ($placeholders)
        ORDER BY post_id, display_order";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($postIds);
    $mediaRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($mediaRows as $media) {
        $postMedia[$media["post_id"]][] = $media;
    }
}

// タイム表示
function formatTimeMs($timeMs)
{
    if ($timeMs === null) {
        return null;
    }

    $timeMs = (int)$timeMs;

    $hours = intdiv($timeMs, 3600000);
    $minutes = intdiv($timeMs % 3600000, 60000);
    $seconds = intdiv($timeMs % 60000, 1000);
    $centiseconds = intdiv($timeMs % 1000, 10);

    return sprintf(
        "%d時間%d分%d秒%02d",
        $hours,
        $minutes,
        $seconds,
        $centiseconds
    );
}

$genreCounts = [];

foreach ($favoriteGames as $game) {
    $genresText = trim($game["game_genres"] ?? "");

    if ($genresText === "") {
        continue;
    }

    // 「RPG, Adventure」のような文字列を分割
    $genres = preg_split(
        '/\s*,\s*/',
        $genresText,
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    foreach ($genres as $genre) {
        if (!isset($genreCounts[$genre])) {
            $genreCounts[$genre] = 0;
        }

        $genreCounts[$genre]++;
    }
}

arsort($genreCounts);

$topGenre = !empty($genreCounts)
    ? array_key_first($genreCounts)
    : "未設定";

$favoriteGameCount = count($favoriteGames);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <meta charset="UTF-8">
    <title>プロフィール</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>

    <div class="container">

        <?php require __DIR__ . "/includes/sidebar.php"; ?>

        <main class="main">
            <section class="profile-favorite-games-section">
                <h3><?= $profileUserName ?>のお気に入りゲーム
                    <?php if ($userId == $profileUserId) :?>
                        <a href="edit_profile.php">＋ お気に入りゲームを追加</a></h3>

                        <div class="favorite-share-actions">

                            <button
                                type="button"
                                id="exportFavoriteGamesButton"
                                class="favorite-export-button"
                            >
                                お気に入りを画像にする
                            </button>

                            <?php
                                $profileUrl =
                                    "profile.php?account_id=" .
                                    urlencode($profileUser["account_id"]);
                                ?>

                                <button
                                    type="button"
                                    id="shareFavoriteGamesButton"
                                    class="favorite-share-button"
                                    data-profile-url="<?= htmlspecialchars(
                                        $profileUrl,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                    disabled
                                >
                                    Xで共有する
                                </button>

                        </div>
                    <?php endif ?>
                <div class="favorite-games">
                    <?php if (empty($profileFavoriteGames)): ?>
                        <p>まだお気に入りゲームが登録されていません。</p>
                    <?php endif; ?>

                    <?php foreach ($profileFavoriteGames as $game): ?>
                        <div class="favorite-game-card">
                            <a href="game_detail.php?igdb_id=<?= $game["igdb_id"] ?>">
                                <?php if (!empty($game["game_cover"])): ?>
                                    <div class="game-cartridge">

                                        <div class="game-cartridge-top">
                                            <span class="game-cartridge-line"></span>
                                            <span class="game-cartridge-line"></span>
                                            <span class="game-cartridge-line"></span>
                                        </div>

                                        <div class="game-cartridge-label">

                                            <img
                                                src="<?= htmlspecialchars(
                                                    $game["game_cover"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                                alt="<?= htmlspecialchars(
                                                    $game["game_name"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                                class="game-cartridge-cover"
                                            >

                                            <div class="game-cartridge-title">
                                                <?= htmlspecialchars(
                                                    $game["game_name"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </div>

                                        </div>

                                        <div class="game-cartridge-bottom">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>

                                    </div>

                                <?php endif; ?>
                            </a>
                            <?php if ($userId == $profileUserId) :?>
                                <form action="delete_favorite_games.php" method="post">
                                    <button
                                        type="submit"
                                        id="deleteButton"
                                        class="delete-button"
                                        data-game-id="<?= $game["game_id"] ?>"
                                    >
                                        <span class="delete">
                                            削除
                                        </span>
                                    </button>
                                    <input type="hidden" name="delete_favorite_games_id" id="deleteFavoriteGamesId" value="<?= $game["game_id"] ?>" >
                                </form>
                            <?php endif ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="timeline-toolbar">
                <h3><?= $profileUserName ?>の投稿一覧</h3>
                <form action="profile.php?account_id=<?= $_SESSION["account_id"] ?>" method="get" class="sort-form">
                    <select
                        name="sort"
                        onchange="this.form.submit()"
                    >
                        <option
                            value="new"
                            <?= $sort === "new" ? "selected" : "" ?>
                        >
                            新着順
                        </option>

                        <option
                            value="likes"
                            <?= $sort === "likes" ? "selected" : "" ?>
                        >
                            いいね順
                        </option>

                        <option
                            value="score"
                            <?= $sort === "score" ? "selected" : "" ?>
                        >
                            ハイスコア順
                        </option>

                        <option
                            value="time"
                            <?= $sort === "time" ? "selected" : "" ?>
                        >
                            タイム順
                        </option>
                    </select>

                    <input
                        type="hidden"
                        name="account_id"
                        value="<?= htmlspecialchars(
                            $profileAccountId,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >
                </form>
            </div>

            <div class="timeline">
                <section class="profile-posts">

                    <?php foreach ($posts as $post): ?>

                        <div class="post">

                            <!-- ユーザー情報 -->
                            <div class="post-header">

                                <div class="post-user-icon">
                                    <?= htmlspecialchars(
                                        mb_substr($post["user_name"], 0, 1)
                                    ) ?>
                                </div>
                                <div class="post-user-info">
                                    <div class="post-user-line">
                                        <strong>
                                            <?= htmlspecialchars($post["user_name"]) ?>
                                        </strong>
                                        <span>
                                            @<?= htmlspecialchars($post["account_id"]) ?>
                                        </span>
                                    </div>
                                    <small>
                                        <?= htmlspecialchars($post["created_at"]) ?>
                                    </small>
                                </div>
                            </div>

                            <!-- 画像・動画 -->
                            <?php $mediaList = $postMedia[$post["id"]] ?? []; ?>

                            <?php if (!empty($mediaList)): ?>

                                <div class="post-media-grid media-count-<?= count($mediaList) ?>">

                                    <?php foreach ($mediaList as $media): ?>

                                        <?php
                                        $fileUrl = "uploads/" . rawurlencode(
                                            $media["file_name"]
                                        );
                                        ?>

                                        <?php if ($media["file_type"] === "image"): ?>

                                            <img
                                                src="<?= htmlspecialchars($fileUrl) ?>"
                                                alt="投稿画像"
                                                class="post-media-item zoom-image"
                                            >

                                        <?php elseif ($media["file_type"] === "video"): ?>

                                            <video
                                                class="post-media-item"
                                                controls
                                                preload="metadata"
                                            >
                                                <source
                                                    src="<?= htmlspecialchars($fileUrl) ?>"
                                                >
                                                動画を再生できません。
                                            </video>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- ハイスコア -->
                            <div class="post-records">

                                <?php if ($post["score"] !== null): ?>
                                    <span class="ranking-badge">
                                        <?= htmlspecialchars($post["score_rank"]) ?>位
                                    </span>

                                    <span class="high-score">
                                        🏆 <?= htmlspecialchars($post["score"]) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($post["time_ms"] !== null): ?>
                                    <span class="ranking-badge">
                                        <?= htmlspecialchars($post["time_rank"]) ?>位
                                    </span>

                                    <span class="clear-time">
                                        ⏱ <?= htmlspecialchars(
                                            formatTimeMs($post["time_ms"])
                                        ) ?>
                                    </span>
                                <?php endif; ?>

                            </div>

                            <!-- 投稿本文 -->
                            <div class="post-content">
                                <?= nl2br(htmlspecialchars($post["content"])) ?>
                            </div>

                            <!-- 投稿フッター -->
                            <div class="post-footer">

                                <div class="post-footer-info">

                                    <!-- 左側 -->
                                    <div class="post-footer-category">

                                        <div class="post-category-group">

                                            <!-- カテゴリ -->
                                            <?php if (!empty($post["category_name"])): ?>
                                                <a
                                                    class="post-category"
                                                    href="search_posts.php?category_id=<?= $post["category_id"] ?>"
                                                >
                                                    <?= htmlspecialchars($post["category_name"]) ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!empty($post["division_name"])): ?>
                                                <a class="post-division" href="search_posts.php?game_id=<?= $post["game_id"] ?>&igdb_id=<?= $post["igdb_id"] ?>&game_name=<?= urlencode($post["game_name"]) ?>&game_cover=<?= urlencode($post["game_cover"]) ?>&game_genres=<?= urlencode($post["game_genres"]) ?>&category_id=<?= $post["category_id"] ?>&division_id=<?= $post["division_id"] ?>">
                                                    <?= htmlspecialchars($post["division_name"]) ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <!-- タグ -->
                                        <?php
                                        $tags = $postTags[$post["id"]] ?? [];
                                        ?>

                                        <?php if (!empty($tags)): ?>
                                            <div class="post-tags">
                                                <?php foreach ($tags as $tag): ?>
                                                    <a class="post-tag">
                                                        #<?= htmlspecialchars($tag["name"]) ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- いいね・削除 -->
                                        <div class="post-footer-actions">

                                            <button
                                                type="button"
                                                class="like-btn <?= $post["liked_by_me"] ? "liked" : "" ?>"
                                                data-post-id="<?= $post["id"] ?>"
                                            >
                                                <span class="heart">
                                                    <?= $post["liked_by_me"] ? "❤️" : "🤍" ?>
                                                </span>

                                                <span class="like-count">
                                                    <?= $post["like_count"] ?>
                                                </span>
                                            </button>

                                            <?php if ($userId == $profileUserId) :?>
                                                <form
                                                    action="delete_posts.php"
                                                    method="post"
                                                >
                                                    <button
                                                        type="submit"
                                                        class="delete-button"
                                                        data-post-id="<?= $post["id"] ?>"
                                                    >
                                                        <span class="delete">
                                                            削除
                                                        </span>
                                                    </button>

                                                    <input
                                                        type="hidden"
                                                        name="delete_post_id"
                                                        value="<?= $post["id"] ?>"
                                                    >
                                                </form>
                                            <?php endif ?>
                                        </div>
                                    </div>

                                    <!-- 右側：ゲーム情報 -->
                                    <?php if (!empty($post["game_name"])): ?>

                                        <div class="post-footer-game">

                                            <span class="post-game-label">
                                                GAME
                                            </span>

                                            <div class="post-footer-game-info">

                                                <div class="post-footer-game-name">
                                                    <?= htmlspecialchars($post["game_name"]) ?>
                                                </div>

                                                <?php if (!empty($post["game_genres"])): ?>

                                                    <div class="post-footer-game-genres">
                                                        <?= htmlspecialchars(
                                                            str_replace(
                                                                ",",
                                                                " / ",
                                                                $post["game_genres"]
                                                            )
                                                        ) ?>
                                                    </div>

                                                <?php endif; ?>

                                                <?php if (!empty($post["game_released"])): ?>

                                                    <div class="post-footer-game-release">
                                                        <?= htmlspecialchars(
                                                            date(
                                                                "Y年n月j日",
                                                                strtotime($post["game_released"])
                                                            )
                                                        ) ?>
                                                    </div>

                                                <?php endif; ?>

                                                <a href="game_detail.php?igdb_id=<?= $post["igdb_id"] ?>">
                                                    ゲーム詳細
                                                </a>

                                            </div>

                                            <?php if (!empty($post["game_cover"])): ?>
                                                <a href="search_posts.php?game_id=<?= $post["game_id"] ?>&igdb_id=<?= $post["igdb_id"] ?>&game_name=<?= $post["game_name"] ?>&game_cover=<?= $post["game_cover"] ?>&game_genres=<?= $post["game_genres"] ?>">
                                                    <img
                                                        src="<?= htmlspecialchars($post["game_cover"]) ?>"
                                                        alt="<?= htmlspecialchars($post["game_name"]) ?>"
                                                        class="post-footer-game-cover"
                                                    >
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>
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

    <script
        src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"
    ></script>
    <script src="./assets/js/like.js"></script>
    <script src="./assets/js/imageModal.js"></script>
    <script src="assets/js/favoriteShare.js"></script>

    <div id="favoriteShareCaptureWrapper">

        <div id="favoriteShareCapture">

            <!-- 上部 -->
            <div class="share-image-header">

                <div class="share-brand">

                    <div class="share-logo-text">
                        Playio
                    </div>
                </div>

                <div class="share-user">

                    <div class="share-user-info">

                        <strong>
                            <?= htmlspecialchars($profileUser["user_name"]) ?>
                        </strong>

                    </div>

                </div>

                <div class="share-stats">

                    <div class="share-stat">

                        <span class="share-stat-label">
                            お気に入りゲーム数
                        </span>

                        <strong>
                            <?= $favoriteGameCount ?>
                            <small>本</small>
                        </strong>

                    </div>

                    <div class="share-stat">

                        <span class="share-stat-label">
                            一番遊んでいるジャンル
                        </span>

                        <strong class="share-top-genre">
                            <?= htmlspecialchars(
                                $topGenre,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>

                    </div>

                </div>

            </div>

            <!-- タイトル -->
            <div class="share-main-title">
                My Favorite Games
            </div>

            <!-- ゲーム一覧 -->
            <div class="share-game-grid">

                <?php foreach (
                    array_slice($favoriteGames, 0, 20)
                    as $game
                ): ?>

                    <div class="share-game-cartridge">

                        <div class="share-cartridge-slots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>

                        <div class="share-game-label">

                            <div class="share-game-cover">
                                <img
                                    src="<?= htmlspecialchars(
                                        $game["game_cover"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                    alt=""
                                    crossorigin="anonymous"
                                >
                            </div>

                            <div class="share-game-name">
                                <?= htmlspecialchars(
                                    $game["game_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </div>

                        </div>

                        <div class="share-cartridge-terminals">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- 下部URL -->
            <div class="share-image-footer">

                <span>
                    ゲーム特化SNS「Playio」でいろいろなゲームの記録を見よう！残そう！
                </span>

                <strong>
                    <?php
                    $profileUrl =
                        "profile.php?account_id=" .
                        urlencode($profileUser["account_id"]);
                    ?>

                    <?= htmlspecialchars(
                        $profileUrl,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </strong>

            </div>

        </div>

        </div>

    </body>
</html>