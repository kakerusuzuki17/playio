document.querySelectorAll(".reply-btn").forEach(button => {

    button.addEventListener("click", () => {

        const id = button.dataset.postId;

        const form = document.getElementById("reply-" + id);

        form.style.display =
            form.style.display === "block"
            ? "none"
            : "block";

    });

});