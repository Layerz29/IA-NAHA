# IA-NAHA — Documentation Technique

---

## 1. Prérequis

### Serveur local

| Outil | macOS | Windows |
|---|---|---|
| Serveur Apache + PHP | MAMP (port **8888**) | XAMPP ou WAMP (port **80** ou **8080**) |
| MySQL | MAMP (port **8889**) | XAMPP/WAMP (port **3306**) |
| PHP | ≥ 7.2 | ≥ 7.2 |
| Python | 3.8+ (`python3`) | 3.8+ (`python`) |

### Dépendances Python

```bash
pip install flask joblib numpy pandas scikit-learn
```

### Fichier `.env`

Copier `.env.example` → `.env` dans le dossier `Application/` :

```
GEMINI_API_KEY=AIza...votre_clé...

# Optionnel — à renseigner seulement si différent des valeurs par défaut
# DB_PORT=3306     ← XAMPP/WAMP
# DB_PORT=8889     ← MAMP (valeur par défaut)
```

> Clé Gemini : https://aistudio.google.com/app/apikey  
> **Windows** : enregistrer le `.env` avec VS Code ou Notepad++ en **UTF-8 sans BOM**, jamais avec Notepad.

### Base de données

1. Ouvrir phpMyAdmin (`http://localhost:8888/phpMyAdmin` sur MAMP, `http://localhost/phpmyadmin` sur XAMPP)
2. Créer une base nommée `ia-naha`
3. Importer le fichier `database/ia-naha.sql`

### Démarrer le serveur ML

```bash
# macOS
python3 Application/api/ml_server.py

# Windows
python Application/api/ml_server.py
```

Le serveur Flask écoute sur `http://127.0.0.1:5050`. **Il doit tourner en parallèle d'Apache** pour que la prédiction de sommeil fonctionne lors de la génération d'un plan.

---

## 2. Bugs connus et solutions

### Apache bloque le header `Authorization`

**Symptôme :** toutes les requêtes authentifiées retournent `401 Non authentifié` même après connexion.

**Cause :** Apache (MAMP et XAMPP) filtre le header `Authorization: Bearer` avant qu'il n'atteigne PHP.

**Solution :** `Application/api/.htaccess` contient :
```apache
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```
Et le frontend envoie toujours un second header `X-Auth-Token` que PHP lit en fallback.

> Si `.htaccess` ne fonctionne pas : vérifier que `AllowOverride All` est activé dans la config Apache du projet.

---

### Clé Gemini invalide sur Windows

**Symptôme :** la génération échoue avec une erreur Gemini alors que la clé est correcte.

**Cause :** Notepad sauvegarde en CRLF — `parse_ini_file()` lit `AIza...\r` au lieu de `AIza...`.

**Solution appliquée** dans `gemini.php` :
```php
define('GEMINI_API_KEY', trim($_env['GEMINI_API_KEY'] ?? ''));
```

> Toujours éditer le `.env` avec VS Code ou Notepad++ en **UTF-8 sans BOM**.

---

### Port MySQL différent selon l'environnement

**Symptôme :** `Connexion BDD impossible` au lancement.

**Cause :** MAMP utilise le port `8889`, XAMPP/WAMP utilisent `3306`.

**Solution appliquée** dans `config.php` : le port est lu depuis le `.env` si présent, sinon `8889` par défaut.

```
# Dans Application/.env — ajouter si sur XAMPP/WAMP :
DB_PORT=3306
```

---

### CORS bloqué sur certains ports Windows

**Symptôme :** erreurs CORS dans la console navigateur sur Windows.

**Cause :** la liste des origines autorisées ne couvrait pas tous les ports courants.

**Solution appliquée** dans `config.php` :
```php
$_allowed_origins = [
    'http://localhost:8888', 'http://127.0.0.1:8888',  // MAMP macOS
    'http://localhost:8080', 'http://127.0.0.1:8080',  // XAMPP alternatif
    'http://localhost',      'http://127.0.0.1',        // XAMPP/WAMP port 80
];
```

