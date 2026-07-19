<?php
$gameId = trim($_GET["game_id"] ?? "");
$igdbId = trim($_GET["igdb_id"] ?? "");

$gameName = trim($_GET["game_name"] ?? "");
$gameCover = trim($_GET["game_cover"] ?? "");
$gameGenres = trim($_GET["game_genres"] ?? "");

$categoryId = trim($_GET["category_id"] ?? "");
$divisionId = trim($_GET["division_id"] ?? "");

$keyword = trim($_GET["keyword"] ?? "");
$tagsText = trim($_GET["tags"] ?? "");

$spoiler = isset($_GET["spoiler"]) ? 1 : 0;
$keyword = trim($_GET["keyword"] ?? "");
$tagsText = trim($_GET["tags"] ?? "");
$spoiler = isset($_GET["spoiler"])
    && $_GET["spoiler"] === "1";

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
?>

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

                <!-- ゲーム -->
                <div class="search-field search-field-game">

                    <label for="gameSearch">
                        ゲーム
                    </label>

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
                        !empty($gameId) ||
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
                            alt="<?= htmlspecialchars(
                                $gameName ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            style="<?= !empty($gameCover)
                                ? "display:block;"
                                : "display:none;"
                            ?>"
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
                        name="game_id"
                        id="gameId"
                        value="<?= htmlspecialchars(
                            $gameId ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

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
                <div class="search-field search-field-category">

                    <label for="categorySelect">
                        カテゴリ
                    </label>

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
                                <?= (string)($categoryId ?? "") ===
                                    (string)$category["id"]
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                <?= htmlspecialchars(
                                    $category["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>

                </div>

                <!-- 部門 -->
                <div
                    class="search-field"
                    id="divisionSearchArea"
                    style="display:none;"
                >
                    <label for="divisionSearchSelect">
                        部門
                    </label>

                    <select
                        id="divisionSearchSelect"
                        name="division_id"
                        data-selected-division-id="<?= htmlspecialchars(
                            $divisionId ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >
                        <option value="">
                            すべての部門
                        </option>
                    </select>
                </div>

                <input
                    type="hidden"
                    name="division_id"
                    id="divisionId"
                    value="<?= htmlspecialchars($divisionId ?? "") ?>"
                >

                <!-- 投稿文 -->
                <div class="search-field search-field-post">

                    <label for="keyword">
                        投稿文
                    </label>

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
                <div class="search-field search-field-tag">

                    <label for="tagSearch">
                        タグ
                    </label>

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

                    <span>
                        ネタバレを含める
                    </span>

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