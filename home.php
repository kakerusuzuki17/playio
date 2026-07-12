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
            games.name AS game_name,
            games.cover AS game_cover,
            games.genres AS game_genres,
            games.release_date AS game_released,
            users.account_id,
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
            categories.name AS category_name
        FROM posts
        JOIN users
            ON posts.user_id = users.id
        LEFT JOIN games
            ON posts.game_id = games.id
        LEFT JOIN highscore
            ON posts.id = highscore.post_id
        LEFT JOIN cleartime
            ON posts.id = cleartime.post_id
            LEFT JOIN categories
            ON posts.category_id = categories.id
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

// カテゴリ一覧を取得
$currentId = $_SESSION["id"];

$sql = "SELECT
            id,
            name
        FROM categories
        ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll();

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
        ORDER BY favorite_games.created_at DESC";

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
        <meta charset="UTF-8">
        <title>ホーム</title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>

    <body>

    <div class="container">

        <!-- 左メニュー -->
        <aside class="sidebar">

            <h2><a href="home.php" class="post-link">Playio</a></h2>

            <ul>
                <li><a href="home.php">🏠 ホーム</a></li>
                <li><a href="profile.php">👤 プロフィール</a></li>
                <li><a href="new_post.php" class="post-link">投稿する</a></li>
                <?php foreach ($favoriteGames as $favoriteGame): ?>
                    <li><a href="search_posts.php?igdb_id=<?= $favoriteGame["igdb_id"] ?>&game_name=<?= $favoriteGame["game_name"] ?>&game_cover=<?= $favoriteGame["game_cover"] ?>">
                    <?= $favoriteGame["game_name"] ?></a></li>
                <?php endforeach ?>
            </ul>

            <div class="account-info">
                <div class="account-icon">
                    <?= htmlspecialchars(mb_substr($_SESSION["user_name"], 0, 1)) ?>
                </div>

                <div>
                    <strong>
                        <?= htmlspecialchars($_SESSION["user_name"]) ?>
                    </strong>
                    <p>
                        @<?= htmlspecialchars($_SESSION["account_id"]) ?>
                    </p>
                    <a href="auth/logout.php">ログアウト</a>
                </div>
            </div>
        </aside>

        <!-- メイン -->
        <main class="main">

            <div class="post-box">
                <div class="search-panel">

                    <form action="search_posts.php" method="get">

                        <div class="search-panel-header">
                            <h3>投稿を検索</h3>

                            <button
                                type="button"
                                id="toggleSearchButton"
                                class="search-toggle-button"
                            >
                                条件を閉じる
                            </button>
                        </div>

                        <div id="searchConditions">

                            <div class="search-grid">

                                <!-- ゲーム検索 -->
                                <div class="search-field search-field-wide">
                                    <label for="gameSearch">ゲーム</label>

                                    <input
                                        type="text"
                                        id="gameSearch"
                                        placeholder="ゲーム名を検索"
                                        value="<?= htmlspecialchars(
                                            $gameName ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <div id="gameResults"></div>

                                    <?php
                                    $hasSelectedGame =
                                        !empty($igdbId);
                                    ?>

                                    <div
                                        id="selectedGame"
                                        style="<?= $hasSelectedGame
                                            ? "display:flex;"
                                            : "display:none;"
                                        ?>"
                                    >
                                        <img
                                            id="selectedGameCover"
                                            src="<?= htmlspecialchars(
                                                $gameCover ?? "",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            alt="ゲーム画像"
                                        >

                                        <div class="selected-game-info">
                                            <strong id="selectedGameName">
                                                <?= htmlspecialchars(
                                                    $gameName ?? "",
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </strong>

                                            <small id="selectedGameGenres">
                                                <?= htmlspecialchars(
                                                    $gameGenres ?? "",
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </small>
                                        </div>

                                        <button
                                            type="button"
                                            id="clearGameButton"
                                            class="clear-game-button"
                                        >
                                            ×
                                        </button>
                                    </div>

                                    <input
                                        type="hidden"
                                        name="igdb_id"
                                        id="igdbId"
                                        value="<?= htmlspecialchars(
                                            $igdbId ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="game_name"
                                        id="gameName"
                                        value="<?= htmlspecialchars(
                                            $gameName ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="game_cover"
                                        id="gameCover"
                                        value="<?= htmlspecialchars(
                                            $gameCover ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="game_genres"
                                        id="gameGenres"
                                        value="<?= htmlspecialchars(
                                            $gameGenres ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >
                                </div>

                                <!-- カテゴリ -->
                                <div class="search-field">
                                    <label for="categorySelect">カテゴリ</label>

                                    <select
                                        name="category_id"
                                        id="categorySelect"
                                    >
                                        <option value="">
                                            すべてのカテゴリ
                                        </option>

                                        <?php foreach ($categories as $category): ?>
                                            <option
                                                value="<?= (int)$category["id"] ?>"
                                                <?= ($categoryId ?? "") == $category["id"]
                                                    ? "selected"
                                                    : ""
                                                ?>
                                            >
                                                <?= htmlspecialchars($category["name"]) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- 投稿文 -->
                                <div class="search-field">
                                    <label for="keyword">投稿文</label>

                                    <input
                                        type="text"
                                        name="keyword"
                                        id="keyword"
                                        placeholder="投稿内容を検索"
                                        value="<?= htmlspecialchars(
                                            $keyword ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >
                                </div>

                                <!-- タグ -->
                                <div class="search-field search-field-wide">
                                    <label for="tagSearch">タグ</label>

                                    <input
                                        type="text"
                                        id="tagSearch"
                                        placeholder="タグを検索"
                                    >

                                    <div id="tagResults"></div>
                                    <div id="selectedTags"></div>

                                    <input
                                        type="hidden"
                                        name="tags"
                                        id="tagsInput"
                                        value="<?= htmlspecialchars(
                                            $tagsText ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >
                                </div>
                            </div>

                            <div class="search-footer">

                                <label class="spoiler-option">
                                    <input
                                        type="checkbox"
                                        name="spoiler"
                                        value="1"
                                        <?= !empty($spoiler)
                                            ? "checked"
                                            : ""
                                        ?>
                                    >
                                    <span>ネタバレを含める</span>
                                </label>

                                <div class="search-footer-actions">

                                    <a
                                        href="home.php"
                                        class="reset-search-button"
                                    >
                                        リセット
                                    </a>

                                    <button
                                        type="submit"
                                        class="search-submit-button"
                                    >
                                        🔍 検索
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

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
                                            class="post-media-item"
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
                                <span class="high-score">
                                    🏆 <?= htmlspecialchars($post["score"]) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($post["time_ms"] !== null): ?>
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

                            <!-- カテゴリ -->
                            <?php if (!empty($post["category_name"])): ?>
                                <span class="post-category">
                                    <?= htmlspecialchars($post["category_name"]) ?>
                                </span>
                            <?php endif; ?>


                            <!-- タグ -->
                            <?php
                            $tags = $postTags[$post["id"]] ?? [];
                            ?>

                            <?php if (!empty($tags)): ?>

                                <div class="post-tags">

                                    <?php foreach ($tags as $tag): ?>
                                        <a
                                            class="post-tag"
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

                                    </div>

                                    <!-- ゲームカバー -->
                                    <?php if (!empty($post["game_cover"])): ?>

                                        <img
                                            src="<?= htmlspecialchars($post["game_cover"]) ?>"
                                            alt="<?= htmlspecialchars($post["game_name"]) ?>"
                                            class="post-footer-game-cover"
                                        >

                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </main>

    </div>

    <script src="./assets/js/like.js"></script>
    <script src="./assets/js/share.js"></script>
    <script src="assets/js/reply.js"></script>
    <script src="assets/js/gameSearch.js"></script>
    <script src="assets/js/tagFilter.js"></script>

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