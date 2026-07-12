const input = document.getElementById("gameSearch");
const results = document.getElementById("gameResults");

const igdbId = document.getElementById("igdbId");
const gameName = document.getElementById("gameName");
const gameCover = document.getElementById("gameCover");
const gameGenres = document.getElementById("gameGenres");

const selectedGame = document.getElementById("selectedGame");
const selectedGameCover = document.getElementById("selectedGameCover");
const selectedGameName = document.getElementById("selectedGameName");
const selectedGameGenres = document.getElementById("selectedGameGenres");

const clearGameButton = document.getElementById("clearGameButton");

let searchTimer;
let controller;

if (input) {
    input.addEventListener("input", () => {
        clearTimeout(searchTimer);

        // 前後の空白を削除し、連続する空白を1つにする
        const keyword = input.value
            .trim()
            .replace(/\s+/g, " ");

        if (keyword.length < 2) {
            results.innerHTML = "";
            return;
        }

        searchTimer = setTimeout(async () => {
            // 前回の通信が残っていたら中断
            if (controller) {
                controller.abort();
            }

            controller = new AbortController();

            try {
                const response = await fetch(
                    "search_games.php?q=" + encodeURIComponent(keyword),
                    {
                        signal: controller.signal
                    }
                );

                if (!response.ok) {
                    throw new Error("ゲーム検索に失敗しました");
                }

                const games = await response.json();

                console.log(games);

                results.innerHTML = "";

                if (!Array.isArray(games) || games.length === 0) {
                    results.innerHTML = "<p>ゲームが見つかりませんでした。</p>";
                    return;
                }

                games.forEach(game => {
                    const option = document.createElement("div");
                    option.className = "game-option";

                    const genreText = Array.isArray(game.genres)
                        ? game.genres.join(", ")
                        : "";

                    option.innerHTML = `
                        ${game.image
                            ? `<img src="${game.image}" width="50" alt="">`
                            : ""
                        }

                        <div>
                            <strong>${game.name}</strong><br>
                            <small>${game.released ?? ""}</small><br>
                            <small>${genreText}</small>
                        </div>
                    `;

                    option.addEventListener("click", () => { // ゲーム検索時
                        input.value = game.name;

                        igdbId.value = game.id;
                        gameName.value = game.name;
                        gameCover.value = game.image ?? "";
                        gameGenres.value = genreText;

                        if (selectedGame) {
                            selectedGame.style.display = "flex";
                            selectedGameName.textContent = game.name;
                            selectedGameGenres.textContent = genreText;

                            if (game.image) {
                                selectedGameCover.src = game.image;
                                selectedGameCover.style.display = "block";
                            } else {
                                selectedGameCover.removeAttribute("src");
                                selectedGameCover.style.display = "none";
                            }
                        }

                        results.innerHTML = "";
                    });

                    results.appendChild(option);
                });
            } catch (error) {
                if (error.name !== "AbortError") {
                    console.error(error);
                    results.innerHTML = "<p>検索中にエラーが発生しました。</p>";
                }
            }
        }, 300);
    });
}

if (clearGameButton) {
    clearGameButton.addEventListener("click", (event) => {
        event.preventDefault();

        if (input) input.value = "";
        if (igdbId) igdbId.value = "";
        if (gameName) gameName.value = "";
        if (gameCover) gameCover.value = "";
        if (gameGenres) gameGenres.value = "";

        if (selectedGame) {
            selectedGame.style.display = "none";
        }

        if (selectedGameCover) {
            selectedGameCover.src = "";
            selectedGameCover.style.display = "none";
        }

        if (selectedGameName) {
            selectedGameName.textContent = "";
        }

        if (selectedGameGenres) {
            selectedGameGenres.textContent = "";
        }

        if (results) {
            results.innerHTML = "";
        }
    });
}

option.addEventListener("click", () => {
    input.value = game.name;

    igdbId.value = game.id;
    gameName.value = game.name;
    gameCover.value = game.image ?? "";
    gameGenres.value = genreText;

    if (selectedGame) {
        selectedGame.style.display = "flex";
    }

    if (selectedGameName) {
        selectedGameName.textContent = game.name;
    }

    if (selectedGameGenres) {
        selectedGameGenres.textContent = genreText;
    }

    if (selectedGameCover) {
        if (game.image) {
            selectedGameCover.src = game.image;
            selectedGameCover.style.display = "block";
        } else {
            selectedGameCover.src = "";
            selectedGameCover.style.display = "none";
        }
    }

    results.innerHTML = "";
});
