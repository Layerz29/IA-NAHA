// ─────────────────────────────────────────────
// usePlan.js — Hook React pour sauvegarder et
// récupérer les plans depuis le backend PHP
//
// Placer dans : src/hooks/usePlan.js
// ─────────────────────────────────────────────

import { useState, useCallback } from "react";

const API_BASE = "http://localhost:8888/SD4/IA-NAHA/Application/api";
// Si ton MAMP tourne sur le port 80, utilise :

export function usePlan() {
  const [saving,  setSaving]  = useState(false);
  const [loading, setLoading] = useState(false);
  const [error,   setError]   = useState(null);

  // ── Sauvegarde un plan en base ──────────────
  const savePlan = useCallback(async (profil, plan) => {
    setSaving(true);
    setError(null);
    try {
      const res = await fetch(`${API_BASE}/save_plan.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ profil, plan }),
      });
      const data = await res.json();
      if (!res.ok || data.error) throw new Error(data.error || "Erreur sauvegarde");
      return data; // { success, user_id, plan_id }
    } catch (e) {
      setError(e.message);
      return null;
    } finally {
      setSaving(false);
    }
  }, []);

  // ── Récupère les plans d'un user ────────────
  const getPlans = useCallback(async (user_id) => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch(`${API_BASE}/get_plans.php?user_id=${user_id}`);
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      return data.plans;
    } catch (e) {
      setError(e.message);
      return [];
    } finally {
      setLoading(false);
    }
  }, []);

  // ── Récupère le détail d'un plan ────────────
  const getPlanDetail = useCallback(async (plan_id) => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch(`${API_BASE}/get_plans.php?plan_id=${plan_id}`);
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      return data;
    } catch (e) {
      setError(e.message);
      return null;
    } finally {
      setLoading(false);
    }
  }, []);

  return { savePlan, getPlans, getPlanDetail, saving, loading, error };
}
