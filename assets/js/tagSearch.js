document.addEventListener("DOMContentLoaded", () => {
    /*
    |--------------------------------------------------------------------------
    | DOM取得
    |--------------------------------------------------------------------------
    */

    const tagSearchInput =
        document.getElementById("tagSearch");

    const tagResults =
        document.getElementById("tagResults");

    const selectedTagsArea =
        document.getElementById("selectedTags");

    const tagsInput =
        document.getElementById("tagsInput");

    /*
     * タグ機能が存在しないページでは処理しない
     */
    if (
        !tagSearchInput ||
        !tagResults ||
        !selectedTagsArea ||
        !tagsInput
    ) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | 選択中タグ
    |--------------------------------------------------------------------------
    */

    let selectedTagNames = [];

    /*
    |--------------------------------------------------------------------------
    | HTMLエスケープ
    |--------------------------------------------------------------------------
    */

    function escapeHtml(text) {
        const div = document.createElement("div");

        div.textContent = text;

        return div.innerHTML;
    }

    /*
    |--------------------------------------------------------------------------
    | 選択中タグを画面に表示
    |--------------------------------------------------------------------------
    */

    function renderTags() {
        selectedTagsArea.innerHTML = "";

        selectedTagNames.forEach(tagName => {
            const tagElement =
                document.createElement("span");

            tagElement.className = "selected-tag";

            tagElement.innerHTML = `
                <span>#${escapeHtml(tagName)}</span>

                <button
                    type="button"
                    class="remove-tag-btn"
                    aria-label="#${escapeHtml(tagName)}を削除"
                >
                    ×
                </button>
            `;

            const removeButton =
                tagElement.querySelector(".remove-tag-btn");

            removeButton.addEventListener("click", () => {
                selectedTagNames =
                    selectedTagNames.filter(currentTagName =>
                        currentTagName.toLowerCase() !==
                        tagName.toLowerCase()
                    );

                renderTags();
            });

            selectedTagsArea.appendChild(tagElement);
        });

        /*
         * PHPへ送るhidden inputを更新
         */
        tagsInput.value =
            selectedTagNames.join(",");
    }

    /*
    |--------------------------------------------------------------------------
    | タグを追加
    |--------------------------------------------------------------------------
    */

    function addTag(tagName) {
        const normalizedTagName =
            String(tagName)
                .trim()
                .replace(/^#/, "");

        if (normalizedTagName === "") {
            return;
        }

        const alreadyExists =
            selectedTagNames.some(currentTagName =>
                currentTagName.toLowerCase() ===
                normalizedTagName.toLowerCase()
            );

        if (!alreadyExists) {
            selectedTagNames.push(normalizedTagName);

            renderTags();
        }

        tagSearchInput.value = "";
        tagResults.innerHTML = "";
        tagResults.style.display = "none";
    }

    /*
    |--------------------------------------------------------------------------
    | タグ検索
    |--------------------------------------------------------------------------
    */

    let searchTimer = null;

    tagSearchInput.addEventListener("input", () => {
        const keyword =
            tagSearchInput.value
                .trim()
                .replace(/^#/, "");

        clearTimeout(searchTimer);

        tagResults.innerHTML = "";

        if (keyword === "") {
            tagResults.style.display = "none";
            return;
        }

        searchTimer = setTimeout(async () => {
            try {
                tagResults.style.display = "block";

                tagResults.innerHTML =
                    '<div class="tag-search-loading">検索中...</div>';

                const response = await fetch(
                    "search_tags.php?q=" +
                    encodeURIComponent(keyword)
                );

                if (!response.ok) {
                    throw new Error(
                        `タグ検索に失敗しました: ${response.status}`
                    );
                }

                const data = await response.json();

                if (!Array.isArray(data)) {
                    throw new Error(
                        "タグ検索APIの形式が正しくありません"
                    );
                }

                tagResults.innerHTML = "";

                /*
                 * 既存タグ候補
                 */
                data.forEach(tag => {
                    if (!tag?.name) {
                        return;
                    }

                    const option =
                        document.createElement("button");

                    option.type = "button";
                    option.className = "tag-option";
                    option.textContent = "#" + tag.name;

                    option.addEventListener("click", () => {
                        addTag(tag.name);
                    });

                    tagResults.appendChild(option);
                });

                /*
                 * 新規作成候補
                 */
                const sameTagExists =
                    data.some(tag =>
                        String(tag?.name ?? "")
                            .toLowerCase() ===
                        keyword.toLowerCase()
                    );

                if (!sameTagExists) {
                    const createOption =
                        document.createElement("button");

                    createOption.type = "button";
                    createOption.className =
                        "tag-option create-tag-option";

                    createOption.textContent =
                        `＋「#${keyword}」を新規作成`;

                    createOption.addEventListener(
                        "click",
                        () => {
                            addTag(keyword);
                        }
                    );

                    tagResults.appendChild(createOption);
                }

                if (tagResults.children.length === 0) {
                    tagResults.style.display = "none";
                }

            } catch (error) {
                console.error(error);

                tagResults.innerHTML =
                    '<div class="tag-search-error">' +
                    "タグ検索中にエラーが発生しました。" +
                    "</div>";

                tagResults.style.display = "block";
            }
        }, 300);
    });

    /*
    |--------------------------------------------------------------------------
    | Enterキーで入力中のタグを追加
    |--------------------------------------------------------------------------
    */

    tagSearchInput.addEventListener("keydown", event => {
        if (event.key !== "Enter") {
            return;
        }

        event.preventDefault();

        const keyword =
            tagSearchInput.value
                .trim()
                .replace(/^#/, "");

        addTag(keyword);
    });

    /*
    |--------------------------------------------------------------------------
    | 検索結果の外をクリックしたら閉じる
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", event => {
        const clickedInput =
            tagSearchInput.contains(event.target);

        const clickedResults =
            tagResults.contains(event.target);

        if (!clickedInput && !clickedResults) {
            tagResults.style.display = "none";
        }
    });

    /*
    |--------------------------------------------------------------------------
    | 検索条件の再表示
    |--------------------------------------------------------------------------
    */

    const initialValue =
        tagsInput.value.trim();

    if (initialValue !== "") {
        selectedTagNames =
            initialValue
                .split(",")
                .map(tagName =>
                    tagName
                        .trim()
                        .replace(/^#/, "")
                )
                .filter(tagName =>
                    tagName !== ""
                );

        /*
         * 大文字・小文字を無視して重複削除
         */
        selectedTagNames =
            selectedTagNames.filter(
                (tagName, index, array) =>
                    array.findIndex(currentTagName =>
                        currentTagName.toLowerCase() ===
                        tagName.toLowerCase()
                    ) === index
            );
    }

    renderTags();
});