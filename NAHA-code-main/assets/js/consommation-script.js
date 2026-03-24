document.addEventListener("DOMContentLoaded", () => {

    const prodSelect = document.getElementById("prod-select");
    const prodQte = document.getElementById("prod-qte");
    const prodKcal = document.getElementById("prod-kcal");

    const sportSelect = document.getElementById("sport-select");
    const sportDuree = document.getElementById("sport-duree");
    const sportKcal = document.getElementById("sport-kcal");

    const logBtn = document.getElementById("btn-log");
    const logMsg = document.getElementById("log-msg");

    /* ===== PRODUITS ===== */
    fetch("consommation.php?ajax=produits")
        .then(res => res.json())
        .then(rows => {
            prodSelect.innerHTML = `<option value="">Sélectionner un produit</option>`;
            rows.forEach(r => {
                prodSelect.innerHTML += `<option value="${r.id_produit}" data-kcal="${r.energie_kcal}">
                  ${r.nom_produit}
                </option>`;
            });
        });

    function updateProdKcal() {
        const opt = prodSelect.selectedOptions[0];
        if (!opt || !opt.dataset.kcal) {
            prodKcal.textContent = "0";
            return;
        }
        const kcal100 = parseFloat(opt.dataset.kcal);
        const qte = parseFloat(prodQte.value);

        prodKcal.textContent = ((kcal100 * qte) / 100).toFixed(0);
    }
    prodSelect.addEventListener("change", updateProdKcal);
    prodQte.addEventListener("input", updateProdKcal);

    /* ===== SPORTS ===== */
    fetch("consommation.php?ajax=sports")
        .then(res => res.json())
        .then(rows => {
            sportSelect.innerHTML = `<option value="">Sélectionner un sport</option>`;
            rows.forEach(r => {
                sportSelect.innerHTML += `<option value="${r.id_sport}" data-k70="${r.kcal_h_70kg}">
                  ${r.nom_sport}
                </option>`;
            });
        });

    function updateSportKcal() {
        const opt = sportSelect.selectedOptions[0];
        if (!opt || !opt.dataset.k70) {
            sportKcal.textContent = "0";
            return;
        }

        const kcalH = parseFloat(opt.dataset.k70);
        const duree = parseFloat(sportDuree.value);

        sportKcal.textContent = ((kcalH * duree) / 60).toFixed(0);
    }
    sportSelect.addEventListener("change", updateSportKcal);
    sportDuree.addEventListener("input", updateSportKcal);

    /* ===== LOG ===== */
    logBtn.addEventListener("click", () => {

        const data = new FormData();
        data.append("id_produit", prodSelect.value);
        data.append("quantite", prodQte.value);
        data.append("id_sport", sportSelect.value);
        data.append("duree", sportDuree.value);

        logMsg.textContent = "Enregistrement...";
        logMsg.className = "log-msg";

        fetch("consommation.php?ajax=log", { method:"POST", body:data })
        .then(res => res.json())
        .then(r => {
            if (r.ok) {
                logMsg.textContent = "✔ Ajouté à ta journée";
                logMsg.classList.add("log-ok");
            } else {
                logMsg.textContent = "❌ " + (r.error || "Erreur");
                logMsg.classList.add("log-error");
            }
        })
        .catch(() => {
            logMsg.textContent = "❌ Erreur serveur";
            logMsg.classList.add("log-error");
        });

    });

});
