const tagSearch = document.getElementById("tagSearch");
const tagResults = document.getElementById("tagResults");
const selectedTags = document.getElementById("selectedTags");
const tagsInput = document.getElementById("tagsInput");

let tags = [];

if (tagsInput && tagsInput.value.trim() !== "") {
    tags = tagsInput.value
        .split(",")
        .map(tag => tag.trim())
        .filter(tag => tag !== "");

    renderTags();
}

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

            data.forEach(tag => {
                const option = document.createElement("div");
                option.className = "tag-option";
                option.textContent = "#" + tag.name;

                option.addEventListener("click", () => {
                    addTag(tag.name);
                });

                tagResults.appendChild(option);
            });
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

        const tagText = document.createElement("span");
        tagText.textContent = "#" + tag;

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className = "remove-tag-btn";
        removeButton.textContent = "×";

        removeButton.addEventListener("click", () => {
            tags = tags.filter(
                currentTag =>
                    currentTag.toLowerCase() !== tag.toLowerCase()
            );

            renderTags();
        });

        tagElement.appendChild(tagText);
        tagElement.appendChild(removeButton);
        selectedTags.appendChild(tagElement);
    });

    tagsInput.value = tags.join(",");
}