document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("calc-form");
  if (!form) return;

  const sexeInput = document.getElementById("sexe");
  const resKcal = document.getElementById("res-kcal");
  const goalMaintien = document.getElementById("goal-maintien");
  const goalPerte = document.getElementById("goal-perte");
  const goalPrise = document.getElementById("goal-prise");
  const saveBtn = document.getElementById("save-goal");
  const saveMsg = document.getElementById("save-goal-msg");
  const goalButtons = Array.from(document.querySelectorAll(".goal-btn"));

  let state = {
    maintenance: null,
    objectifNom: "Maintien",
    objectifKcal: null,
  };

  function formatKcal(v) {
    return `${Math.round(v)} kcal/j`;
  }

  function computeMaintenance(data) {
    const age = parseFloat(data.get("age") || document.getElementById("age").value);
    const taille = parseFloat(data.get("taille") || document.getElementById("taille").value);
    const poids = parseFloat(data.get("poids") || document.getElementById("poids").value);
    const activite = parseFloat(data.get("activite") || document.getElementById("activite").value);
    const sexe = (data.get("sexe") || sexeInput.value || "H").toString();

    const bmr =
      sexe === "F"
        ? 10 * poids + 6.25 * taille - 5 * age - 161
        : 10 * poids + 6.25 * taille - 5 * age + 5;

    return Math.round(bmr * activite);
  }

  function refreshGoals(maintenance) {
    const perte = maintenance - 400;
    const prise = maintenance + 300;
    goalMaintien.textContent = formatKcal(maintenance);
    goalPerte.textContent = formatKcal(perte);
    goalPrise.textContent = formatKcal(prise);
    resKcal.textContent = maintenance;
  }

  function setActiveGoal(btn) {
    goalButtons.forEach((b) => b.classList.remove("is-active"));
    btn.classList.add("is-active");
    const delta = parseFloat(btn.dataset.delta || "0");
    state.objectifNom = btn.dataset.name || "Maintien";
    state.objectifKcal = Math.round((state.maintenance || 0) + delta);
  }

  document.querySelectorAll(".segmented-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".segmented-btn").forEach((b) => b.classList.remove("is-active"));
      btn.classList.add("is-active");
      sexeInput.value = btn.dataset.sexe || "H";
    });
  });

  goalButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      if (state.maintenance == null) return;
      setActiveGoal(btn);
    });
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const data = new FormData(form);
    data.set("sexe", sexeInput.value);

    state.maintenance = computeMaintenance(data);
    refreshGoals(state.maintenance);

    const defaultGoal = document.querySelector(".goal-btn.is-active") || goalButtons[0];
    if (defaultGoal) setActiveGoal(defaultGoal);
  });

  saveBtn?.addEventListener("click", async () => {
    if (state.maintenance == null || state.objectifKcal == null) {
      saveMsg.textContent = "Calcule d'abord ton objectif.";
      return;
    }

    const payload = {
      age: Number(document.getElementById("age").value),
      taille: Number(document.getElementById("taille").value),
      poids: Number(document.getElementById("poids").value),
      activite: Number(document.getElementById("activite").value),
      sexe: sexeInput.value,
      maintenance: state.maintenance,
      objectif_nom: state.objectifNom,
      objectif_kcal: state.objectifKcal,
    };

    saveMsg.textContent = "Enregistrement...";
    try {
      const res = await fetch("actions/save_goal.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      saveMsg.textContent = json.message || (json.ok ? "Objectif enregistré." : "Erreur.");
    } catch (_err) {
      saveMsg.textContent = "Erreur réseau.";
    }
  });
});
