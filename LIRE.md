##  ENGLISH

# AI and Machine Learning Applied to Sports and Nutrition
# 📌 Project Description
IA-NAHA is an AI-driven virtual health assistant designed to bridge the gap between daily lifestyle habits and optimal recovery.
Using Machine Learning, the system analyzes the synergy between physical activity, stress, and biometrics to predict an individual's sleep needs and provide personalized nutritional guidance.
The system automatically adapts its recommendations based on:

Daily Activity: Steps, calories burned, and exercise intensity.

Biometric Markers: Heart rate, Blood pressure, BMI, and Age.

Psychological State: Daily stress levels.

Recovery Data: Predicted sleep duration.
The project relies on Machine Learning techniques, biometric data analysis, and predictive modeling.
# ❓ Problem Statement
How can AI leverage daily lifestyle and biometric data to predict recovery needs (sleep) for the general population, and provide automated nutritional guidance to improve overall well-being?

The challenge is to develop a model capable of:

Predicting recovery: Estimating sleep hours based on daily strain.

Anticipating energy needs: Calculating requirements based on activity and biometrics.

Nutritional Mapping: Linking predicted recovery states to specific food recommendations from the CIQUAL database.
## 🌍 Sources
# -https://pacompendium.com/adult-compendium/ 
# -https://ciqual.anses.fr/#/cms/telechargement/node/20 
# -https://www.kaggle.com/datasets/uom190346a/sleep-health-and-lifestyle-dataset/data 
# -https://www.kaggle.com/datasets/valakhorasani/gym-members-exercise-dataset 

## 🏗️ Project Architecture
     IA-NAHA/
     │
     ├── data/
     │   ├── raw/
     │   ├── processed/
     │
     ├── database/
     │   ├── schema.sql
     │   ├── dump.sql
     │
     ├── notebooks/
     │
     ├── src/
     │   ├── preprocessing/
     │   ├── models/
     │   ├── api/
     │
     ├── reports/
     │
     ├── docs/
     │
     ├── .gitignore
     └── README.md

## Francais
# IA et Machine Learning appliqués à l'Hygiène de vie, la Santé et la Nutrition
# 📌 Description du projet
IA-NAHA est un assistant virtuel intelligent conçu pour analyser l'impact du mode de vie sur la santé globale.
Grâce au Machine Learning, le système explore la synergie entre l'activité physique, le stress et les constantes biométriques pour prédire les besoins de récupération (sommeil) et proposer un accompagnement nutritionnel sur mesure.

L'objectif est d'adapter automatiquement les conseils en fonction de :

L'activité quotidienne : Pas, calories brûlées, intensité des exercices.

Marqueurs biométriques : Rythme cardiaque, tension, IMC et âge.

État psychologique : Niveau de stress ressenti.

Données de récupération : Prédiction de la durée du sommeil.
# ❓ Problématique
"Comment l'IA peut-elle utiliser les données de vie quotidienne et les constantes biométriques pour prédire les besoins de récupération (sommeil) du grand public, et recommander des ajustements nutritionnels automatisés via la base CIQUAL pour optimiser le bien-être ?"

L’enjeu est de développer un modèle capable de :

Prédire la récupération : Estimer les heures de sommeil en fonction de la fatigue accumulée.

Anticiper les besoins énergétiques : Calculer les besoins caloriques et nutritionnels.

Recommandation intelligente : Lier les résultats de l'IA à des ajustements alimentaires (ex: magnésium, protéines) via la table CIQUAL.
## 🌍 Sources
# -https://pacompendium.com/adult-compendium/ 
# -https://ciqual.anses.fr/#/cms/telechargement/node/20 
# -https://www.kaggle.com/datasets/uom190346a/sleep-health-and-lifestyle-dataset/data 
# -https://www.kaggle.com/datasets/valakhorasani/gym-members-exercise-dataset 

## 🏗️ Architecture du projet

     IA-NAHA/
     │
     ├── data/
     │   ├── raw/
     │   ├── processed/
     │
     ├── database/
     │   ├── schema.sql
     │   ├── dump.sql
     │
     ├── notebooks/
     │
     ├── src/
     │   ├── preprocessing/
     │   ├── models/
     │   ├── api/
     │
     ├── reports/
     │
     ├── docs/
     │
     ├── .gitignore
     └── README.md


