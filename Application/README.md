# IA-NAHA — Documentation Technique

---

## 0. Accès à l'application

### En ligne (aucune installation)

L'application est accessible directement depuis n'importe quel navigateur :

**`https://ianaha.rf.gd/IA-NAHA/Application/index.html`**

Rien à installer, rien à configurer.

---

### En local (développement)

L'application nécessite un serveur local Apache+PHP+MySQL et un serveur Python Flask pour la prédiction ML.

**Étape 1 — Configurer le `.env`**

Copier `.env.example` → `.env` dans le dossier `Application/` et remplir selon l'environnement :

```
# Clé API Gemini (obligatoire)
GEMINI_API_KEY=ta_clé_gemini

# Base de données
DB_HOST=localhost
DB_PORT=8889        # MAMP (macOS) → 8889 | XAMPP/WAMP (Windows) → 3306
DB_NAME=ia-naha
DB_USER=root
DB_PASS=root

# Serveur ML (laisser vide en local, le fallback 127.0.0.1:5050 est utilisé)
# ML_SERVER_URL=https://ia-naha.onrender.com
```

**Étape 2 — Lancer le serveur ML**

```bash
# macOS
python3 Application/api/ml_server.py

# Windows
python Application/api/ml_server.py
```

> Si le serveur ML n'est pas lancé, l'app fonctionne quand même — une estimation de sommeil est calculée directement côté PHP en fallback.

**Étape 3 — Accéder à l'app**

- macOS (MAMP) : `http://localhost:8888/SD4/IA-NAHA/Application/login.html`
- Windows (XAMPP) : `http://localhost/SD4/IA-NAHA/Application/login.html`

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

### Base de données

1. Ouvrir phpMyAdmin (`http://localhost:8888/phpMyAdmin` sur MAMP, `http://localhost/phpmyadmin` sur XAMPP)
2. Créer une base nommée `ia-naha`
3. Importer le fichier `database/ia-naha.sql`

> **Windows** : éditer le `.env` avec VS Code ou Notepad++ en **UTF-8 sans BOM**, jamais avec Notepad.  
> Clé Gemini : https://aistudio.google.com/app/apikey

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

L'application nécessite **deux hébergements séparés** car elle combine PHP/MySQL et un serveur Python Flask.

### Architecture de production

```
Navigateur
  ├─► InfinityFree  (PHP + MySQL)  — pages HTML + API PHP
  └─► Render.com    (Python Flask) — serveur ML prédiction sommeil
```

---

### Étape 1 — Déployer le serveur ML sur Render.com

1. Créer un compte sur [render.com](https://render.com)
2. **New → Web Service** → connecter le repo GitHub
3. Configurer :
   - **Root directory** : `Application/api`
   - **Build command** : `pip install flask joblib numpy pandas scikit-learn`
   - **Start command** : `python ml_server.py`
4. URL publique du service : `https://ia-naha.onrender.com`
5. Tester : `https://ia-naha.onrender.com/health` doit retourner `{"status":"ok"}`

> **Note** : sur le plan gratuit Render, le serveur se met en veille après 15 min d'inactivité — la première requête peut prendre ~30 secondes.

---

### Étape 2 — Déployer PHP + MySQL sur InfinityFree

1. Créer un compte sur [infinityfree.com](https://infinityfree.com)
2. Dans le panel InfinityFree :
   - Créer un hébergement → noter le sous-domaine
   - Créer une base MySQL via **MySQL Databases**
   - Noter : host, port, nom BDD, user, password
3. Importer `database/ia-naha.sql` via phpMyAdmin
4. Uploader le repo via **FTP** (FileZilla) dans `htdocs/` pour obtenir `htdocs/IA-NAHA/Application/`

---

### Étape 3 — Configurer le `.env` sur le serveur

Créer `htdocs/IA-NAHA/Application/.env` via FTP :

```
GEMINI_API_KEY=ta_clé_gemini

DB_HOST=host_fourni_par_infinityfree
DB_PORT=3306
DB_NAME=nom_de_ta_bdd
DB_USER=user_bdd
DB_PASS=mot_de_passe_bdd

ML_SERVER_URL=https://ia-naha.onrender.com
```

---

### Étape 4 — Vérifier que tout fonctionne

| Test | URL |
|---|---|
| Page d'accueil | `https://ianaha.rf.gd/IA-NAHA/Application/index.html` |
| Serveur ML | `https://ia-naha.onrender.com/health` |

