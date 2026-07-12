const categorySelect = document.getElementById("categorySelect");
const highScoreInput = document.getElementById("highScoreInput");
const clearTimeInput = document.getElementById("clearTimeInput");
const mediaInput = document.getElementById("mediaInput");
const mediaPreview = document.getElementById("mediaPreview");

let previewUrls = [];

mediaInput.addEventListener("change", () => {
    previewUrls.forEach(url => {
        URL.revokeObjectURL(url);
    });

    previewUrls = [];
    mediaPreview.innerHTML = "";

    const files = Array.from(mediaInput.files);

    if (files.length > 4) {
        alert("画像・動画は4件までです。");

        mediaInput.value = "";
        return;
    }

    files.forEach(file => {
        const previewUrl = URL.createObjectURL(file);

        previewUrls.push(previewUrl);

        const previewItem = document.createElement("div");
        previewItem.className = "media-preview-item";

        if (file.type.startsWith("image/")) {
            const image = document.createElement("img");

            image.src = previewUrl;
            image.alt = file.name;

            previewItem.appendChild(image);

        } else if (file.type.startsWith("video/")) {
            const video = document.createElement("video");

            video.src = previewUrl;
            video.controls = true;
            video.preload = "metadata";

            previewItem.appendChild(video);
        }

        mediaPreview.appendChild(previewItem);
    });
});

if (categorySelect) {
    categorySelect.addEventListener("change", updateCategoryInput);
}

function updateCategoryInput() {
    // 先に両方消して、重複追加を防ぐ
    highScoreInput.innerHTML = "";
    clearTimeInput.innerHTML = "";

    // ハイスコア
    if (categorySelect.value === "1") {
        highScoreInput.innerHTML = `
            <div class="form-field">
                <label for="highScore">ハイスコア</label>

                <input
                    type="number"
                    name="high_score"
                    id="highScore"
                    min="0"
                    placeholder="例：1000"
                    required
                >
            </div>
        `;
    }

    // タイムアタック
    if (categorySelect.value === "2") {
        clearTimeInput.innerHTML = `
            <div class="form-field">
                <label>クリアタイム</label>
                <div class="clear-time-fields">
                    <label>
                        <input
                            type="number"
                            name="clear_time_hour"
                            id="clearTimeHour"
                            min="0"
                            max="99"
                            value="0"
                            required
                        >
                        <span>時間</span>
                    </label>

                    <label>
                        <input
                            type="number"
                            name="clear_time_minute"
                            id="clearTimeMinute"
                            min="0"
                            max="59"
                            value="0"
                            required
                        >
                        <span>分</span>
                    </label>

                    <label>
                        <input
                            type="number"
                            name="clear_time_second"
                            id="clearTimeSecond"
                            min="0"
                            max="59"
                            value="0"
                            required
                        >
                        <span>秒</span>
                    </label>

                    <label>
                        <input
                            type="number"
                            name="clear_time_cenci_seconds"
                            id="clearCentiseconds"
                            min="0"
                            max="99"
                            value="0"
                            required
                        >
                        <span>100分の1秒</span>
                    </label>
                </div>
            </div>
        `;
    }
}