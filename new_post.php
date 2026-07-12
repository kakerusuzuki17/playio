<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: auth/login.php");
    exit;
}

require "config/db.php";

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
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿する</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">

    <main class="main new-post-page">

        <div class="new-post-container">

            <div class="new-post-header">
                <div>
                    <h2>投稿する</h2>
                </div>

                <a href="home.php" class="back-link">
                    ← 戻る
                </a>
            </div>

            <?php
            $error = $_SESSION["error"] ?? null;
            unset($_SESSION["error"]);
            ?>

            <?php if ($error): ?>
                <div class="form-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form
                action="create_post.php"
                method="post"
                enctype="multipart/form-data"
                class="new-post-form"
            >

                <!-- ゲーム -->
                <section class="form-section">
                    <div class="form-section-header">
                        <span class="form-step">1</span>

                        <div>
                            <h3>ゲームを選択</h3>
                            <p>投稿するゲームを検索してください</p>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="gameSearch">ゲーム名</label>

                        <input
                            type="text"
                            id="gameSearch"
                            placeholder="例：Street Fighter 6"
                            autocomplete="off"
                        >

                        <div id="gameResults"></div>
                    </div>

                    <div id="selectedGame" style="display:none;">
                        <img
                            id="selectedGameCover"
                            src=""
                            alt="ゲーム画像"
                        >

                        <div class="selected-game-info">
                            <h3 id="selectedGameName"></h3>
                            <p id="selectedGameGenres"></p>
                            <p id="selectedGameReleased"></p>
                        </div>

                        <button
                            type="button"
                            id="clearGameButton"
                            class="clear-game-button"
                        >
                            選択解除
                        </button>
                    </div>

                    <input type="hidden" name="igdb_id" id="igdbId">
                    <input type="hidden" name="game_name" id="gameName">
                    <input type="hidden" name="game_cover" id="gameCover">
                    <input type="hidden" name="game_genres" id="gameGenres">
                    <input type="hidden" name="game_released" id="gameReleased">
                </section>

                <!-- 投稿内容 -->
                <section class="form-section">
                    <div class="form-section-header">
                        <span class="form-step">2</span>

                        <div>
                            <h3>投稿内容</h3>
                            <p>カテゴリや本文を入力してください</p>
                        </div>
                    </div>

                    <div class="form-grid">

                        <div class="form-field">
                            <label for="categorySelect">カテゴリ</label>

                            <select
                                name="category_select"
                                id="categorySelect"
                                required
                            >
                                <option value="">選択してください</option>

                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category["id"] ?>">
                                        <?= htmlspecialchars($category["name"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div
                            id="highScoreInput"
                            class="dynamic-input"
                        ></div>

                        <div
                            id="clearTimeInput"
                            class="dynamic-input form-grid-wide"
                        ></div>

                        <div class="form-field form-grid-wide">
                            <label for="content">本文</label>

                            <textarea
                                name="content"
                                id="content"
                                rows="5"
                                maxlength="300"
                                placeholder="ゲームの感想や記録を書いてください"
                                required
                            ></textarea>

                            <div class="character-guide">
                                最大300文字
                            </div>
                        </div>
                    </div>
                </section>

                <!-- メディア -->
                <section class="form-section">
                    <div class="form-section-header">
                        <span class="form-step">3</span>

                        <div>
                            <h3>画像・動画</h3>
                            <p>最大4件まで追加できます</p>
                        </div>
                    </div>

                    <label
                        for="mediaInput"
                        class="media-upload-box"
                    >
                        <span class="media-upload-icon">＋</span>
                        <strong>画像・動画を選択</strong>
                        <small>クリックしてファイルを追加</small>
                    </label>

                    <input
                        type="file"
                        name="media[]"
                        id="mediaInput"
                        accept="image/*,video/*"
                        multiple
                        class="media-input-hidden"
                    >

                    <div id="mediaPreview" class="media-preview"></div>
                </section>

                <!-- タグ・設定 -->
                <section class="form-section">
                    <div class="form-section-header">
                        <span class="form-step">4</span>

                        <div>
                            <h3>タグ・公開設定</h3>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="tagSearch">タグ</label>

                        <input
                            type="text"
                            id="tagSearch"
                            placeholder="タグを検索または新規作成"
                            autocomplete="off"
                        >

                        <div id="tagResults"></div>
                        <div id="selectedTags"></div>

                        <input
                            type="hidden"
                            name="tags"
                            id="tagsInput"
                        >
                    </div>

                    <label class="spoiler-switch">
                        <input
                            type="checkbox"
                            name="spoiler"
                            id="spoilerCheckbox"
                            value="1"
                        >

                        <span class="spoiler-switch-ui"></span>

                        <span>
                            <strong>ネタバレを含む</strong>
                        </span>
                    </label>
                </section>

                <div class="new-post-actions">
                    <a href="home.php" class="cancel-button">
                        キャンセル
                    </a>

                    <button type="submit" class="submit-post-button">
                        投稿する
                    </button>
                </div>

            </form>

        </div>

    </main>
</div>

<script src="assets/js/gameSearch.js"></script>
<script src="assets/js/tagSearch.js"></script>
<script src="assets/js/newPostAddForm.js"></script>

</body>
</html>