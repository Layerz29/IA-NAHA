# 🌿 IA-NAHA - Nutrition Intelligence & Sleep Recovery Prediction

> **L3 MIASHS - Science des Données 4 Université de Montpellier - 2025–2026**  
> Supervised by: Sandra Bringay · Namrata Patel · Théodore Michel-Picque

---

## 🔗 Live Demo

> 🌐 **[ia-naha.fr](https://ianaha.rf.gd/IA-NAHA/Application/index.html)**

---

## 📋 Table of Contents

1. [Project Overview](#-project-overview)
2. [Team](#-team)
3. [Project Structure](#-project-structure)
4. [Data Sources](#-data-sources)
5. [Data Science Pipeline](#-data-science-pipeline)
6. [Machine Learning Model](#-machine-learning-model)
7. [Web Application](#-web-application)
8. [Technologies](#-technologies)
9. [Installation & Setup](#-installation--setup)
10. [Results](#-results)
11. [Limitations & Perspectives](#-limitations--perspectives)

---

## 🎯 Project Overview

**IA-NAHA** (Nutrition & Health AI) is a full-stack data science project built as part of the *Science des Données 4* module at the Université de Montpellier (L3 MIASHS).

The central question guiding this project is:

> *How can artificial intelligence use daily life data and biometric constants to predict the recovery (sleep) needs of the general public?*

The project delivers two complementary outputs:

- **A predictive Machine Learning model** - a Linear Regression trained on 2 011 active profiles to predict the required number of sleep hours based on physical activity data and biometric variables.
- **A personalized nutrition web application** - a platform powered by Gemini 2.5 Flash AI that generates custom 1-to-14-day meal plans based on the ANSES CIQUAL database (2 976 foods), the user's BMR, goals, and dietary restrictions.

Both components are integrated into a single web interface accessible to any user without technical knowledge.

---

## 👥 Team

| Name | Role |
|---|---|
| **Noah CHAYRIGUES** | Data Science |
| **Arthur FESCHET** | Data Science |
| **Haitham ALFAKHRY** | Data Science |
| **Yann BROWNE** | Data Science |

---

## 📁 Project Structure

```
IA-NAHA/
│
├── Application/                        # Web application (PHP + HTML/CSS/JS)
│   ├── api/
│   │   ├── config.php                  # DB config, JSON helpers, Bearer auth
│   │   ├── login.php                   # Authentication + session token
│   │   ├── register.php                # User registration
│   │   ├── save_plan.php               # Save nutritional plan
│   │   ├── save_profile.php            # Update user profile
│   │   ├── get_plans.php               # Retrieve plans
│   │   ├── get_user.php                # User data
│   │   ├── get_stats.php               # Statistics for live DataViz
│   │   ├── delete_plan.php             # Delete plan
│   │   ├── gemini.php                  # Gemini AI proxy (server-side key)
│   │   ├── ciqual.php                  # CIQUAL database access
│   │   ├── predict_sleep.php           # ML server call → sleep prediction
│   │   └── ml_server.py                # Flask server (port 5050) - ML model
         
│   │
│   ├── assets/css/                     # Stylesheets
│   ├── index.html                      # Landing page
│   ├── login.html                      # Login / Registration
│   ├── onboarding.html                 # User profile form
│   ├── generate.html                   # Plan generation + save
│   ├── dashboard.html                  # Personal dashboard
│   ├── dataviz.html                    # Analyses & Visualizations
    └── README.md
│
├── data/
│   ├── Raw/                            # Original raw datasets
│   │   ├── activite_globale.csv        # Gym Members Exercise Tracking (n=3 000)
│   │   ├── Sleep_health_and_lifestyle_dataset.csv
│   │   ├── sommeil_logs.csv            #table utilisé pour comparaison
│   │   ├── Sport.csv                   # PA Compendium
│   │   └── Table_ciqual.csv            # ANSES CIQUAL (2 976 foods)
│   │
│   └── Cleaned/                        # Cleaned and filtered datasets
│       ├── activite_uniquement_sportifs.csv   # 2 011 active profiles
│       ├── sommeil_uniquement_sportifs.csv    # 1 196 sleep profiles
│       ├── ciqual_nutrition.csv
│       └── compendium_sports.csv
│
├── database/
│   ├── ia-naha.sql                     # Full MySQL dump (schema + data)
│   └── MCD.png                         # Conceptual Data Model
│
├── modeles/                            # Exported ML models (scikit-learn)
│   ├── modele_machine_learning.joblib  # Trained LinearRegression
│   └── scaler_machine_learning.joblib  # Fitted StandardScaler
│
├── Notebook/
│   ├── Notebook_DataViz.ipynb          # Exploratory analysis & visualizations
│   └── IA_NAHA_ML_Notebook_FINAL.ipynb # Model training & evaluation
│   └── Rapport Machine Learning # Rapport clair de ML
│
│
├── Orange_results/                     # Orange Data Mining results
│   ├── Orange_Modèle.ows
│   ├── Importance_Variable.png
│   ├── Regression.png
│   ├── Resultat_Modèle.png
│   └── Orange_Global.png
│
├── .gitignore
└── README.md
```

---

## 📊 Data Sources & Engineering

All of our models and features rely on rigorous data cleaning and filtering. Here is the detailed lifecycle of every file, from the `Raw/` folder to the `Cleaned/` directory:

### 1. Activity Data (The ML Engine)
* **`Raw/activite_globale.csv`**: The original *Gym Members Exercise Tracking* dataset (Kaggle) containing 3,000 profiles with their physical effort and biometric variables.
* **`Cleaned/activite_uniquement_sportifs.csv`**: The final file after applying a strict filter (≥ 8,000 steps/day or sessions ≥ 30 min). This file containing **2,011 active profiles** is the core of our project: it was used to train our final Machine Learning model.

### 2. Sleep Data (The Comparison Base)
* **`Raw/Sleep_health_and_lifestyle_dataset.csv`**: The original raw dataset from Kaggle concerning sleep habits and lifestyle.
* **`Raw/sommeil_logs.csv`**: An intermediate table extracted from the previous dataset, specifically formatted so it could be compared alongside our activity data.
* **`Cleaned/sommeil_uniquement_sportifs.csv`**: The final file of **1,196 profiles**, obtained after applying the exact same activity filter (≥ 8,000 steps). **Decision:** This base was ultimately rejected from our final AI pipeline because its analysis revealed a Data Leakage issue and synthetic correlation patterns (-0.98).

### 3. Nutritional Data (The Food Intelligence)
* **`Raw/Table_ciqual.csv`**: The raw official ANSES database containing macronutrients for 2,976 foods.
* **`Cleaned/ciqual_nutrition.csv`**: The cleaned table, formatted and imported into our MySQL database. The Gemini AI exclusively queries this clean file to generate its meal plans, thereby preventing any algorithmic hallucinations.

### 4. Metabolic Data (The Sports Reference)
* **`Raw/Sport.csv`**: The raw file extracted from the *2024 Adult Compendium of Physical Activities* (the scientific reference for MET values).
* **`Cleaned/compendium_sports.csv`**: The cleaned file integrated into our system. It was crucial for auditing our other databases and accurately recalculating the theoretical caloric expenditure for each user, fixing the common overestimations made by smartwatches.
---

## 🔬 Data Science Pipeline

### Exploratory Data Analysis (`Notebook_DataViz.ipynb`)

The EDA notebook follows a structured approach:

1. **Population audit** - Initial exploration of the raw global base (3 000 profiles)
2. **Filtering justification** - Identification of sedentary profiles as confounding factors
3. **Univariate analysis** - Distribution of `sleep_hours` (normal distribution, mean = 7.10h)
4. **Bivariate analyses:**
   - `stress_level` vs `sleep_hours` → r = **-0.413** (dominant predictor)
   - `daily_steps` vs `sleep_hours` → r = -0.011 (no relationship)
   - `duration_minutes` vs `sleep_hours` → r = 0.004 (no relationship)
   - `bmi` vs `sleep_hours` → r = -0.019 (no relationship)
   - `age` vs `sleep_hours` → r = -0.027 (negligible)
   - `blood_pressure_systolic` vs `sleep_hours` → r = 0.032 (excluded: weak signal + requires medical equipment)
5. **Global correlation matrix** - 7 numerical ML variables validated (no multicollinearity > 0.8)
6. **Secondary base analysis** - Detection of synthetic patterns in sleep dataset
7. **Feature selection** - Final 10 features identified for ML

**Key finding:** Physical activity volume alone does not predict sleep. The **psychological stress level** is the single most significant predictor in this dataset.

---

## 🤖 Machine Learning Model

### Model: Linear Regression (scikit-learn)

**Why Linear Regression?**  
Although Gradient Boosting achieved very similar scores, Linear Regression was retained for its **interpretability**. In a health recommendation tool, every prediction must be explainable. The model's coefficients directly indicate which variable is driving the recommendation - essential for user trust.

### Features (10 variables)

| Variable | Type | Justification |
|---|---|---|
| `stress_level` | Numerical | Strongest predictor (r = -0.41) |
| `age` | Numerical | Retained for cross-variable interactions |
| `bmi` | Numerical | Basic morphological profile |
| `duration_minutes` | Numerical | Session effort quantification |
| `daily_steps` | Numerical | Daily activity volume |
| `hydration_level` | Numerical | Lifestyle factor |
| `gender` | Categorical (encoded) | Demographic profile |
| `intensity` | Categorical (encoded) | Effort quality (Low/Medium/High) |
| `activity_type` | Categorical (encoded) | Sport discipline |
| `smoking_status` | Categorical (encoded) | Behavioral factor |

**Excluded variables:**
- `blood_pressure` - r = 0.032 (no signal) + requires a tensiometer
- `quality_of_sleep` - **Data Leakage** (unknown before the night)

### Validation Pipeline

```
80/20 Split → K-Fold (K=5) → Leave-One-Out (200 profiles)
```

| Metric | Value |
|---|---|
| **R² (80/20)** | 0.142 |
| **RMSE (80/20)** | 0.85h (~51 min) |
| **R² (K-Fold mean)** | ~0.14 |
| **RMSE (LOO)** | 0.951h (~57 min) |

### Feature Importance (absolute coefficients after StandardScaler)

| Feature | Coefficient |
|---|---|
| `stress_level` | **0.407** |
| `hydration_level` | 0.039 |
| `activity_type` | 0.028 |
| `age` | 0.027 |
| `bmi` | 0.026 |
| `intensity` | 0.024 |
| `duration_minutes` | 0.018 |
| `gender` | 0.016 |
| `daily_steps` | 0.010 |
| `smoking_status` | 0.003 |

### Coherence Benchmark

To validate that R² = 0.14 reflects the **complexity of sleep as a variable** and not a flaw in our pipeline, the same pipeline was applied to predict 5 different variables:

| Target variable | Best R² | Conclusion |
|---|---|---|
| `avg_heart_rate` | **0.80** | Pipeline is functional ✅ |
| `calories_burned` | 0.36 | Moderate - depends on morphology |
| `bmi` | ~0.00 | Determined by diet, not activity |
| `sleep_hours` | **0.14** | Scientific limit, not technical ✅ |
| `stress_level` | -0.07 | Psychological, not measurable by sensor |

**Conclusion:** R² = 0.80 on heart rate with the same data proves the pipeline is correct. Sleep is inherently difficult to predict from physical activity sensors alone.

### Inference Example

*Profile: Male, 28 years old, BMI 22.5, 50 min moderate intensity session, 10 500 steps/day, stress 4/10, non-smoker.*

**→ Predicted sleep: 7h28**

---

## 🌐 Web Application

The application is structured around 6 pages:

| Page | Description |
|---|---|
| `index.html` | Public landing page with features and onboarding CTA |
| `login.html` | Login / Registration (JWT-based auth via PHP API) |
| `onboarding.html` | Step-by-step profile form (age, weight, goals, restrictions) |
| `generate.html` | AI-powered meal plan generation via Gemini 2.5 Flash + CIQUAL |
| `dashboard.html` | Personal dashboard - saved plans, macros, history |
| `dataviz.html` | Data analysis page - EDA results + ML model explanations |

---

## 🛠️ Technologies

### Data Science
| Technology | Version | Use |
|---|---|---|
| Python | 3.11+ | Core language |
| pandas | latest | Data manipulation |
| scikit-learn | latest | ML pipeline, models, metrics |
| scipy | latest | Pearson correlations |
| matplotlib / seaborn | latest | Visualizations |
| Flask | latest | ML model serving (port 5050) |
| joblib | latest | Model serialization |
| Jupyter Notebook | latest | EDA + ML notebooks |
| Orange Data Mining | latest | Visual ML workflow |

### Web Application
| Technology | Use |
|---|---|
| PHP 8+ | REST API backend |
| MySQL | Database (users, plans, CIQUAL) |
| HTML5 / CSS3 / JavaScript | Frontend |
| Chart.js 4.4 | Interactive data visualizations |
| Gemini 2.5 Flash API | AI meal plan generation |
| MAMP | Local development server |
| Montserrat (Google Fonts) | Typography |
| InfinityFree | Production Web Hosting (PHP/MySQL) |

---

## ⚙️ Installation & Deployment

### Prerequisites
- MAMP (or any PHP/MySQL local server for development)
- Python 3.11+ and pip
- An InfinityFree account (for web deployment)

### 1. Open the application

```
https://ianaha.rf.gd/IA-NAHA/Application/index.html
```

### 6. Run the Notebooks (optional)

```bash
pip install jupyter pandas numpy matplotlib seaborn scikit-learn scipy
cd Notebook/
jupyter notebook
```

---

## 📈 Results

### ML Performance Summary

```
Dataset:        3 000 profiles → 2 011 active (after filtering)
Target:         sleep_hours (continuous, regression)
Best model:     Linear Regression (interpretability + performance)

R² (80/20):     0.142
RMSE (80/20):   0.848h  (~51 minutes)
RMSE (LOO):     0.951h  (~57 minutes)
Features:       10 variables
```

### Key Findings

1. **Stress dominates** - `stress_level` has an absolute coefficient of 0.407, far above all other variables. This is the single most important predictor of sleep in this dataset.
2. **Physical volume is irrelevant in isolation** - steps and session duration show near-zero correlation with sleep when considered alone.
3. **The pipeline is valid** - R² = 0.80 on `avg_heart_rate` with the same features confirms data quality. The weak performance on sleep is a scientific limitation, not a technical failure.
4. **Sleep data was synthetic** - The Kaggle sleep dataset was rejected after detecting mathematically perfect correlations (r = -0.98) inconsistent with real human behavior.

---

## ⚠️ Limitations & Perspectives

### Current Limitations

- R² of 0.14 reflects the **inherent complexity of sleep prediction** from physical activity sensors alone. Many determinants (caffeine, screen exposure, ambient noise, mental load) are invisible to wearables.
- The LOO was run on 200 profiles for computational reasons (full run would cover 2 011 profiles).
- The sleep dataset was identified as likely synthetic - real clinical data would improve validation.
- We are L3 students, not healthcare professionals. IA-NAHA is a wellness guide, not a medical device.

### Future Work (IA-NAHA v2.0)

- Integrate **blue light exposure** data (smartphone usage before bedtime)
- Add **sleep schedule regularity** (consistent bedtime/wake-up times)
- Include **nutritional evening data** via the CIQUAL module (caffeine, alcohol)
- Add **ambient sensors** (temperature, noise level)
- Deploy ML model to a production server for real-time predictions

---

## 📄 License

This project was developed for academic purposes as part of the L3 MIASHS curriculum at Université de Montpellier. All data sources are publicly available (Kaggle, ANSES CIQUAL, PA Compendium).

---

*IA-NAHA · L3 MIASHS · Université de Montpellier · 2025–2026*
# 🌿 IA-NAHA - Intelligence Nutritionnelle & Prédiction de la Récupération

> **L3 MIASHS - Science des Données 4 | Université de Montpellier - 2025–2026**  
> Encadrement : Sandra Bringay · Namrata Patel · Théodore Michel-Picque

---

## 🔗 Démonstration en ligne

> 🌐 **[ia-naha.fr](https://ianaha.rf.gd/IA-NAHA/Application/index.html)** *(remplacer par l'URL de production)*

---

## 📋 Table des matières

1. [Présentation du projet](#-présentation-du-projet)
2. [Équipe](#-équipe)
3. [Structure du projet](#-structure-du-projet)
4. [Sources de données](#-sources-de-données)
5. [Pipeline Data Science](#-pipeline-data-science)
6. [Modèle Machine Learning](#-modèle-machine-learning)
7. [Application Web](#-application-web)
8. [Technologies](#-technologies)
9. [Installation & Lancement](#-installation--lancement)
10. [Résultats](#-résultats)
11. [Limites & Perspectives](#-limites--perspectives)

---

## 🎯 Présentation du projet

**IA-NAHA** (Nutrition & Health AI) est un projet de data science full-stack développé dans le cadre du module *Science des Données 4* de la Licence 3 MIASHS à l'Université de Montpellier.

La question centrale qui guide ce projet est la suivante :

> *Comment l'IA peut-elle utiliser les données de vie quotidienne et les constantes biométriques pour prédire les besoins de récupération (sommeil) du grand public ?*

Le projet produit deux livrables complémentaires :

- **Un modèle prédictif de Machine Learning** - une Régression Linéaire entraînée sur 2 011 profils actifs, capable d'estimer le nombre d'heures de sommeil nécessaires à partir de données d'activité physique et de variables biométriques.
- **Une application web de nutrition personnalisée** - une plateforme propulsée par l'IA Gemini 2.5 Flash qui génère des plans alimentaires sur mesure (1 à 14 jours) à partir de la base officielle ANSES CIQUAL (2 976 aliments), du métabolisme de base de l'utilisateur, de ses objectifs et de ses restrictions alimentaires.

Les deux composants sont intégrés dans une interface web unique, accessible sans aucune compétence technique préalable.

---

## 👥 Équipe

| Nom | Rôle |
|---|---|
| **Noah CHAYRIGUES** | Data Science |
| **Arthur FESCHET** | Data Science |
| **Haitham ALFAKHRY** | Data Science |
| **Yann BROWNE** | Data Science |

---

## 📁 Structure du projet

```
IA-NAHA/
│
├── Application/                        # Application web (PHP + HTML/CSS/JS)
│   ├── api/
│   │   ├── config.php                  # Config BDD, helpers JSON, auth Bearer
│   │   ├── login.php                   # Authentification + token de session
│   │   ├── register.php                # Inscription utilisateur
│   │   ├── save_plan.php               # Sauvegarde plan nutritionnel
│   │   ├── save_profile.php            # Mise à jour profil utilisateur
│   │   ├── get_plans.php               # Récupération des plans
│   │   ├── get_user.php                # Données utilisateur
│   │   ├── get_stats.php               # Statistiques pour DataViz live
│   │   ├── delete_plan.php             # Suppression d'un plan
│   │   ├── gemini.php                  # Proxy Gemini AI (clé côté serveur)
│   │   ├── ciqual.php                  # Accès à la base CIQUAL via BDD
│   │   ├── predict_sleep.php           # Appel ML server → prédiction sommeil
│   │   └── ml_server.py                # Serveur Flask (port 5050) - modèle ML
│   │
│   ├── assets/css/                     # Feuilles de style
│   ├── index.html                      # Page d'accueil
│   ├── login.html                      # Connexion / Inscription
│   ├── onboarding.html                 # Formulaire profil utilisateur
│   ├── generate.html                   # Génération du plan + sauvegarde
│   ├── dashboard.html                  # Dashboard personnel
│   └── dataviz.html                    # Analyses & Visualisations
│
├── data/
│   ├── Raw/                            # Données brutes originales
│   │   ├── activite_globale.csv        # Gym Members Exercise Tracking (n=3 000)
│   │   ├── Sleep_health_and_lifestyle_dataset.csv
│   │   ├── sommeil_logs.csv
│   │   ├── Sport.csv                   # PA Compendium
│   │   └── Table_ciqual.csv            # ANSES CIQUAL (2 976 aliments)
│   │
│   └── Cleaned/                        # Données nettoyées et filtrées
│       ├── activite_uniquement_sportifs.csv   # 2 011 profils actifs
│       ├── sommeil_uniquement_sportifs.csv    # 1 196 profils sommeil sportifs
│       ├── ciqual_nutrition.csv
│       └── compendium_sports.csv
│
├── database/
│   ├── ia-naha.sql                     # Dump MySQL complet (schéma + données)
│   └── MCD.png                         # Modèle Conceptuel de Données
│   └── Rapport Machine Learning        # Rapport clair de ML
│
├── modeles/                            # Modèles ML exportés (scikit-learn)
│   ├── modele_machine_learning.joblib  # LinearRegression entraîné
│   └── scaler_machine_learning.joblib  # StandardScaler ajusté
│
├── Notebook/
│   ├── Notebook_DataViz.ipynb          # Analyse exploratoire & visualisations
│   └── IA_NAHA_ML_Notebook_FINAL.ipynb # Entraînement & évaluation du modèle ML
│
├── Orange_results/                     # Résultats Orange Data Mining
│   ├── Orange_Modèle.ows
│   ├── Importance_Variable.png
│   ├── Regression.png
│   ├── Resultat_Modèle.png
│   └── Orange_Global.png
│
├── .gitignore
└── README.md
```

---

## 📊 Sources de données
## 📊 Sources de données & Ingénierie (Data Engineering)

L'intégralité de nos modèles et de nos fonctionnalités repose sur un travail rigoureux de nettoyage et de filtrage. Voici l'explication détaillée du cycle de vie de chaque fichier, du dossier `Raw/` (données brutes) au dossier `Cleaned/` (données exploitables) :

### 1. Données d'Activité (Le Moteur ML)
* **`Raw/activite_globale.csv`** : provient du Dataset original *Gym Members Exercise Tracking* (Kaggle) contenant 3 000 profils avec leurs variables d'effort physique et biométriques.
* **`Cleaned/activite_uniquement_sportifs.csv`** : Fichier final après application d'un filtre strict (≥ 8 000 pas/jour ou séance ≥ 30 min). Ce fichier de **2 011 profils** est le cœur de notre projet : il a servi à entraîner notre modèle de Machine Learning final.

### 2. Données de Sommeil (La Base de Comparaison)
* **`Raw/Sleep_health_and_lifestyle_dataset.csv`** : Dataset brut d'origine issu de Kaggle concernant les habitudes de sommeil.
* **`Raw/sommeil_logs.csv`** : Table intermédiaire extraite du dataset précédent, formatée spécifiquement pour pouvoir être comparée à nos données d'activité.
* **`Cleaned/sommeil_uniquement_sportifs.csv`** : Fichier final de **1 196 profils**, obtenu après avoir appliqué le même filtre d'activité (≥ 8 000 pas). **Décision :** Cette base a été rejetée de notre IA finale, car son analyse a révélé une fuite de données (*Data Leakage*) et des modèles de corrélation synthétiques (-0.98).

### 3. Données Nutritionnelles (L'Intelligence Alimentaire)
* **`Raw/Table_ciqual.csv`** : Base de données brute officielle de l'ANSES contenant l'ensemble des macros pour 2 976 aliments.
* **`Cleaned/ciqual_nutrition.csv`** : Table nettoyée, formatée et importée dans notre base de données MySQL. C'est dans ce fichier exclusif que l'IA Gemini vient puiser pour générer ses plans alimentaires, évitant ainsi toute hallucination algorithmique.

### 4. Données Métaboliques (Le Référentiel Sportif)
* **`Raw/Sport.csv`** : Fichier brut issu du *2024 Adult Compendium of Physical Activities* (référentiel scientifique des valeurs MET).
* **`Cleaned/compendium_sports.csv`** : Fichier nettoyé et intégré à notre système. Il nous a été indispensable pour auditer nos autres bases et recalculer la dépense calorique théorique exacte de chaque utilisateur, palliant ainsi les surestimations courantes des montres connectées.
---

## 🔬 Pipeline Data Science

### Analyse Exploratoire des Données (`Notebook_DataViz.ipynb`)

Le notebook EDA suit une approche structurée en 15 sections :

1. **Audit de la population** - Exploration de la base globale brute (3 000 profils)
2. **Justification du filtrage** - Identification des profils sédentaires comme facteurs de confusion
3. **Analyse univariée** - Distribution de `sleep_hours` (distribution normale, moyenne = 7,10h)
4. **Analyses bivariées :**
   - `stress_level` vs `sleep_hours` → r = **-0,413** (prédicteur dominant)
   - `daily_steps` vs `sleep_hours` → r = -0,011 (aucune relation)
   - `duration_minutes` vs `sleep_hours` → r = 0,004 (aucune relation)
   - `bmi` vs `sleep_hours` → r = -0,019 (aucune relation)
   - `age` vs `sleep_hours` → r = -0,027 (négligeable)
   - `blood_pressure_systolic` vs `sleep_hours` → r = 0,032 (exclu : signal faible + nécessite tensiomètre)
5. **Matrice de corrélation globale** - 7 variables numériques ML validées (pas de multicolinéarité > 0,8)
6. **Analyse de la base secondaire** - Détection de patterns synthétiques dans le dataset sommeil
7. **Sélection des features** - 10 variables finales identifiées pour le ML

**Enseignement clé :** Le volume d'activité physique seul ne prédit pas le sommeil. Le **niveau de stress psychologique** est le seul prédicteur significatif dans ce dataset.

---

## 🤖 Modèle Machine Learning

### Modèle retenu : Régression Linéaire (scikit-learn)

**Pourquoi la Régression Linéaire ?**  
Bien que le Gradient Boosting ait obtenu des scores très proches, la Régression Linéaire a été retenue pour son **interprétabilité**. Dans un outil de recommandation santé, chaque prédiction doit être explicable. Les coefficients du modèle indiquent directement quelle variable oriente la recommandation - essentiel pour la confiance de l'utilisateur.

### Variables (10 features)

| Variable | Type | Justification |
|---|---|---|
| `stress_level` | Numérique | Prédicteur le plus fort (r = -0,41) |
| `age` | Numérique | Conservé pour les interactions croisées |
| `bmi` | Numérique | Profil morphologique de base |
| `duration_minutes` | Numérique | Quantification de l'effort de séance |
| `daily_steps` | Numérique | Volume d'activité quotidien |
| `hydration_level` | Numérique | Facteur de mode de vie |
| `gender` | Catégorielle (encodée) | Profil démographique |
| `intensity` | Catégorielle (encodée) | Qualité de l'effort (Low/Medium/High) |
| `activity_type` | Catégorielle (encodée) | Discipline sportive |
| `smoking_status` | Catégorielle (encodée) | Facteur comportemental |

**Variables exclues :**
- `blood_pressure` - r = 0,032 (signal nul) + nécessite un tensiomètre
- `quality_of_sleep` - **Data Leakage** (information inconnue avant la nuit)

### Pipeline de validation

```
Découpage 80/20 → K-Fold (K=5) → Leave-One-Out (200 profils)
```

| Métrique | Valeur |
|---|---|
| **R² (80/20)** | 0,142 |
| **RMSE (80/20)** | 0,85h (~51 min) |
| **R² (K-Fold moyen)** | ~0,14 |
| **RMSE (LOO)** | 0,951h (~57 min) |

### Importance des variables (coefficients absolus après StandardScaler)

| Variable | Coefficient |
|---|---|
| `stress_level` | **0,407** |
| `hydration_level` | 0,039 |
| `activity_type` | 0,028 |
| `age` | 0,027 |
| `bmi` | 0,026 |
| `intensity` | 0,024 |
| `duration_minutes` | 0,018 |
| `gender` | 0,016 |
| `daily_steps` | 0,010 |
| `smoking_status` | 0,003 |

### Benchmark de cohérence (preuve par l'absurde)

Pour valider que R² = 0,14 reflète la **complexité du sommeil comme variable** et non un défaut de notre pipeline, le même pipeline a été appliqué à la prédiction de 5 variables différentes :

| Variable cible | Meilleur R² | Conclusion |
|---|---|---|
| `avg_heart_rate` | **0,80** | Pipeline fonctionnel  |
| `calories_burned` | 0,36 | Modéré - dépend de la morphologie |
| `bmi` | ~0,00 | Déterminé par l'alimentation, pas l'activité |
| `sleep_hours` | **0,14** | Limite scientifique (complexité du sommeil) |
| `stress_level` | -0,07 | Psychologique, non mesurable par capteur |

**Conclusion :** R² = 0,80 sur le rythme cardiaque avec les mêmes données prouve que le pipeline est correct. Le sommeil est intrinsèquement difficile à prédire depuis les seuls capteurs d'activité physique.

### Exemple d'inférence

*Profil : Homme, 28 ans, IMC 22,5, séance de 50 min intensité moyenne, 10 500 pas/jour, stress 4/10, non-fumeur.*

**→ Sommeil prédit : 7h28**

---

## 🌐 Application Web

L'application est structurée autour de 6 pages :

| Page | Description |
|---|---|
| `index.html` | Page d'accueil publique avec présentation et CTA |
| `login.html` | Connexion / Inscription (auth JWT via API PHP) |
| `onboarding.html` | Formulaire profil pas-à-pas (âge, poids, objectifs, restrictions) |
| `generate.html` | Génération de plans alimentaires via Gemini 2.5 Flash + CIQUAL |
| `dashboard.html` | Dashboard personnel - plans sauvegardés, macros, historique |
| `dataviz.html` | Page d'analyses - résultats EDA + explication du modèle ML |

---

## 🛠️ Technologies

### Data Science
| Technologie | Version | Utilisation |
|---|---|---|
| Python | 3.11+ | Langage principal |
| pandas | latest | Manipulation des données |
| scikit-learn | latest | Pipeline ML, modèles, métriques |
| scipy | latest | Corrélations de Pearson |
| matplotlib / seaborn | latest | Visualisations |
| Flask | latest | Serveur ML (port 5050) |
| joblib | latest | Sérialisation des modèles |
| Jupyter Notebook | latest | Notebooks EDA + ML |
| Orange Data Mining | latest | Workflow ML visuel |

### Application Web
| Technologie | Utilisation |
|---|---|
| PHP 8+ | API REST backend |
| MySQL | Base de données (utilisateurs, plans, CIQUAL) |
| HTML5 / CSS3 / JavaScript | Frontend |
| Chart.js 4.4 | Visualisations interactives |
| Gemini 2.5 Flash API | Génération IA des plans nutritionnels |
| MAMP | Serveur local de développement |
| Montserrat (Google Fonts) | Typographie |
| InfinityFree | Hébergement Web de production (PHP/MySQL) |

---

## ⚙️ Installation & Déploiement

### Prérequis
- MAMP (ou tout serveur PHP/MySQL local pour le développement)
- Python 3.11+ et pip
- Un compte InfinityFree (pour le déploiement en ligne)


---

Tuto pour essayer en local, cependant la version en ligne est plus simple d'accès
### 1. Cloner le dépôt

```bash
cd C:/MAMP/htdocs/
git clone https://github.com/VOTRE_USERNAME/IA-NAHA.git
```

### 2. Importer la base de données

Ouvrir phpMyAdmin à `http://localhost:8888/phpMyAdmin/`, créer une base nommée `ia_naha`, puis importer :

```
database/ia-naha.sql
```

### 3. Configurer l'API

Éditer `Application/api/config.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ia_naha');
define('DB_USER', 'root');
define('DB_PASS', 'root');
```

### 4. Démarrer le serveur ML

```bash
pip install flask scikit-learn joblib pandas numpy
cd Application/api/
python ml_server.py
# → Flask en écoute sur http://localhost:5050
```

### 5. Ouvrir l'application

```
https://ianaha.rf.gd/IA-NAHA/Application/index.html
```

### 6. Lancer les Notebooks (optionnel)

```bash
pip install jupyter pandas numpy matplotlib seaborn scikit-learn scipy
cd Notebook/
jupyter notebook
```

---

## 📈 Résultats

### Résumé des performances ML

```
Dataset :       3 000 profils → 2 011 actifs (après filtrage)
Cible :         sleep_hours (continue, régression)
Meilleur modèle : Régression Linéaire (interprétabilité + performance)

R² (80/20) :    0,142
RMSE (80/20) :  0,848h  (~51 minutes)
RMSE (LOO) :    0,951h  (~57 minutes)
Features :      10 variables
```

### Enseignements clés

1. **Le stress domine** - `stress_level` a un coefficient absolu de 0,407, très largement au-dessus de toutes les autres variables. C'est le prédicteur le plus important du sommeil dans ce dataset.
2. **Le volume physique est insuffisant seul** - les pas quotidiens et la durée de séance montrent des corrélations proches de zéro avec le sommeil considérés isolément.
3. **Le pipeline est valide** - R² = 0,80 sur `avg_heart_rate` avec les mêmes features confirme la qualité des données. La faible performance sur le sommeil est une limite scientifique, pas technique.
4. **Le dataset sommeil était synthétique** - le dataset Kaggle sommeil a été rejeté après détection de corrélations mathématiquement parfaites (r = -0,98) incohérentes avec un comportement humain réel.

---

## ⚠️ Limites & Perspectives

### Limites actuelles

- Le R² de 0,14 reflète la **complexité inhérente de la prédiction du sommeil** depuis des capteurs d'activité physique seuls. De nombreux déterminants (caféine, exposition aux écrans, bruit ambiant, charge mentale) sont invisibles aux montres connectées.
- Le LOO a été lancé sur 200 profils pour des raisons de temps de calcul (l'exécution complète couvrirait 2 011 profils).
- Le dataset sommeil a été identifié comme probablement synthétique des données cliniques réelles amélioreraient la validation.
- Nous sommes des étudiants de L3, pas des professionnels de santé, IA-NAHA est un guide de bien-être, pas un dispositif médical.

### Pistes d'amélioration (IA-NAHA v2.0)

- Intégrer les données d'**exposition à la lumière bleue** (usage du smartphone avant le coucher)
- Ajouter la **régularité des horaires de coucher/lever** (chronobiologie)
- Inclure les **données nutritionnelles du soir** via le module CIQUAL (caféine, alcool)
- Ajouter des **capteurs ambiants** (température, niveau sonore)
- Déployer le modèle ML sur un serveur de production pour des prédictions en temps réel

---

## 📄 Licence

Ce projet a été développé dans un cadre académique dans le cadre du cursus L3 MIASHS à l'Université de Montpellier. Toutes les sources de données sont publiquement disponibles (Kaggle, ANSES CIQUAL, PA Compendium).

---

*IA-NAHA · L3 MIASHS · Université de Montpellier · 2025–2026*