---

### URL API ne fonctionne que sur macOS

**Symptôme :** sur Windows, toutes les requêtes échouent (ERR_CONNECTION_REFUSED).

**Cause :** URL hardcodée à `http://localhost:8888`.

**Solution appliquée** dans tous les fichiers HTML :
```javascript
const API = window.location.origin + '/SD4/IA-NAHA/Application/api';
```

---

### Gemini renvoie du texte au lieu du JSON

**Symptôme :** `JSON.parse` échoue, erreur `"The string did not match the expected pattern"` dans Safari.

**Cause :** Gemini 2.5 Flash en mode "thinking" renvoie plusieurs parts — le premier contient sa réflexion interne, pas le JSON.

**Solution :** `thinkingBudget: 0` désactive ce comportement + le code parcourt les parts à l'envers en fallback.

---

### Le serveur ML ne démarre pas

**Symptôme :** `ModuleNotFoundError` ou `FileNotFoundError`.

**Solutions :**
- Installer les dépendances : `pip install flask joblib numpy pandas scikit-learn`
- Vérifier que les `.joblib` existent dans `/IA-NAHA/modeles/`
- Sur Windows, lancer le terminal en tant qu'administrateur si besoin

---

## 3. Code important commenté

### `config.php` — Connexion BDD (`getPDO`)

```php
function getPDO(): PDO {
    static $pdo = null;
    // Singleton — une seule connexion MySQL par requête PHP
    if ($pdo) return $pdo;

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // exceptions sur erreur SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES   => false,                   // requêtes préparées natives
        ]);
    } catch (PDOException $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Connexion BDD impossible : ' . $e->getMessage()]);
        exit;
    }
    return $pdo;
}
```

---

### `config.php` — Authentification (`requireAuth`)

```php
function requireAuth(): int {
    // Apache/MAMP bloque souvent Authorization — on essaie 4 sources dans l'ordre
    $auth = $_SERVER['HTTP_AUTHORIZATION']
         ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
         ?? '';
    if (!$auth && function_exists('getallheaders')) {
        $h    = array_change_key_case(getallheaders(), CASE_LOWER);
        $auth = $h['authorization'] ?? '';
    }
    $token = trim(str_replace('Bearer', '', $auth));

    // X-Auth-Token : header custom envoyé en parallèle par le frontend,
    // jamais bloqué par Apache car non-standard
    if (!$token) {
        $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
        if (!$token && function_exists('getallheaders')) {
            $h     = array_change_key_case(getallheaders(), CASE_LOWER);
            $token = $h['x-auth-token'] ?? '';
        }
        $token = trim($token);
    }

    if (!$token) {
        ob_clean(); http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']); exit;
    }

    $stmt = getPDO()->prepare('SELECT user_id FROM user_sessions WHERE id = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        ob_clean(); http_response_code(401);
        echo json_encode(['error' => 'Session invalide ou expirée']); exit;
    }

    return (int)$row['user_id'];
}
```

---

### `login.php` — Connexion avec rate limiting

```php
// Bloque une IP après 10 échecs en 15 minutes (anti brute-force)
$stmt = $pdo->prepare('
    SELECT COUNT(*) FROM login_logs
    WHERE ip_address = ? AND success = 0
    AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
');
$stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '']);
if ((int)$stmt->fetchColumn() >= 10) {
    jsonOut(['error' => 'Trop de tentatives. Réessayez dans 15 minutes.'], 429);
}

// Message volontairement identique email inconnu / mauvais mot de passe
// → évite l'énumération de comptes
if (!$user || !password_verify($password, $user['password'])) {
    jsonOut(['error' => 'Email ou mot de passe incorrect.'], 401);
}

// Token = 32 octets aléatoires (CSPRNG) = 64 caractères hex, non-devinable
$token = bin2hex(random_bytes(32));
```

