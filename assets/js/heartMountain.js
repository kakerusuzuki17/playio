document.addEventListener("DOMContentLoaded", () => {

    const mountain =
        document.getElementById("likeMountain");

    if (!mountain) {
        return;
    }

    const likeCount =
        Number(mountain.dataset.likeCount ?? 0);

    const heartSize = 30;
    const horizontalGap = 23;
    const verticalGap = 20;

    /*
     * 下段ほど多く、上段ほど少なくなるように配置
     */
    let createdCount = 0;
    let row = 0;

    while (createdCount < likeCount) {

        // 1段目は1個、2段目は3個、3段目は5個……
        const rowCapacity = row * 2 + 1;

        const remainingCount =
            likeCount - createdCount;

        const countInRow =
            Math.min(rowCapacity, remainingCount);

        for (
            let column = 0;
            column < countInRow;
            column++
        ) {
            const heart =
                document.createElement("img");

            const heartNo = Math.floor(Math.random() * 15) + 1;

            heart.src =
                `assets/images/heart/heart${heartNo}.png`;

            heart.alt = "";
            heart.className =
                "like-mountain-heart";

            /*
             * 山の中央から左右に並べる
             */
            const centerOffset =
                (countInRow - 1) / 2;

            const x =
                (column - centerOffset) *
                horizontalGap;

            const randomX =
                Math.random() * 8 - 4;

            const randomY =
                Math.random() * 5;

            const rotation =
                Math.random() * 30 - 15;

            heart.style.left =
                `calc(50% + ${x + randomX}px)`;

            heart.style.bottom =
                `${20 + row * verticalGap + randomY}px`;

            heart.style.width =
                `${heartSize}px`;

            heart.style.transform =
                `translateX(-50%) rotate(${rotation}deg)`;

            heart.style.zIndex =
                String(1000 - row);

            mountain.appendChild(heart);

            createdCount++;
        }

        row++;
    }

    /*
     * いいね数に応じて表示領域の高さを伸ばす
     */
    const requiredHeight =
        Math.max(
            220,
            row * verticalGap + 80
        );

    mountain.style.height =
        `${requiredHeight}px`;

});