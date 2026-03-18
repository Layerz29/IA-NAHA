import { useEffect, useState } from "react";
import { usePlan } from "./usePlan";

// ─────────────────────────────────────────────
// Dashboard.jsx — Tableau de bord nutritionnel
// Affiche les plans sauvegardés avec graphiques
// ─────────────────────────────────────────────

const COLORS = {
  proteines: "#4ade80",
  glucides:  "#facc15",
  lipides:   "#f97316",
  bg:        "rgba(255,255,255,0.04)",
  border:    "rgba(130,200,100,0.15)",
  green:     "#9ecf7a",
  text:      "#e8f0e6",
  muted:     "#7a9e72",
};

// ── Mini graphique camembert (SVG pur) ───────
function PieChart({ proteines, glucides, lipides }) {
  const total = proteines * 4 + glucides * 4 + lipides * 9;
  if (!total) return null;

  const pct = {
    p: (proteines * 4) / total,
    g: (glucides  * 4) / total,
    l: (lipides   * 9) / total,
  };

  const slices = [
    { pct: pct.p, color: COLORS.proteines },
    { pct: pct.g, color: COLORS.glucides  },
    { pct: pct.l, color: COLORS.lipides   },
  ];

  let cumul = 0;
  const paths = slices.map(({ pct, color }) => {
    const start = cumul * 2 * Math.PI - Math.PI / 2;
    cumul += pct;
    const end = cumul * 2 * Math.PI - Math.PI / 2;
    const r = 40, cx = 50, cy = 50;
    const x1 = cx + r * Math.cos(start);
    const y1 = cy + r * Math.sin(start);
    const x2 = cx + r * Math.cos(end);
    const y2 = cy + r * Math.sin(end);
    const large = pct > 0.5 ? 1 : 0;
    return (
      <path
        key={color}
        d={`M${cx},${cy} L${x1},${y1} A${r},${r} 0 ${large},1 ${x2},${y2} Z`}
        fill={color}
      />
    );
  });

  return (
    <div style={{ textAlign: "center" }}>
      <svg viewBox="0 0 100 100" width={120} height={120}>{paths}</svg>
      <div style={{ display: "flex", justifyContent: "center", gap: 12, marginTop: 6, fontSize: 11 }}>
        {[
          { label: "Prot", color: COLORS.proteines, val: Math.round(pct.p * 100) },
          { label: "Gluc", color: COLORS.glucides,  val: Math.round(pct.g * 100) },
          { label: "Lip",  color: COLORS.lipides,   val: Math.round(pct.l * 100) },
        ].map(({ label, color, val }) => (
          <span key={label} style={{ color }}>● {label} {val}%</span>
        ))}
      </div>
    </div>
  );
}

// ── Graphique barres calories par jour ───────
function CaloriesChart({ statsJours, cible }) {
  if (!statsJours?.length) return null;
  const max = Math.max(...statsJours.map(j => j.calories), cible || 0);

  return (
    <div>
      <div style={{ fontSize: "0.7rem", letterSpacing: 2, textTransform: "uppercase", color: COLORS.green, marginBottom: 12 }}>
        ◈ Calories par jour
      </div>
      <div style={{ display: "flex", alignItems: "flex-end", gap: 8, height: 100 }}>
        {statsJours.map(j => (
          <div key={j.jour} style={{ flex: 1, display: "flex", flexDirection: "column", alignItems: "center", gap: 4 }}>
            <div style={{ fontSize: 9, color: COLORS.muted }}>{j.calories}</div>
            <div style={{
              width: "100%",
              height: `${(j.calories / max) * 80}px`,
              background: `linear-gradient(to top, #4a8c3a, #9ecf7a)`,
              borderRadius: "4px 4px 0 0",
              minHeight: 4,
            }} />
            <div style={{ fontSize: 9, color: COLORS.muted }}>J{j.jour}</div>
          </div>
        ))}
        {/* Ligne cible */}
        {cible && (
          <div style={{ position: "relative" }}>
            <div style={{
              position: "absolute",
              bottom: `${(cible / max) * 80 + 20}px`,
              right: 0,
              left: 0,
              borderTop: "1px dashed #f97316",
              fontSize: 9,
              color: "#f97316",
            }}>cible</div>
          </div>
        )}
      </div>
    </div>
  );
}