---

### `gemini.php` — Proxy Gemini

```php
// La clé API n'est jamais exposée côté client.
// trim() retire le \r si le .env a été sauvegardé en CRLF (Windows).
define('GEMINI_API_KEY', trim($_env['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: ''));

// thinkingBudget: 0 désactive le mode "thinking" de Gemini 2.5 Flash.
// Sans ça, la réponse contient plusieurs parts dont le premier est la
// réflexion interne du modèle — pas du JSON parseable.
'thinkingConfig' => ['thinkingBudget' => 0]

// Fallback si le thinking est quand même actif :
// on prend le dernier part qui n'est pas un thought
foreach (array_reverse($parts) as $part) {
    if (!($part['thought'] ?? false) && isset($part['text'])) {
        $text = $part['text']; break;
    }
}
```

---

### `save_plan.php` — Encodages pour le modèle ML

```php
// Convertit les valeurs texte du formulaire en entiers.
// Doit rester synchronisé avec ml_server.py.

function encodeIntensity(string $v): int {
    $map = [
        'high'=>0, 'actif'=>0, 'tres_actif'=>0,  // haute intensité
        'low'=>1, 'sedentaire'=>1, 'leger'=>1,    // basse intensité
        'medium'=>2, 'modere'=>2,                 // modérée (défaut)
    ];
    return $map[strtolower($v)] ?? 2;
}

// BMI calculé côté serveur — pas confiance à la valeur client
$bmi = ($poids > 0 && $taille > 0)
    ? round($poids / (($taille / 100) ** 2), 2)
    : null;
```

---

### `ml_server.py` — Prédiction du sommeil

```python
# os.path.join est cross-platform (macOS et Windows)
MODEL_DIR = os.path.join(BASE_DIR, '..', '..', 'modeles')

# Le scaler doit être celui sauvegardé à l'entraînement —
# un scaler différent fausserait complètement la prédiction
x_scaled = scaler.transform(features)
pred     = float(model.predict(x_scaled)[0])

# Clamping : le modèle peut extrapoler hors des bornes pour des profils extrêmes
pred = round(max(4.0, min(12.0, pred)), 1)
```

---

### `generate.html` — Double header d'authentification

```javascript
// Les deux headers sont toujours envoyés ensemble.
// Authorization: Bearer → peut être bloqué par Apache
// X-Auth-Token → jamais bloqué, lu en fallback côté PHP
const authHeaders = {
    'Content-Type':  'application/json',
    'Authorization': `Bearer ${localStorage.getItem('naha_token')}`,
    'X-Auth-Token':   localStorage.getItem('naha_token'),
};
```

---

## 4. Mise en ligne (déploiement)

L'application tourne en production sur deux hébergements séparés :

### Architecture de production

```
Navigateur
  ├─► InfinityFree  (PHP + MySQL)  — ianaha.rf.gd
  └─► Render.com    (Python Flask) — ia-naha.onrender.com
```

---

### Étape 1 — Serveur ML sur Render.com (gratuit)

