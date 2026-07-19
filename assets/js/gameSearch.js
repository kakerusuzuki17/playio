document.addEventListener("DOMContentLoaded", () => {

    // ゲーム検索欄
    const gameSearchInput =
        document.getElementById("gameSearch");

    // 検索結果表示欄
    const gameResults =
        document.getElementById("gameResults");

    // 選択中ゲーム表示欄
    const selectedGame =
        document.getElementById("selectedGame");

    const selectedGameCover =
        document.getElementById("selectedGameCover");

    const selectedGameName =
        document.getElementById("selectedGameName");

    const selectedGameGenres =
        document.getElementById("selectedGameGenres");

    // hidden input
    const igdbIdInput =
        document.getElementById("igdbId");

    const gameIdInput =
        document.getElementById("gameId");

    const gameNameInput =
        document.getElementById("gameName");

    const gameCoverInput =
        document.getElementById("gameCover");

    const gameGenresInput =
        document.getElementById("gameGenres");

    // 選択解除ボタン
    const clearGameButton =
        document.getElementById("clearGameButton");

    const categorySelect =
        document.getElementById("categorySelect");

    const divisionArea =
        document.getElementById("divisionSearchArea");

    const divisionSelect =
        document.getElementById("divisionSearchSelect");


    /**
     * ゲームを選択したときに実行する共通処理
     */
    function selectGame(game) {
    const gameId =
        game.gameId ?? game.game_id ?? "";

    const igdbId =
        game.id ?? game.igdb_id ?? "";

    const name =
        game.name ?? "";

    const cover =
        game.cover ?? game.image ?? "";

    const genres =
        Array.isArray(game.genres)
            ? game.genres.join(", ")
            : game.genres ?? "";

    // hidden input
    gameIdInput.value = gameId;
    igdbIdInput.value = igdbId;
    gameNameInput.value = name;
    gameCoverInput.value = cover;
    gameGenresInput.value = genres;

    // 検索欄
    gameSearchInput.value = name;

    // 選択中ゲーム表示
    selectedGameName.textContent = name;
    selectedGameGenres.textContent = genres;

    if (cover !== "") {
        selectedGameCover.src = cover;
        selectedGameCover.style.display = "block";
    } else {
        selectedGameCover.src = "";
        selectedGameCover.style.display = "none";
    }

    selectedGame.style.display = "flex";

    // 検索結果を閉じる
    gameResults.innerHTML = "";
    gameResults.style.display = "none";

    // 部門処理へゲーム選択を通知
    document.dispatchEvent(
        new CustomEvent("game-selected", {
            detail: {
                gameId: gameId,
                igdbId: igdbId
            }
        })
    );
}

    /**
     * ゲーム選択を解除する処理
     */
    function clearSelectedGame() {

        // hidden inputを空にする
        igdbIdInput.value = "";
        gameIdInput.value = "";
        gameNameInput.value = "";
        gameCoverInput.value = "";
        gameGenresInput.value = "";

        // 表示内容を空にする
        selectedGameName.textContent = "";
        selectedGameGenres.textContent = "";

        selectedGameCover.src = "";
        selectedGameCover.style.display = "block";

        // 選択欄を非表示
        selectedGame.style.display = "none";

        // 検索欄を空にする
        if (gameSearchInput) {
            gameSearchInput.value = "";
            gameSearchInput.focus();
        }

        // 検索結果も消す
        if (gameResults) {
            gameResults.innerHTML = "";
            gameResults.style.display = "none";
        }
        document.dispatchEvent(
            new CustomEvent("game-cleared")
        );
    }


    /**
     * お気に入りゲームをクリックしたとき
     */
    const favoriteGameCards =
    document.querySelectorAll(".favorite-game-card");

    favoriteGameCards.forEach(card => {

        card.addEventListener("click", () => {

            selectGame({
                gameId: card.dataset.gameId,
                id: card.dataset.id,
                name: card.dataset.name,
                cover: card.dataset.cover,
                genres: card.dataset.genres
            });

            gameSearchInput.value = card.dataset.name;

        });

    });


    /**
     * 選択解除ボタン
     */
    if (clearGameButton) {

        clearGameButton.addEventListener(
            "click",
            clearSelectedGame
        );
    }


    /**
     * ゲーム検索
     */
    if (gameSearchInput && gameResults) {

        let searchTimer = null;

        gameSearchInput.addEventListener("input", () => {

            const keyword =
                gameSearchInput.value.trim();

            // 前回の検索予約を解除
            clearTimeout(searchTimer);

            // 2文字未満なら検索しない
            if (keyword.length < 2) {
                gameResults.innerHTML = "";
                gameResults.style.display = "none";
                return;
            }

            // 入力するたび即通信しないように少し待つ
            searchTimer = setTimeout(async () => {

                try {

                    gameResults.style.display = "block";
                    gameResults.innerHTML =
                        '<div class="game-search-loading">検索中...</div>';

                    const response = await fetch(
                        "search_games.php?keyword=" +
                        encodeURIComponent(keyword)
                    );

                    if (!response.ok) {
                        throw new Error(
                            "ゲーム検索に失敗しました"
                        );
                    }

                    const games = await response.json();

                    gameResults.innerHTML = "";

                    if (!Array.isArray(games) ||
                        games.length === 0) {

                        gameResults.innerHTML =
                            '<div class="game-search-empty">' +
                            "ゲームが見つかりませんでした" +
                            "</div>";

                        return;
                    }

                    games.forEach(game => {

                        const resultItem =
                            document.createElement("button");

                        resultItem.type = "button";
                        resultItem.className =
                            "game-result-item";

                        // カバー画像
                        if (game.image) {
                            const cover =
                                document.createElement("img");

                            cover.src = game.image;
                            cover.alt = game.name ?? "";
                            cover.className = "game-result-cover";

                            resultItem.appendChild(cover);
                        } else {

                            const noCover =
                                document.createElement("div");

                            noCover.className =
                                "game-result-no-cover";

                            noCover.textContent =
                                "No Image";

                            resultItem.appendChild(noCover);
                        }

                        // ゲーム情報
                        const info =
                            document.createElement("div");

                        info.className =
                            "game-result-info";

                        const name =
                            document.createElement("strong");

                        name.textContent =
                            game.name ?? "名称不明";

                        info.appendChild(name);

                        const genres =
                            Array.isArray(game.genres)
                                ? game.genres.join(", ")
                                : game.genres ?? "";

                        if (genres !== "") {

                            const genreText =
                                document.createElement("small");

                            genreText.textContent =
                                genres;

                            info.appendChild(genreText);
                        }

                        resultItem.appendChild(info);

                        // 検索結果クリック
                        resultItem.addEventListener(
                            "click",
                            () => {

                                selectGame({

                                    gameId: game.game_id,

                                    id: game.id,

                                    name: game.name,

                                    cover: game.image,

                                    genres: genres

                                });

                            }
                        );

                        gameResults.appendChild(
                            resultItem
                        );

                    });

                } catch (error) {

                    console.error(error);

                    gameResults.innerHTML =
                        '<div class="game-search-error">' +
                        "ゲーム検索中にエラーが発生しました" +
                        "</div>";
                }

            }, 300);

        });
    }

    /*
|--------------------------------------------------------------------------
| 部門検索
|--------------------------------------------------------------------------
*/

async function loadDivisions() {
    const gameId = gameIdInput.value;
    const categoryId = categorySelect.value;

    const selectedDivisionId =
        divisionSelect.dataset.selectedDivisionId ?? "";

    /*
     * ゲームまたはカテゴリが未選択
     */
    if (!gameId || !categoryId) {
        divisionSelect.disabled = true;
        return;
    }

    divisionSelect.disabled = true;

    try {
        const params = new URLSearchParams({
            game_id: gameId,
            category_id: categoryId
        });

        const response = await fetch(
            `get_divisions.php?${params.toString()}`
        );

        if (!response.ok) {
            throw new Error(
                `部門取得エラー: ${response.status}`
            );
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(
                data.message ?? "部門取得に失敗しました"
            );
        }

        const divisions = data.divisions;
        divisionSelect.innerHTML =
            '<option value="">すべての部門</option>';

        if (!Array.isArray(divisions)) {
            throw new Error(
                "部門取得APIの形式が正しくありません"
            );
        }

        divisions.forEach(division => {
            const option =
                document.createElement("option");

            option.value = division.id;
            option.textContent = division.name;

            if (
                String(division.id) ===
                String(selectedDivisionId)
            ) {
                option.selected = true;
            }

            divisionSelect.appendChild(option);
        });
        if (selectedDivisionId) {
            divisionSelect.value = selectedDivisionId;
}

    } catch (error) {
        console.error(error);

        divisionSelect.innerHTML =
            '<option value="">部門を取得できませんでした</option>';

    } finally {
        divisionSelect.disabled = false;
    }
}

/**
 * 検索結果の外をクリックしたら閉じる
 */
document.addEventListener("click", event => {

    const clickedSearchInput =
        gameSearchInput &&
        gameSearchInput.contains(event.target);

    const clickedSearchResult =
        gameResults &&
        gameResults.contains(event.target);

    if (!clickedSearchInput &&
        !clickedSearchResult &&
        gameResults) {

        gameResults.style.display = "none";
    }

});

function updateDivisionArea() {
    const categoryId = categorySelect.value;

    const canUseDivision =
        categoryId === "1" ||
        categoryId === "2";

    if (!canUseDivision) {
        divisionArea.style.display = "none";

        divisionSelect.innerHTML =
            '<option value="">すべての部門</option>';

        divisionSelect.value = "";
        divisionSelect.disabled = true;

        return;
    }

    divisionArea.style.display = "";

    if (!gameIdInput.value) {
        divisionSelect.disabled = true;

        divisionSelect.innerHTML =
            '<option value="">先にゲームを選択してください</option>';

        return;
    }

    loadDivisions();
}

categorySelect.addEventListener(
    "change",
    updateDivisionArea
);

document.addEventListener(
    "game-selected",
    updateDivisionArea
);

document.addEventListener(
    "game-cleared",
    updateDivisionArea
);

/*
* 検索後の再表示にも対応
*/
updateDivisionArea();
});

divisionSelect.addEventListener("change", () => {
    document.getElementById("divisionId").value =
        divisionSelect.value;
});