// ── Card plan ────────────────────────────────
function PlanCard({ plan, onSelect, selected }) {
  const date = new Date(plan.date_creation).toLocaleDateString("fr-FR");
  return (
    <div
      onClick={() => onSelect(plan.id)}
      style={{
        background: selected ? "rgba(130,200,100,0.12)" : COLORS.bg,
        border: `1px solid ${selected ? "rgba(130,200,100,0.5)" : COLORS.border}`,
        borderRadius: 12,
        padding: "1rem",
        cursor: "pointer",
        transition: "all 0.2s",
        marginBottom: "0.8rem",
      }}
    >
      <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 6 }}>
        <span style={{ color: COLORS.green, fontSize: "0.8rem", letterSpacing: 1 }}>
          Plan du {date}
        </span>
        <span style={{ color: COLORS.muted, fontSize: "0.75rem" }}>
          {plan.duree_jours}j · {plan.nb_repas} repas
        </span>
      </div>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(4,1fr)", gap: 6 }}>
        {[
          { l: "Kcal", v: plan.calories_cibles },
          { l: "Prot", v: plan.proteines_g + "g" },
          { l: "Gluc", v: plan.glucides_g + "g" },
          { l: "Lip",  v: plan.lipides_g + "g" },
        ].map(({ l, v }) => (
          <div key={l} style={{ textAlign: "center" }}>
            <div style={{ fontSize: "0.9rem", fontWeight: 700, color: COLORS.text }}>{v}</div>
            <div style={{ fontSize: "0.65rem", color: COLORS.muted, textTransform: "uppercase", letterSpacing: 1 }}>{l}</div>
          </div>
        ))}
      </div>
    </div>
  );
}

