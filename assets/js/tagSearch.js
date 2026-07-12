const tagSearch = document.getElementById("tagSearch");
const tagResults = document.getElementById("tagResults");
const selectedTags = document.getElementById("selectedTags");
const tagsInput = document.getElementById("tagsInput");

let tags = [];

if (tagSearch) {
    tagSearch.addEventListener("input", async () => {
        const keyword = tagSearch.value
            .trim()
            .replace(/^#/, "");

        tagResults.innerHTML = "";

        if (keyword === "") {
            return;
        }

        try {
            const response = await fetch(
                "search_tags.php?q=" + encodeURIComponent(keyword)
            );

            if (!response.ok) {
                throw new Error("タグ検索に失敗しました");
            }

            const data = await response.json();

            // 既存タグ候補
            data.forEach(tag => {
                const option = document.createElement("div");
                option.className = "tag-option";
                option.textContent = "#" + tag.name;

                option.addEventListener("click", () => {
                    addTag(tag.name);
                });

                tagResults.appendChild(option);
            });

            // 一番下に新規作成候補
            const createOption = document.createElement("div");
            createOption.className = "tag-option create-tag-option";
            createOption.textContent =
                `＋「#${keyword}」を新規作成`;

            createOption.addEventListener("click", () => {
                addTag(keyword);
            });

            tagResults.appendChild(createOption);

        } catch (error) {
            console.error(error);
            tagResults.innerHTML =
                "<p>タグ検索中にエラーが発生しました。</p>";
        }
    });
}

function addTag(tagName) {
    const normalizedTagName = tagName
        .trim()
        .replace(/^#/, "");

    if (normalizedTagName === "") {
        return;
    }

    const alreadyExists = tags.some(tag =>
        tag.toLowerCase() === normalizedTagName.toLowerCase()
    );

    if (!alreadyExists) {
        tags.push(normalizedTagName);
        renderTags();
    }

    tagSearch.value = "";
    tagResults.innerHTML = "";
}

function renderTags() {
    selectedTags.innerHTML = "";

    tags.forEach(tag => {
        const tagElement = document.createElement("span");
        tagElement.className = "selected-tag";

        tagElement.innerHTML = `
            <span>#${escapeHtml(tag)}</span>
            <button
                type="button"
                class="remove-tag-btn"
            >
                ×
            </button>
        `;

        const removeButton =
            tagElement.querySelector(".remove-tag-btn");

        removeButton.addEventListener("click", () => {
            tags = tags.filter(currentTag =>
                currentTag.toLowerCase() !== tag.toLowerCase()
            );

            renderTags();
        });

        selectedTags.appendChild(tagElement);
    });

    tagsInput.value = tags.join(",");
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}