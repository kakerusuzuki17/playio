<?php
$currentPage =
    basename($_SERVER["PHP_SELF"]);
?>

<aside class="sidebar">

    <h2>
        <a
            href="home.php"
            class="post-link"
        >
            Playio β版
        </a>
    </h2>

    <ul>
        <li>
            <a
                href="home.php"
                class="<?= $currentPage === "home.php"
                    ? "active"
                    : "" ?>"
            >
                🏠 ホーム
            </a>
        </li>

        <li>
            <a
                href="profile.php?account_id=<?= urlencode($_SESSION["account_id"]) ?>"
                class="<?= $currentPage === "profile.php"
                    ? "active"
                    : "" ?>"
            >
                👤 プロフィール
            </a>
        </li>

        <li>
            <a
                href="upcoming_games.php"
                class="<?= $currentPage === "upcoming_games.php"
                    ? "active"
                    : "" ?>"
            >
                📅 発売予定
            </a>
        </li>

        <li>
            <a
                href="new_post.php"
                class="post-link <?= $currentPage === "new_post.php"
                    ? "active"
                    : "" ?>"
            >
                ✏️ 投稿する
            </a>
        </li>
    </ul>



    <!-- お気に入りゲーム -->
    <section class="favorite-games-section">

        <h3 class="favorite-games-title">
            お気に入りゲーム
        </h3>

        <ul class="favorite-games-list">

            <?php if (!empty($favoriteGames)): ?>

                <?php foreach (
                    $favoriteGames
                    as $favoriteGame
                ): ?>

                    <li>
                        <a
                            href="search_posts.php?igdb_id=<?= urlencode(
                                $favoriteGame["igdb_id"]
                            ) ?>&game_name=<?= urlencode(
                                $favoriteGame["game_name"]
                            ) ?>&game_cover=<?= urlencode(
                                $favoriteGame["game_cover"] ?? ""
                            ) ?>"
                        >
                        <img class="favorite-game-list-img" src="<?= $favoriteGame["game_cover"] ?>">
                            <?= htmlspecialchars(
                                $favoriteGame["game_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </a>
                    </li>

                <?php endforeach; ?>

            <?php endif; ?>

        </ul>

    </section>

    <div class="account-info">
        <a href="profile.php?account_id=<?= urlencode($_SESSION["account_id"]) ?>">
            <div class="account-icon">
                <?= htmlspecialchars(
                    mb_substr(
                        $_SESSION["user_name"] ?? "?",
                        0,
                        1
                    ),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </div>
        </a>

        <div>
            <a href="profile.php?account_id=<?= urlencode($_SESSION["account_id"]) ?>">
                <strong>
                    <?= htmlspecialchars(
                        $_SESSION["user_name"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </strong>

                <p>
                    @<?= htmlspecialchars(
                        $_SESSION["account_id"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>
            </a>

            <a href="auth/logout.php">
                ログアウト
            </a>
        </div>

    </div>

</aside>