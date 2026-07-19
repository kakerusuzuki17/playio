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
        ORDER BY FIELD(
            id,
            6,4,5,3,1,2,7)";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll();

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
    ORDER BY game_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$currentId]);
$favoriteGames = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿する</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/new_post.css">
</head>
<body>

<div class="container">
<?php require __DIR__ . "/includes/sidebar.php"; ?>
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
                <div class="form-group game-select-area">

                    <label for="gameSearch">ゲーム</label>

                    <input
                        type="text"
                        id="gameSearch"
                        placeholder="ゲーム名を検索"
                        autocomplete="off"
                    >

                    <div id="gameResults"></div>

                    <!-- お気に入りゲーム -->
                    <?php if (!empty($favoriteGames)): ?>

                        <div class="favorite-game-picker">

                            <h3 class="favorite-game-picker-title">
                                ★ お気に入りから選ぶ
                            </h3>

                            <div class="favorite-game-picker-list">

                                <?php foreach ($favoriteGames as $game): ?>

                                    <button
                                        type="button"
                                        class="favorite-game-card"
                                        data-game-id="<?= (int)$game["game_id"] ?>"
                                        data-id="<?= (int)$game["igdb_id"] ?>"
                                        data-name="<?= htmlspecialchars(
                                            $game["game_name"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        data-cover="<?= htmlspecialchars(
                                            $game["game_cover"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        data-genres="<?= htmlspecialchars(
                                            $game["game_genres"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                        <?php if (!empty($game["game_cover"])): ?>

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
                                                class="favorite-game-picker-cover"
                                            >

                                        <?php else: ?>

                                            <div class="favorite-game-picker-no-cover">
                                                No Image
                                            </div>

                                        <?php endif; ?>

                                        <span class="favorite-game-picker-name">
                                            <?= htmlspecialchars(
                                                $game["game_name"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>

                                    </button>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- 選択中のゲーム -->
                    <div
                        id="selectedGame"
                        class="selected-game"
                        style="display:none;"
                    >

                        <img
                            id="selectedGameCover"
                            src=""
                            alt="選択したゲーム画像"
                        >

                        <div class="selected-game-info">

                            <strong id="selectedGameName"></strong>

                            <small id="selectedGameGenres"></small>

                        </div>

                        <button
                            type="button"
                            id="clearGameButton"
                            class="clear-game-button"
                        >
                            ×
                        </button>

                    </div>


                    <!-- 投稿時に送信する値 -->
                    <input
                        type="hidden"
                        name="igdb_id"
                        id="igdbId"
                    >

                    <input
                        type="hidden"
                        name="game_id"
                        id="gameId"
                    >

                    <input
                        type="hidden"
                        name="game_name"
                        id="gameName"
                    >

                    <input
                        type="hidden"
                        name="game_cover"
                        id="gameCover"
                    >

                    <input
                        type="hidden"
                        name="game_genres"
                        id="gameGenres"
                    >

                </div>

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
                                    <option value="<?= (int)$category["id"] ?>"
                                    data-category-name="<?= htmlspecialchars($category["name"], ENT_QUOTES, "UTF-8") ?>">
                                        <?= htmlspecialchars($category["name"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div
                            id="divisionField"
                            class="form-field form-grid-wide"
                            style="display:none;"
                        >
                            <label for="divisionSelect">部門</label>

                            <select
                                name="division_id"
                                id="divisionSelect"
                            >
                                <option value="">部門を選択してください</option>
                            </select>

                            <button
                                type="button"
                                id="showNewDivisionButton"
                                class="show-new-division-button"
                            >
                                ＋ 新しい部門を追加
                            </button>

                            <div
                                id="newDivisionArea"
                                class="new-division-area"
                                style="display:none;"
                            >
                                <label for="newDivisionName">
                                    新しい部門名
                                </label>

                                <input
                                    type="text"
                                    name="new_division_name"
                                    id="newDivisionName"
                                    maxlength="100"
                                    placeholder="例：Any%、100%、HARD"
                                >

                                <button
                                    type="button"
                                    id="cancelNewDivisionButton"
                                    class="cancel-new-division-button"
                                >
                                    既存の部門から選ぶ
                                </button>
                            </div>

                            <p id="divisionMessage" class="division-message"></p>
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
<script src="assets/js/divisionSelect.js"></script>
<script src="assets/js/tagSearch.js"></script>
<script src="assets/js/newPostAddForm.js"></script>

<script>
document.querySelector(".new-post-form").addEventListener("submit", function (e) {

    const category = document.getElementById("categorySelect").value;
    const division = document.getElementById("divisionSelect").value;
    const newDivision = document
        .getElementById("newDivisionName")
        .value
        .trim();

    // ハイスコア・タイムアタックは部門必須
    if (
        (category === "1" || category === "2") &&
        division === "" &&
        newDivision === ""
    ) {
        e.preventDefault();
        alert("部門を選択するか、新しい部門を追加してください。");
        return;
    }

});
</script>

</body>
</html>