1. Créer un compte sur [render.com](https://render.com)
2. **New → Web Service** → connecter le repo GitHub `noahchrgs/IA-NAHA`
3. Configurer :

| Champ | Valeur |
|---|---|
| Root directory | *(vide — racine du repo)* |
| Runtime | Python 3 |
| Build command | `pip install -r Application/api/requirements.txt` |
| Start command | `python Application/api/ml_server.py` |

4. URL du service : `https://ia-naha.onrender.com`
5. Vérification : `https://ia-naha.onrender.com/health` → `{"status":"ok"}`

**Points importants :**
- Le Root directory doit être **vide** (pas `Application/api`) pour que `ml_server.py` puisse accéder à `modeles/` via `../../modeles`
- Flask doit écouter sur `0.0.0.0` et non `127.0.0.1` — sinon Render ne détecte pas le port et timeout
- Le plan gratuit Render se met en veille après 15 min — la première requête peut prendre ~30s
- Le timeout dans `predict_sleep.php` est à **35 secondes** pour absorber ce délai de réveil

---

### Étape 2 — PHP + MySQL sur InfinityFree (gratuit)

1. Créer un compte sur [infinityfree.com](https://infinityfree.com)
2. Créer un hébergement → domaine : `ianaha.rf.gd`
3. Dans le panel → **MySQL Databases** : créer une BDD et noter les identifiants
4. Importer `database/ia-naha.sql` via phpMyAdmin
5. Uploader le dossier `IA-NAHA/` via **FileZilla** dans `htdocs/`

---

### Étape 3 — Fichier `.env` sur InfinityFree

Créer `htdocs/IA-NAHA/Application/.env` via FileZilla :

```
GEMINI_API_KEY=ta_clé_gemini

DB_HOST=sql211.infinityfree.com
DB_PORT=3306
DB_NAME=if0_41583765_ia_naha
DB_USER=if0_41583765
DB_PASS=ton_mot_de_passe

ML_SERVER_URL=https://ia-naha.onrender.com
```

---

### Étape 4 — URLs de vérification

| Test | URL |
|---|---|
| Page de connexion | `https://ianaha.rf.gd/IA-NAHA/Application/login.html` |
| Dashboard | `https://ianaha.rf.gd/IA-NAHA/Application/dashboard.html` |
| API PHP | `https://ianaha.rf.gd/IA-NAHA/Application/api/login.php` |
| Serveur ML | `https://ia-naha.onrender.com/health` |

---

### Problèmes rencontrés lors du déploiement

| Problème | Cause | Solution |
|---|---|---|
| `load failed` sur register | URL API hardcodée sur `localhost:8888` | `window.location.origin` + détection locale/prod |
| `Connexion BDD impossible` | `.env` absent ou mal configuré sur InfinityFree | Créer le `.env` avec les vraies valeurs InfinityFree |
| CORS bloqué | Liste d'origines trop restrictive | `Access-Control-Allow-Origin: *` |
| Render timeout | Flask écoutait sur `127.0.0.1` au lieu de `0.0.0.0` | `app.run(host='0.0.0.0', port=int(os.environ.get('PORT', 5050)))` |
| Render ne trouve pas les `.joblib` | Root directory = `Application/api`, les modèles sont à la racine | Root directory vide + chemins relatifs `../../modeles` |
| `requirements.txt` manquant | Fichier créé en local mais jamais pushé | `git add Application/api/requirements.txt && git push` |
4. Uploader le dossier `Application/` via **FTP** (FileZilla) à la racine `public_html/`

---

### Étape 3 — Configurer le `.env` sur le serveur

Créer le fichier `public_html/Application/.env` sur le serveur FTP avec :

```
GEMINI_API_KEY=ta_clé_gemini

DB_HOST=host_fourni_par_planethoster
DB_PORT=3306
DB_NAME=nom_de_ta_bdd
DB_USER=user_bdd
DB_PASS=mot_de_passe_bdd

ML_SERVER_URL=https://ia-naha-ml.onrender.com
```

---

### Étape 4 — Ajouter le domaine dans le CORS

Dans `Application/api/config.php`, ajouter le domaine PlanetHoster à la liste :

```php
$_allowed_origins = [
    'http://localhost:8888', 'http://127.0.0.1:8888',
    'http://localhost:8080', 'http://127.0.0.1:8080',
    'http://localhost',      'http://127.0.0.1',
    'https://ton-domaine.planethoster.net',  // ← ajouter ici
];
```

---

### Étape 5 — Vérifier que tout fonctionne

| Test | URL attendue |
|---|---|
| Page d'accueil | `https://ton-domaine.planethoster.net/Application/login.html` |
| Serveur ML | `https://ia-naha-ml.onrender.com/health` |
| API PHP | `https://ton-domaine.planethoster.net/Application/api/login.php` |
