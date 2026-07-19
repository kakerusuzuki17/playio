document.querySelectorAll(".favorite-btn").forEach((button) => {
    button.addEventListener("click", async () => {
        const gameId = button.dataset.gameId;

        if (!gameId) {
            alert("ゲームIDを取得できませんでした");
            return;
        }

        const formData = new FormData();
        formData.append("game_id", gameId);

        button.disabled = true;

        try {
            const response = await fetch("favorite.php", {
                method: "POST",
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTPエラー: ${response.status}`);
            }

            const result = await response.json();

            if (!result.success) {
                alert(result.message ?? "お気に入り登録に失敗しました");
                return;
            }

            const star = button.querySelector(".star");
            const count = button.querySelector(".favorite-count");

            count.textContent = result.favorite_count;

            if (result.favorite) {
                button.classList.add("favorite");
                star.textContent = "⭐";
            } else {
                button.classList.remove("favorite");
                star.textContent = "☆";
            }

            // 星のアニメーションを再実行
            star.classList.remove("pop");
            void star.offsetWidth;
            star.classList.add("pop");

        } catch (error) {
            console.error(error);
            alert("通信に失敗しました");
        } finally {
            button.disabled = false;
        }
    });
});