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
$currentAccountId = $_SESSION["account_id"];

/*
|--------------------------------------------------------------------------
| 検索条件
|--------------------------------------------------------------------------
*/

$gameId = "";
$igdbId = "";

$gameName = "";
$gameCover = "";
$gameGenres = "";

$categoryId = "";
$divisionId = "";

$keyword = "";
$tagsText = "";

$spoiler = false;

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

    case "random":
        $orderBy = "RAND()";
        break;

    case "new":
    default:
        $sort = "new";
        $orderBy = "posts.created_at DESC";
        break;
}

// 投稿一覧を取得
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
            categories.id AS category_id,
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
        WHERE posts.spoiler = 0
        ORDER BY {$orderBy}";

$stmt = $pdo->prepare($sql);
$stmt->execute([$currentId]);
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
?>

<!DOCTYPE html>
<html lang="ja">

    <head>
        <link rel="icon" type="image/png" href="assets/images/favicon.png">
        <meta charset="UTF-8">
        <title>ホーム</title>
        <link rel="stylesheet" href="assets/css/common.css">
        <link rel="stylesheet" href="assets/css/layout.css">
        <link rel="stylesheet" href="assets/css/components.css">
        <link rel="stylesheet" href="assets/css/home.css">
        <link rel="stylesheet" href="assets/css/search_posts.css">
    </head>

    <body>

        <div class="container">

            <!-- 左メニュー -->
            <?php require __DIR__ . "/includes/sidebar.php"; ?>

            <!-- メイン -->
            <main class="main">

                <div class="post-box">
                    <?php
                    require __DIR__ . "/includes/search_form.php";
                    ?>

                    <!-- タイムライン -->
                    <div class="timeline-toolbar">

                        <h3>投稿一覧</h3>

                        <form action="" method="get" class="sort-form">

                            <!-- 検索条件を維持 -->
                            <input
                                type="hidden"
                                name="igdb_id"
                                value="<?= htmlspecialchars($igdbId ?? "") ?>"
                            >

                            <input
                                type="hidden"
                                name="category_id"
                                value="<?= htmlspecialchars($categoryId ?? "") ?>"
                            >

                            <input
                                type="hidden"
                                name="keyword"
                                value="<?= htmlspecialchars($keyword ?? "") ?>"
                            >

                            <input
                                type="hidden"
                                name="tags"
                                value="<?= htmlspecialchars($tagsText ?? "") ?>"
                            >

                            <input
                                type="hidden"
                                name="game_name"
                                value="<?= htmlspecialchars(
                                    $gameName ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="game_cover"
                                value="<?= htmlspecialchars(
                                    $gameCover ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="game_genres"
                                value="<?= htmlspecialchars(
                                    $gameGenres ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                            <?php if (!empty($spoiler)): ?>
                                <input type="hidden" name="spoiler" value="1">
                            <?php endif; ?>

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

                                <option
                                    value="random"
                                    <?= $sort === "random" ? "selected" : "" ?>
                                >
                                    ランダム
                                </option>
                            </select>

                        </form>

                    </div>
                    <div class="timeline">
                        <?php foreach ($posts as $post): ?>

                            <div class="post">

                                <div class="post-header">

                                    <a href="profile.php?account_id=<?= $post["account_id"] ?>">
                                        <div class="post-user-icon">
                                            <?= htmlspecialchars(
                                                mb_substr($post["user_name"], 0, 1)
                                            ) ?>
                                        </div>
                                    </a>
                                    <div class="post-user-info">

                                    <a href="profile.php?account_id=<?= $post["account_id"] ?>">
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
                                    </a>

                                </div>

                                <?php
                                    $mediaList = $postMedia[$post["id"]] ?? [];
                                ?>

                                <?php if (!empty($mediaList)): ?>
                                    <div class="post-media-grid media-count-<?= count($mediaList) ?>">

                                        <?php foreach ($mediaList as $media): ?>
                                            <?php
                                                $fileUrl =
                                                    "uploads/" .
                                                    rawurlencode($media["file_name"]);
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
                                                </video>

                                            <?php endif; ?>

                                        <?php endforeach; ?>

                                    </div>
                                <?php endif; ?>

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

                                <div class="post-content">
                                    <?= nl2br(htmlspecialchars($post["content"])) ?>
                                </div>

                                <div class="post-footer">

                                    <!-- 1段目 -->
                                    <div class="post-footer-info">

                                        <div class="post-footer-category">

                                            <div class="post-category-group">

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
                                                        <a
                                                            class="post-tag" href="search_posts.php?tags=<?= $tag["name"] ?>"
                                                        >
                                                            #<?= htmlspecialchars($tag["name"]) ?>
                                                        </a>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php endif; ?>


                                            <!-- いいね -->
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

                                            </div>

                                        </div>

                                        <!-- 右：ゲーム情報 -->
                                        <?php if (!empty($post["game_name"])): ?>

                                            <div class="post-footer-game">

                                                <!-- GAMEラベル -->
                                                <span class="post-game-label">
                                                    GAME
                                                </span>

                                                <!-- ゲーム詳細 -->
                                                <div class="post-footer-game-info">

                                                    <!-- ゲーム名 -->
                                                    <div class="post-footer-game-name">
                                                        <?= htmlspecialchars($post["game_name"]) ?>
                                                    </div>

                                                    <!-- ジャンル -->
                                                    <?php if (!empty($post["game_genres"])): ?>

                                                        <div class="post-footer-game-genres">
                                                            <?= htmlspecialchars($post["game_genres"]) ?>
                                                        </div>

                                                    <?php endif; ?>

                                                    <!-- 発売日 -->
                                                    <?php if (!empty($post["game_released"])): ?>

                                                        <div class="post-footer-game-release">

                                                            <?=
                                                                htmlspecialchars(
                                                                    date(
                                                                        "Y年n月j日",
                                                                        strtotime($post["game_released"])
                                                                    )
                                                                )
                                                            ?>

                                                        </div>

                                                    <?php endif; ?>

                                                    <a href="game_detail.php?igdb_id=<?= $post["igdb_id"] ?>">
                                                    ゲーム詳細
                                                    </a>

                                                </div>

                                                <!-- ゲームカバー -->
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

                    </div>
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

        <script src="./assets/js/like.js"></script>
        <script src="./assets/js/share.js"></script>
        <script src="assets/js/reply.js"></script>
        <script src="assets/js/gameSearch.js"></script>
        <script src="assets/js/tagFilter.js"></script>
        <script src="assets/js/imageModal.js"></script>

        <script>
            const toggleSearchButton =
                document.getElementById("toggleSearchButton");

            const searchConditions =
                document.getElementById("searchConditions");

            if (toggleSearchButton && searchConditions) {
                toggleSearchButton.addEventListener("click", () => {
                    const isHidden =
                        searchConditions.style.display === "none";

                    searchConditions.style.display =
                        isHidden ? "block" : "none";

                    toggleSearchButton.textContent =
                        isHidden ? "条件を閉じる" : "条件を開く";
                });
            }
        </script>

    </body>
</html>