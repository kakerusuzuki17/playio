<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

$error = $_SESSION["error"] ?? null;
unset($_SESSION["error"]);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <title>お気に入りゲーム追加</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">

    <!-- 左メニュー -->
    <?php require __DIR__ . "/includes/sidebar.php"; ?>

    <main class="main">

        <div class="post-box">
            <h2>お気に入りゲームを追加</h2>

            <a href="profile.php">← プロフィールに戻る</a>

            <?php if ($error): ?>
                <p class="error-message">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <form action="add_favorite_game.php" method="post">

                <input
                    type="text"
                    id="gameSearch"
                    placeholder="ゲーム名を検索"
                    autocomplete="off"
                >

                <div id="gameResults"></div>

                <div id="selectedGame" style="display:none;">
                    <img
                        id="selectedGameCover"
                        src=""
                        alt="ゲーム画像"
                    >

                    <div>
                        <h3 id="selectedGameName"></h3>
                        <p id="selectedGameGenres"></p>
                    </div>

                    <button
                        type="button"
                        id="clearGameButton"
                        class="clear-game-button"
                    >
                        × 選択解除
                    </button>
                </div>

                <input type="hidden" name="igdb_id" id="igdbId">
                <input type="hidden" name="game_name" id="gameName">
                <input type="hidden" name="game_cover" id="gameCover">
                <input type="hidden" name="game_genres" id="gameGenres">

                <button type="submit">
                    お気に入りに追加
                </button>
            </form>
        </div>
    </main>
</div>

<script src="assets/js/gameSearch.js"></script>

</body>
</html>