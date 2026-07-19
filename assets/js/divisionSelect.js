document.addEventListener("DOMContentLoaded", () => {

    const categorySelect =
        document.getElementById("categorySelect");

    const gameIdInput =
        document.getElementById("gameId");

    const divisionField =
        document.getElementById("divisionField");

    const divisionSelect =
        document.getElementById("divisionSelect");

    const showNewDivisionButton =
        document.getElementById("showNewDivisionButton");

    const newDivisionArea =
        document.getElementById("newDivisionArea");

    const newDivisionName =
        document.getElementById("newDivisionName");

    const cancelNewDivisionButton =
        document.getElementById("cancelNewDivisionButton");

    const divisionMessage =
        document.getElementById("divisionMessage");


    /**
     * 部門を使うカテゴリか判定
     */
    function isDivisionCategory() {

        const selectedOption =
            categorySelect.options[
                categorySelect.selectedIndex
            ];

        const categoryName =
            selectedOption?.dataset.categoryName ?? "";

        return (
            categoryName === "タイムアタック" ||
            categoryName === "ハイスコア"
        );
    }


    /**
     * 部門選択欄を初期状態に戻す
     */
    function resetDivisionField() {

        divisionSelect.innerHTML =
            '<option value="">' +
            "部門を選択してください" +
            "</option>";

        divisionSelect.disabled = false;

        newDivisionName.value = "";

        newDivisionArea.style.display = "none";

        showNewDivisionButton.style.display =
            "inline-block";

        divisionMessage.textContent = "";
    }


    /**
     * 部門一覧をデータベースから取得
     */
    async function loadDivisions() {

        resetDivisionField();

        // タイムアタック・ハイスコア以外
        if (!isDivisionCategory()) {
            divisionField.style.display = "none";
            return;
        }

        divisionField.style.display = "block";

        const gameId = gameIdInput.value;
        const categoryId = categorySelect.value;

        // ゲームが未選択
        if (!gameId) {

            divisionSelect.disabled = true;

            divisionMessage.textContent =
                "先にゲームを選択してください";

            return;
        }

        // カテゴリが未選択
        if (!categoryId) {
            return;
        }

        divisionSelect.disabled = true;

        divisionMessage.textContent =
            "部門を読み込んでいます...";

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
                    "部門一覧の取得に失敗しました"
                );
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(
                    result.message ??
                    "部門一覧の取得に失敗しました"
                );
            }

            divisionSelect.innerHTML =
                '<option value="">' +
                "部門を選択してください" +
                "</option>";

            result.divisions.forEach(division => {

                const option =
                    document.createElement("option");

                option.value = division.id;
                option.textContent = division.name;

                divisionSelect.appendChild(option);
            });

            if (result.divisions.length === 0) {

                divisionMessage.textContent =
                    "登録済みの部門はありません。" +
                    "新しい部門を追加できます。";

            } else {

                divisionMessage.textContent = "";
            }

            divisionSelect.disabled = false;

        } catch (error) {

            console.error(error);

            divisionSelect.disabled = true;

            divisionMessage.textContent =
                "部門の読み込みに失敗しました";
        }
    }


    /**
     * 「新しい部門を追加」を押したとき
     */
    showNewDivisionButton.addEventListener(
        "click",
        () => {

            divisionSelect.value = "";
            divisionSelect.disabled = true;

            newDivisionArea.style.display = "block";

            showNewDivisionButton.style.display = "none";

            newDivisionName.focus();
        }
    );


    /**
     * 「既存の部門から選ぶ」を押したとき
     */
    cancelNewDivisionButton.addEventListener(
        "click",
        () => {

            newDivisionName.value = "";

            newDivisionArea.style.display = "none";

            showNewDivisionButton.style.display =
                "inline-block";

            divisionSelect.disabled = false;
            divisionSelect.focus();
        }
    );


    /**
     * 既存の部門を選んだ場合、
     * 新規部門名を空にする
     */
    divisionSelect.addEventListener("change", () => {

        if (divisionSelect.value !== "") {
            newDivisionName.value = "";
        }
    });


    /**
     * カテゴリを変えたとき
     */
    categorySelect.addEventListener(
        "change",
        loadDivisions
    );


    /**
     * gameSearch.jsから呼び出すイベント
     *
     * ゲームを選択したら、
     * game-selectedイベントを発生させます。
     */
    document.addEventListener(
        "game-selected",
        loadDivisions
    );


    /**
     * ゲーム選択を解除したとき
     */
    document.addEventListener(
        "game-cleared",
        () => {

            resetDivisionField();

            if (isDivisionCategory()) {

                divisionField.style.display = "block";
                divisionSelect.disabled = true;

                divisionMessage.textContent =
                    "先にゲームを選択してください";

            } else {

                divisionField.style.display = "none";
            }
        }
    );

});