const shareBtn = document.getElementById("share-x-btn");

if (shareBtn) {

    shareBtn.addEventListener("click", () => {

        window.open(
            shareBtn.dataset.url,
            "_blank"
        );

    });

}

const closeBtn = document.getElementById("close-share-btn");

if (closeBtn) {

    closeBtn.addEventListener("click", () => {

        closeBtn.parentElement.remove();

    });

}