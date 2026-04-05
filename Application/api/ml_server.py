#!/usr/bin/env python3
"""
Serveur Flask léger pour la prédiction du temps de sommeil.
Charge modele_machine_learning.joblib + scaler_machine_learning.joblib.

Lancer : python3 ml_server.py
Écoute : http://localhost:5050/predict
"""
import os, sys
import joblib
import numpy as np
import pandas as pd
from flask import Flask, request, jsonify

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_DIR = os.path.join(BASE_DIR, '..', '..', 'modeles')

app = Flask(__name__)

# ── Chargement du modèle et du scaler ──────────────────────────────────────
try:
    model  = joblib.load(os.path.join(MODEL_DIR, 'modele_machine_learning.joblib'))
    scaler = joblib.load(os.path.join(MODEL_DIR, 'scaler_machine_learning.joblib'))
    print("✓ Modèle et scaler chargés.")
except Exception as e:
    print(f"✗ Erreur chargement modèle : {e}", file=sys.stderr)
    sys.exit(1)

# ── Encodages catégoriels (LabelEncoder alphabétique sklearn) ──────────────
GENDER_MAP = {
    'femme': 0, 'f': 0, 'autre': 0,
    'homme': 1, 'm': 1,
}
INTENSITY_MAP = {
    'high': 0,
    'low': 1,
    'medium': 2,
    # depuis champ "activite" onboarding
    'sedentaire': 1, 'leger': 1,
    'modere': 2,
    'actif': 0, 'tres_actif': 0,
}
ACTIVITY_MAP = {
    'cycling': 0, 'velo': 0,
    'dancing': 1, 'danse': 1,
    'hiit': 2,
    'running': 3, 'course': 3,
    'strength': 4, 'musculation': 4,
    'swimming': 5, 'natation': 5,
    'walking': 6, 'marche': 6,
    'weight_training': 7,
    'yoga': 8,
}
SMOKING_MAP = {
    'current': 0, 'fumeur': 0,
    'former':  1, 'ancien': 1, 'ancien_fumeur': 1,
    'never':   2, 'jamais': 2, 'non_fumeur': 2,
}

def encode(mapping, val, default):
    return mapping.get(str(val).lower().strip(), default)


@app.route('/predict', methods=['POST'])
def predict():
    d = request.get_json(force=True, silent=True)
    if not d:
        return jsonify({'error': 'JSON invalide', 'success': False}), 400

    try:
        age       = float(d.get('age', 30))
        poids     = float(d.get('poids', d.get('weight_kg', 70)))
        taille    = float(d.get('taille', d.get('height_cm', 170)))
        bmi       = poids / ((taille / 100) ** 2)

        duration  = float(d.get('duration_minutes', 30))
        steps     = float(d.get('daily_steps', 8000))
        stress    = float(d.get('stress_level', 5))
        hydration = float(d.get('hydration_level', 2.5))

        gender    = encode(GENDER_MAP,    d.get('sexe',          d.get('gender',        'homme')), 0)
        intensity = encode(INTENSITY_MAP, d.get('activite',      d.get('intensity',     'modere')), 2)
        activity  = encode(ACTIVITY_MAP,  d.get('activity_type', 'walking'),                        6)
        smoking   = encode(SMOKING_MAP,   d.get('smoking_status','jamais'),                         2)

        features = pd.DataFrame([[age, bmi, duration, steps, stress, gender, intensity, activity, smoking, hydration]],
                                 columns=['age','bmi','duration_minutes','daily_steps','stress_level',
                                          'gender','intensity','activity_type','smoking_status','hydration_level'])

        x_scaled = scaler.transform(features)
        pred     = float(model.predict(x_scaled)[0])
        pred     = round(max(4.0, min(12.0, pred)), 1)

        return jsonify({'sleep_hours': pred, 'success': True})

    except Exception as e:
        return jsonify({'error': str(e), 'success': False}), 500


@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok'})


if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5050, debug=False)
