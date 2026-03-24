document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector("#newsletter-form");
    const msg  = document.querySelector("#newsletter-msg");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        msg.textContent = "Chargement...";
        msg.style.color = "#6b7280";

        let data = new FormData(form);

        let res = await fetch("conseil.php?ajax=newsletter", {
            method: "POST",
            body: data
        });

        let json = await res.json();

        msg.textContent = json.msg;
        msg.style.color = json.ok ? "#45c778" : "#ef4444";
    });

});
