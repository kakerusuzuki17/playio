const modal =
    document.getElementById("imageModal");

const modalImage =
    document.getElementById("modalImage");

const closeButton =
    document.getElementById("closeImageModal");

document
    .querySelectorAll(".zoom-image")
    .forEach(image => {

        image.addEventListener("click", () => {

            modalImage.src = image.src;

            modal.classList.add("show");

        });

    });

closeButton.addEventListener("click", () => {

    modal.classList.remove("show");

});

modal.addEventListener("click", e => {

    if (e.target === modal) {

        modal.classList.remove("show");

    }

});