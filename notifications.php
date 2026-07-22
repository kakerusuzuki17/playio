<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";

$currentUserId = (int)$_SESSION["id"];

/*
|--------------------------------------------------------------------------
| 通知確認開始時刻
|--------------------------------------------------------------------------
|
| 画面を開いている途中に付いたいいねを取りこぼさないように、
| 最初に現在時刻を確定します。
|
*/

$stmt = $pdo->query("SELECT NOW()");
$openedAt = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| 最後に通知画面を開いた日時を取得
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT notice_final_datetime
    FROM users
    WHERE id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$currentUserId]);

$lastViewedAt = $stmt->fetchColumn();

/*
 * 一度も通知画面を開いていない場合
 */
if ($lastViewedAt === null) {
    $lastViewedAt = "1970-01-01 00:00:00";
}

/*
|--------------------------------------------------------------------------
| 前回確認後に付いたいいねを取得
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        likes.id AS like_id,
        likes.created_at AS liked_at,

        posts.id AS post_id,
        posts.content AS post_content,

        liked_user.id AS liked_user_id,
        liked_user.user_name AS liked_user_name,
        liked_user.account_id AS liked_user_account_id

    FROM likes

    JOIN posts
        ON likes.post_id = posts.id

    JOIN users AS liked_user
        ON likes.user_id = liked_user.id

    WHERE posts.user_id = ?
      AND likes.user_id <> ?
      AND likes.created_at > ?
      AND likes.created_at <= ?

    ORDER BY likes.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $currentUserId,
    $currentUserId,
    $lastViewedAt,
    $openedAt
]);

$newLikes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 通知画面を開いた日時を更新
|--------------------------------------------------------------------------
*/

$sql = "
    UPDATE users
    SET notice_final_datetime = ?
    WHERE id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $openedAt,
    $currentUserId
]);

$newLikeCount = count($newLikes);

/*
|--------------------------------------------------------------------------
| 自分の投稿に付いた総いいね数
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT COUNT(*)
    FROM likes
    JOIN posts
        ON likes.post_id = posts.id
    WHERE posts.user_id = ?
      AND likes.user_id <> ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $currentUserId,
    $currentUserId
]);

$totalLikeCount = (int)$stmt->fetchColumn();

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
$stmt->execute([$currentUserId]);
$favoriteGames = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>通知</title>

    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
</head>

<body>

<div class="container">

    <?php require __DIR__ . "/includes/sidebar.php"; ?>

    <main class="main">

        <section class="notification-page">

            <div class="notification-header">
                <h1>通知</h1>

                <?php if ($newLikeCount > 0): ?>
                    <span class="notification-count">
                        新着 <?= $newLikeCount ?>件
                    </span>
                <?php endif; ?>

            </div>

            <?php if (empty($newLikes)): ?>

                <div class="notification-empty">
                    新しい通知はありません。
                </div>

            <?php else: ?>

                <div class="notification-list">

                    <?php foreach ($newLikes as $notification): ?>

                        <article class="notification-item">

                            <div class="notification-icon">
                                ❤️
                            </div>

                            <div class="notification-content">

                                <span>
                                    あなたの投稿にいいねがつきました
                                </span>

                                    <?= htmlspecialchars(
                                        mb_strimwidth(
                                            $notification["post_content"],
                                            0,
                                            80,
                                            "..."
                                        ),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                <time class="notification-time">
                                    <?= htmlspecialchars(
                                        $notification["liked_at"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </time>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <section class="like-mountain-section">

                <div class="like-mountain-title">
                    <span>あなたが集めたいいね</span>

                    <strong>
                        <?= number_format($totalLikeCount) ?>
                    </strong>
                </div>

                <div
                    id="likeMountain"
                    class="like-mountain"
                    data-like-count="<?= $totalLikeCount ?>"
                >
                    <div class="like-mountain-ground"></div>
                </div>

            </section>

        </section>

    </main>

</div>

<script src="assets/js/heartMountain.js"></script>

</body>
</html>