document.querySelectorAll(".like-btn").forEach(button => {
    button.addEventListener("click", async () => {
        // いいねボタンがクリックされたときの処理

        const postId = button.dataset.postId;
        const formData = new FormData();
        formData.append("post_id", postId);

        const response = await fetch("like.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            alert("いいねに失敗しました");
            return;
        }

        button.querySelector(".like-count").textContent = result.like_count;

        const count = button.querySelector(".like-count");
        const heart = button.querySelector(".heart");

        // ハートのアニメーションのためにクラスを一度削除して再度追加
        heart.classList.remove("pop");
        void heart.offsetWidth;
        heart.classList.add("pop");

        count.textContent = result.like_count;
        if (result.liked) {
            button.classList.add("liked");
            button.innerHTML = `
                <span class="heart">❤️</span>
                <span class="like-count">${result.like_count}</span>
            `;
            
            const heart = button.querySelector(".heart");
            heart.classList.remove("pop");
            void heart.offsetWidth;
            heart.classList.add("pop");

        } else {
            button.classList.remove("liked");
            button.innerHTML = `
                <span class="heart">🤍</span>
                <span class="like-count">${result.like_count}</span>
            `;
        }
    });
});