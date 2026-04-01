# IA-NAHA — Application de planification nutritionnelle personnalisée

IA-NAHA est une application web qui génère des plans nutritionnels personnalisés grâce à l'intelligence artificielle (Google Gemini). Elle collecte le profil de l'utilisateur via un questionnaire, calcule ses besoins caloriques et en macronutriments, puis produit un plan de repas détaillé jour par jour.

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Frontend | HTML5, CSS3, JavaScript vanilla |
| Backend | PHP 7.4+ avec PDO |
| Base de données | MySQL 8.0+ |
| IA | Google Gemini 2.5 Flash |
| Environnement local | MAMP (Apache + MySQL + PHP) |
| Graphiques | Chart.js |
| Fonts | Google Fonts (Cormorant Garamond, DM Mono) |

---

## Prérequis

- **MAMP** installé et démarré (Apache sur le port `8888`, MySQL sur le port `8889`)
- Base de données MySQL nommée `ia-naha`
- PHP 7.4 minimum
- Une clé API Google Gemini

---

## Installation

### 1. Placer le projet

Placer le dossier dans le répertoire `htdocs` de MAMP :

```
/Applications/MAMP/htdocs/SD4/IA-NAHA/Application/
```

### 2. Créer la base de données

Se connecter à MySQL (via phpMyAdmin ou terminal) et créer la base :

```sql
CREATE DATABASE `ia-naha` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Configurer la base de données

Éditer `api/config.php` :

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '8889');       // Port MAMP par défaut
define('DB_NAME', 'ia-naha');
define('DB_USER', 'root');
define('DB_PASS', 'root');
```

### 4. Configurer la clé API Gemini

Éditer `api/gemini.php` et remplacer la clé API :

```php
define('GEMINI_API_KEY', 'VOTRE_CLE_API_ICI');
```

### 5. Lancer l'application

Ouvrir dans le navigateur :

```
http://localhost:8888/SD4/IA-NAHA/Application/
```

---

## Structure du projet

```
Application/
├── index.html          # Page d'accueil (landing page)
├── login.html          # Connexion et inscription
├── onboarding.html     # Questionnaire en 9 étapes
├── generate.html       # Génération et affichage du plan IA
├── dashboard.html      # Tableau de bord utilisateur
│
└── api/
    ├── config.php       # Configuration BDD + fonctions utilitaires
    ├── login.php        # Authentification utilisateur
    ├── register.php     # Inscription
    ├── get_user.php     # Récupération du profil
    ├── get_plans.php    # Liste et détail des plans
    ├── save_plan.php    # Sauvegarde d'un plan généré
    ├── save_profile.php # Mise à jour du profil
    ├── delete_plan.php  # Suppression d'un plan
    └── gemini.php       # Proxy vers l'API Google Gemini
```

---

## Parcours utilisateur

```
Page d'accueil (index.html)
    │
    ├─ [Non connecté] → Connexion / Inscription (login.html)
    │                        │
    │                        └─ Questionnaire (onboarding.html)  ← 9 étapes
    │                                 │
    │                                 └─ Génération IA (generate.html)
    │                                          │
    │                                          └─ Tableau de bord (dashboard.html)
    │
    └─ [Connecté] → Tableau de bord (dashboard.html)
                         ├─ Vue d'ensemble + graphiques
                         ├─ Liste des plans sauvegardés
                         ├─ Détail d'un plan (repas par jour)
                         └─ Profil utilisateur
```

---

## Fonctionnalités

### Authentification
- Inscription avec validation email et indicateur de force du mot de passe
- Connexion avec option "Se souvenir de moi"
- Gestion de session via `localStorage` (`naha_user_id`)

### Questionnaire d'onboarding (9 étapes)
1. Prénom
2. Âge
3. Sexe
4. Poids (kg)
5. Taille (cm)
6. Niveau d'activité (5 niveaux : sédentaire → athlète)
7. Objectifs (multi-choix : perte de poids, prise de masse, endurance, etc.)
8. Restrictions alimentaires (sans gluten, halal, vegan, allergies personnalisées, etc.)
9. Configuration du plan (durée 1/3/7/14 jours, nombre de repas par jour)

### Génération IA
- Calcul du BMR (métabolisme de base) selon âge, poids, taille, sexe et activité
- Calcul des macros cibles (protéines, glucides, lipides)
- Génération du plan via Google Gemini 2.5 Flash
- Base alimentaire officielle CIQUAL ANSES (2 976 aliments)
- Plan détaillé jour par jour avec aliments et grammages

### Tableau de bord
- KPIs : nombre de plans, calories et protéines du dernier plan, jours planifiés au total
- Graphique donut : répartition des macronutriments
- Graphique linéaire : évolution des calories sur les derniers plans
- Grille des plans avec aperçu rapide
- Détail complet de chaque plan (repas, aliments, quantités)
- Suppression de plans

---

## API — Endpoints

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `api/login.php` | POST | Connexion utilisateur |
| `api/register.php` | POST | Inscription |
| `api/get_user.php` | GET | Récupérer le profil (`?user_id=`) |
| `api/get_plans.php` | GET | Lister les plans (`?user_id=`) ou détail (`?plan_id=`) |
| `api/save_plan.php` | POST | Sauvegarder un plan généré |
| `api/save_profile.php` | POST | Mettre à jour le profil |
| `api/delete_plan.php` | POST | Supprimer un plan |
| `api/gemini.php` | POST | Appel proxifié à l'API Gemini |

---

## Sécurité

- Mots de passe hashés avec `bcrypt` (`password_hash` / `password_verify`)
- Requêtes SQL préparées via PDO (protection contre les injections SQL)
- Vérification de propriété avant suppression (un utilisateur ne peut supprimer que ses propres plans)
- Journalisation des tentatives de connexion (IP, succès/échec)

---

## Notes

- Les URLs de l'API sont codées en dur dans les fichiers HTML (`http://localhost:8888/SD4/IA-NAHA/Application/api`). À externaliser avant tout déploiement en production.
- La clé API Gemini dans `api/gemini.php` ne doit pas être committée dans un dépôt public.
- Un `package.json` React est présent mais non utilisé — le frontend est intégralement en JavaScript vanilla.
