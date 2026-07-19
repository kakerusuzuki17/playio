document.addEventListener("DOMContentLoaded", () => {
    const captureArea =
        document.getElementById("favoriteShareCapture");

    const exportButton =
        document.getElementById("exportFavoriteGamesButton");

    const shareButton =
        document.getElementById("shareFavoriteGamesButton");

    let generatedBlob = null;

    /**
     * 画像がすべて読み込まれるまで待つ
     */
    async function waitForImages(element) {
        const images = Array.from(
            element.querySelectorAll("img")
        );

        await Promise.all(
            images.map(image => {
                if (image.complete) {
                    return Promise.resolve();
                }

                return new Promise(resolve => {
                    image.addEventListener(
                        "load",
                        resolve,
                        { once: true }
                    );

                    image.addEventListener(
                        "error",
                        resolve,
                        { once: true }
                    );
                });
            })
        );
    }

    /**
     * 共有画像をCanvasとして生成
     */
    async function createFavoriteCanvas() {
        if (!captureArea) {
            throw new Error(
                "共有画像エリアが見つかりません"
            );
        }

        await waitForImages(captureArea);

        return await html2canvas(captureArea, {
            width: 1080,
            height: 1350,

            scale: 1,

            backgroundColor: null,

            useCORS: true,
            allowTaint: false,

            logging: false
        });
    }

    /**
     * CanvasをBlobへ変換
     */
    function canvasToBlob(canvas) {
        return new Promise((resolve, reject) => {
            canvas.toBlob(
                blob => {
                    if (!blob) {
                        reject(
                            new Error(
                                "画像の生成に失敗しました"
                            )
                        );
                        return;
                    }

                    resolve(blob);
                },
                "image/png",
                1
            );
        });
    }

    /**
     * PNGをダウンロード
     */
    function downloadBlob(blob) {
        const url = URL.createObjectURL(blob);

        const link = document.createElement("a");

        link.href = url;
        link.download = "playio-favorite-games.png";

        document.body.appendChild(link);
        link.click();
        link.remove();

        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 1000);
    }

    /**
     * 画像出力
     */
    exportButton?.addEventListener("click", async () => {
        exportButton.disabled = true;
        exportButton.textContent = "画像を作成中...";

        try {
            const canvas =
                await createFavoriteCanvas();

            generatedBlob =
                await canvasToBlob(canvas);

            downloadBlob(generatedBlob);

            if (shareButton) {
                shareButton.disabled = false;
            }

        } catch (error) {
            console.error(error);

            alert(
                "画像の作成に失敗しました。" +
                "ゲームカバー画像の読み込み状態を確認してください。"
            );

        } finally {
            exportButton.disabled = false;
            exportButton.textContent =
                "お気に入りを画像にする";
        }
    });

    /**
     * X共有
     */
    shareButton?.addEventListener("click", async () => {
    if (!generatedBlob) {
        alert("先に画像を作成してください");
        return;
    }

    // 画像は端末に保存
    downloadBlob(generatedBlob);

    const profileUrl =
        shareButton.dataset.profileUrl ?? "";

    const tweetText =
        "私のお気に入りゲームです！\n\n" +
        profileUrl +
        "\n\n#Playio";

    const tweetUrl =
        "https://twitter.com/intent/tweet?text=" +
        encodeURIComponent(tweetText);

    window.open(
        tweetUrl,
        "_blank",
        "noopener,noreferrer"
    );
});
});