// ── Composant principal Dashboard ────────────
export default function Dashboard({ userId }) {
  const { getPlans, getPlanDetail, loading, error } = usePlan();
  const [plans,      setPlans]      = useState([]);
  const [selectedId, setSelectedId] = useState(null);
  const [detail,     setDetail]     = useState(null);
  const [activeJour, setActiveJour] = useState(1);

  // Charge la liste des plans
  useEffect(() => {
    if (!userId) return;
    getPlans(userId).then(data => {
      setPlans(data || []);
      if (data?.length > 0) handleSelect(data[0].id);
    });
  }, [userId]);

  const handleSelect = async (planId) => {
    setSelectedId(planId);
    setActiveJour(1);
    const data = await getPlanDetail(planId);
    setDetail(data);
  };

  const s = {
    root: { fontFamily: "'Playfair Display',Georgia,serif", background: "linear-gradient(135deg,#0f1b0e,#1a2f19,#0d1f0c)", minHeight: "100vh", color: COLORS.text, padding: "2rem 1rem" },
    box:  { maxWidth: 1100, margin: "0 auto" },
    grid: { display: "grid", gridTemplateColumns: "300px 1fr", gap: "1.5rem" },
    card: { background: COLORS.bg, border: `1px solid ${COLORS.border}`, borderRadius: 16, padding: "1.5rem" },
    sec:  { fontSize: "0.7rem", letterSpacing: 3, textTransform: "uppercase", color: COLORS.green, marginBottom: "1rem" },
    badge:{ display:"inline-block", background:"rgba(130,200,100,0.15)", border:"1px solid rgba(130,200,100,0.4)", borderRadius:20, padding:"4px 14px", fontSize:11, letterSpacing:3, textTransform:"uppercase", color:COLORS.green, marginBottom:"1rem" },
    title:{ fontSize:"clamp(1.5rem,3vw,2.2rem)", fontWeight:700, color:"#f0f8ee", margin:"0 0 2rem" },
  };

  return (
    <div style={s.root}>
      <div style={s.box}>
        <div style={s.badge}>✦ Dashboard Nutrition</div>
        <h1 style={s.title}>Mes plans nutritionnels</h1>

        {error && (
          <div style={{ background:"rgba(200,60,60,0.1)", border:"1px solid rgba(200,60,60,0.3)", borderRadius:10, padding:"1rem", color:"#f0a0a0", marginBottom:"1rem" }}>
            ⚠ {error}
          </div>
        )}

        {loading && (
          <div style={{ textAlign:"center", color:COLORS.muted, padding:"3rem" }}>Chargement…</div>
        )}

        {!loading && plans.length === 0 && (
          <div style={{ textAlign:"center", color:COLORS.muted, padding:"3rem" }}>
            Aucun plan sauvegardé pour l'instant.
          </div>
        )}

        {plans.length > 0 && (
          <div style={s.grid}>

            {/* ── Colonne gauche : liste des plans ── */}
            <div>
              <div style={s.card}>
                <div style={s.sec}>◈ Mes plans ({plans.length})</div>
                {plans.map(p => (
                  <PlanCard
                    key={p.id}
                    plan={p}
                    onSelect={handleSelect}
                    selected={p.id === selectedId}
                  />
                ))}
              </div>
            </div>

            {/* ── Colonne droite : détail du plan ── */}
            {detail && (
              <div>

                {/* Macros + camembert */}
                <div style={{ ...s.card, display:"grid", gridTemplateColumns:"1fr auto", gap:"2rem", marginBottom:"1.5rem" }}>
                  <div>
                    <div style={s.sec}>◈ Objectifs journaliers</div>
                    <div style={{ display:"grid", gridTemplateColumns:"repeat(2,1fr)", gap:"1rem" }}>
                      {[
                        { label:"Calories cibles", val: detail.plan.calories_cibles, unit:"kcal", color:"#9ecf7a" },
                        { label:"BMR",             val: detail.plan.bmr,             unit:"kcal", color:"#7a9e72" },
                        { label:"Protéines",       val: detail.plan.proteines_g,     unit:"g",    color: COLORS.proteines },
                        { label:"Glucides",        val: detail.plan.glucides_g,      unit:"g",    color: COLORS.glucides  },
                        { label:"Lipides",         val: detail.plan.lipides_g,       unit:"g",    color: COLORS.lipides   },
                      ].map(({ label, val, unit, color }) => (
                        <div key={label} style={{ background:"rgba(255,255,255,0.03)", borderRadius:10, padding:"0.8rem" }}>
                          <div style={{ fontSize:"1.3rem", fontWeight:700, color }}>{val}</div>
                          <div style={{ fontSize:"0.65rem", color:COLORS.muted, textTransform:"uppercase", letterSpacing:1 }}>{label} ({unit})</div>
                        </div>
                      ))}
                    </div>
                  </div>
                  <div style={{ display:"flex", alignItems:"center" }}>
                    <PieChart
                      proteines={detail.plan.proteines_g}
                      glucides={detail.plan.glucides_g}
                      lipides={detail.plan.lipides_g}
                    />
                  </div>
                </div>

                {/* Graphique calories par jour */}
                <div style={{ ...s.card, marginBottom:"1.5rem" }}>
                  <CaloriesChart
                    statsJours={detail.stats_jours}
                    cible={detail.plan.calories_cibles}
                  />
                </div>

                {/* Sélecteur de jour */}
                <div style={{ ...s.card, marginBottom:"1.5rem" }}>
                  <div style={s.sec}>◈ Repas par jour</div>
                  <div style={{ display:"flex", gap:8, flexWrap:"wrap", marginBottom:"1rem" }}>
                    {Object.keys(detail.jours).map(j => (
                      <button
                        key={j}
                        onClick={() => setActiveJour(Number(j))}
                        style={{
                          padding:"6px 14px", borderRadius:20, border:"none", cursor:"pointer",
                          background: activeJour === Number(j) ? "linear-gradient(135deg,#4a8c3a,#2d6622)" : "rgba(255,255,255,0.06)",
                          color: activeJour === Number(j) ? "#e8f8e0" : COLORS.muted,
                          fontSize:"0.85rem",
                        }}
                      >
                        Jour {j}
                      </button>
                    ))}
                  </div>

                  {/* Repas du jour actif */}
                  {detail.jours[activeJour]?.map((repas, i) => (
                    <div key={i} style={{
                      background:"rgba(255,255,255,0.03)",
                      border:`1px solid ${COLORS.border}`,
                      borderRadius:10, padding:"1rem", marginBottom:"0.8rem"
                    }}>
                      <div style={{ display:"flex", justifyContent:"space-between", marginBottom:6 }}>
                        <div>
                          <span style={{ fontSize:"0.65rem", background:"rgba(130,200,100,0.15)", color:COLORS.green, padding:"2px 8px", borderRadius:10, textTransform:"uppercase", letterSpacing:1, marginRight:8 }}>
                            {repas.type_repas?.replace("_"," ")}
                          </span>
                          <span style={{ fontWeight:700, color:COLORS.text }}>{repas.nom}</span>
                        </div>
                        <span style={{ color:COLORS.green, fontWeight:700 }}>{repas.calories} kcal</span>
                      </div>
                      <div style={{ fontSize:"0.75rem", color:COLORS.muted, marginBottom:6 }}>
                        P: {repas.proteines}g · G: {repas.glucides}g · L: {repas.lipides}g · F: {repas.fibres}g
                      </div>
                      <div style={{ fontSize:"0.8rem", color:"#a0b89a" }}>
                        {repas.aliments?.map((a, ai) => (
                          <span key={ai}>
                            {a.nom} <strong style={{color:COLORS.green}}>{a.quantite_g}g</strong>
                            {ai < repas.aliments.length - 1 ? " · " : ""}
                          </span>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>

              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
