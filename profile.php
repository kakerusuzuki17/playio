<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";

$userId = $_SESSION["id"];

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

// お気に入りゲーム一覧
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
    ORDER BY favorite_games.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$favoriteGames = $stmt->fetchAll();

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

// 自分の投稿一覧
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
        WHERE posts.user_id = ?
        ORDER BY {$orderBy}";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $userId]);
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
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフィール</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="container">

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
                    <?= htmlspecialchars(mb_substr($user["user_name"], 0, 1)) ?>
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

        <main class="main">
            <section class="favorite-games-section">
                <h3>お気に入りゲーム <a href="edit_profile.php">＋ お気に入りゲームを追加</a></h3>

                <div class="favorite-games">
                    <?php if (empty($favoriteGames)): ?>
                        <p>まだお気に入りゲームが登録されていません。</p>
                    <?php endif; ?>

                    <?php foreach ($favoriteGames as $game): ?>
                        <div class="favorite-game-card">
                            <?php if (!empty($game["game_cover"])): ?>
                                <img
                                    src="<?= htmlspecialchars($game["game_cover"]) ?>"
                                    alt="<?= htmlspecialchars($game["game_name"]) ?>"
                                    width="90"
                                >
                            <?php endif; ?>

                            <strong><?= htmlspecialchars($game["game_name"]) ?></strong>

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
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="timeline-toolbar">
                <h3>自分の投稿</h3>
                <form action="profile.php" method="get" class="sort-form">
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
                                                動画を再生できません。
                                            </video>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- ハイスコア -->
                            <?php if ($post["score"] !== null): ?>

                                <p class="high-score">
                                    <?= htmlspecialchars($post["score"]) ?>
                                </p>

                            <?php endif; ?>

                            <!-- クリアタイム -->
                            <?php if ($post["time_ms"] !== null): ?>

                                <p class="clear-time">
                                    ⏱ <?= htmlspecialchars(
                                        formatTimeMs($post["time_ms"])
                                    ) ?>
                                </p>
                            <?php endif; ?>

                            <!-- 投稿本文 -->
                            <div class="post-content">
                                <?= nl2br(htmlspecialchars($post["content"])) ?>
                            </div>

                            <!-- 投稿フッター -->
                            <div class="post-footer">

                                <div class="post-footer-info">

                                    <!-- 左側 -->
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

                                            </div>

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
                </section>
            </div>
        </main>
    </div>

    <script src="./assets/js/like.js"></script>

    </body>
</html>