<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/bdd.php';
$bdd = getBD();

// CSRF questionnaire IA
if (empty($_SESSION['csrf_ia'])) {
    $_SESSION['csrf_ia'] = bin2hex(random_bytes(32));
}

$isLoggedIn = isset($_SESSION['utilisateur']['id_utilisateur']);
$needsQuestionnaire = false;
$questionnaireSaved = false;
$questionnaireError = null;
$heroSummary = null;

if ($isLoggedIn) {
    $idUser = (int)$_SESSION['utilisateur']['id_utilisateur'];

    // Est-ce qu'on a déjà un profil objectif ?
    $stmt = $bdd->prepare("SELECT COUNT(*) FROM objectif_utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$idUser]);
    $needsQuestionnaire = ((int)$stmt->fetchColumn() === 0);

    // Traitement questionnaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ia_questionnaire') {
        $token = (string)($_POST['csrf'] ?? '');
        if (!hash_equals($_SESSION['csrf_ia'], $token)) {
            $questionnaireError = "Token invalide. Recharge la page et réessaie.";
        } else {
            $age = (int)($_POST['age'] ?? 0);
            $taille = (int)($_POST['taille'] ?? 0);
            $poids = (float)str_replace(',', '.', (string)($_POST['poids'] ?? '0'));
            $activite = (float)str_replace(',', '.', (string)($_POST['activite'] ?? '0'));
            $sexe = (string)($_POST['sexe'] ?? '');

            $validSexe = in_array($sexe, ['H', 'F'], true);
            $validActivite = in_array($activite, [1.2, 1.375, 1.55, 1.725, 1.9], true);

            if ($age < 10 || $age > 99) {
                $questionnaireError = "Âge invalide.";
            } elseif ($taille < 120 || $taille > 230) {
                $questionnaireError = "Taille invalide.";
            } elseif ($poids < 30 || $poids > 250) {
                $questionnaireError = "Poids invalide.";
            } elseif (!$validSexe) {
                $questionnaireError = "Sexe invalide.";
            } elseif (!$validActivite) {
                $questionnaireError = "Niveau d'activité invalide.";
            } else {
                // Mifflin-St Jeor
                $bmr = 10 * $poids + 6.25 * $taille - 5 * $age + ($sexe === 'H' ? 5 : -161);
                $maintenance = (int)round($bmr * $activite);
                $objectifNom = "Maintien";
                $objectifKcal = $maintenance;

                try {
                    // On garde 1 seule entrée (comme save_goal.php)
                    $bdd->prepare("DELETE FROM objectif_utilisateur WHERE id_utilisateur = ?")->execute([$idUser]);

                    $sql = "
                        INSERT INTO objectif_utilisateur
                        (id_utilisateur, objectif_nom, objectif_kcal, maintenance, age, poids, taille, activite, sexe)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                    $bdd->prepare($sql)->execute([
                        $idUser,
                        $objectifNom,
                        $objectifKcal,
                        $maintenance,
                        $age,
                        $poids,
                        $taille,
                        $activite,
                        $sexe
                    ]);

                    // PRG (évite double submit)
                    header("Location: IA.php?saved=1");
                    exit;
                } catch (Exception $e) {
                    $questionnaireError = "Erreur serveur. Réessaie plus tard.";
                }
            }
        }
    }

    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        $questionnaireSaved = true;
        $needsQuestionnaire = false;
    }

    // Résumé pour la carte du hero (si profil OK)
    if (!$needsQuestionnaire) {
        $sqlGoal = "
            SELECT *
            FROM objectif_utilisateur
            WHERE id_utilisateur = :id
            ORDER BY date_maj DESC
            LIMIT 1
        ";
        $stmtGoal = $bdd->prepare($sqlGoal);
        $stmtGoal->execute(['id' => $idUser]);
        $goal = $stmtGoal->fetch(PDO::FETCH_ASSOC) ?: null;

        $sqlIn = "
            SELECT SUM(p.energie_kcal * c.quantite / 100) AS kcal_in
            FROM consommation c
            JOIN produits p ON p.id_produit = c.id_produit
            WHERE c.id_utilisateur = :u
              AND DATE(c.date_conso) = CURDATE()
        ";
        $stmtIn = $bdd->prepare($sqlIn);
        $stmtIn->execute(['u' => $idUser]);
        $kcalIn = (int)($stmtIn->fetchColumn() ?: 0);

        $sqlOut = "
            SELECT SUM(s.kcal_h_70kg * (a.duree_minutes / 60)) AS kcal_out
            FROM activite a
            JOIN sports s ON s.id_sport = a.id_sport
            WHERE a.id_utilisateur = :u
              AND DATE(a.date_sport) = CURDATE()
        ";
        $stmtOut = $bdd->prepare($sqlOut);
        $stmtOut->execute(['u' => $idUser]);
        $kcalOut = (int)($stmtOut->fetchColumn() ?: 0);

        $objectifKcal = (int)($goal['objectif_kcal'] ?? 0);
        $maintenance = (int)($goal['maintenance'] ?? 0);
        $objectifNom = (string)($goal['objectif_nom'] ?? '');
        $progress = ($objectifKcal > 0) ? min(200, max(0, (int)round(($kcalIn / $objectifKcal) * 100))) : 0;
        $solde = $kcalIn - $kcalOut;

        $heroSummary = [
            'goal' => $goal,
            'kcalIn' => $kcalIn,
            'kcalOut' => $kcalOut,
            'solde' => $solde,
            'objectifKcal' => $objectifKcal,
            'maintenance' => $maintenance,
            'objectifNom' => $objectifNom,
            'progress' => $progress
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NAHA — IA</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800&display=swap" rel="stylesheet">

  <!-- Styles globaux + IA -->
  <link rel="stylesheet" href="assets/css/accueil-style.css" />
  <link rel="stylesheet" href="assets/css/IA-style.css" />

  <style>
    /* Smooth progress bar transition */
    .ia-summaryBarFill,
    #ia-wiz-progress {
      transition: width 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    }
  </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="ia-page">
  <?php if ($isLoggedIn && $needsQuestionnaire): ?>
    <section class="ia-section ia-onboarding">
      <div class="container">
        <div class="ia-onboardingCard">
          <div>
            <p class="badge">Étape 1/1</p>
            <h2 class="section-title" style="margin-bottom:8px">Parle nous de toi</h2>
            <p class="ia-intro" style="margin-bottom:14px">
              On a besoin de quelques infos pour personnaliser l'expérience (calculs + recommandations).
            </p>
          </div>

          <?php if (!empty($questionnaireError)): ?>
            <div class="ia-alert ia-alert--error">
              <?= htmlspecialchars($questionnaireError, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <form class="ia-form" method="post" action="IA.php" autocomplete="on" id="ia-questionnaire">
            <input type="hidden" name="action" value="ia_questionnaire" />
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_ia'], ENT_QUOTES, 'UTF-8') ?>" />

            <!-- Valeurs finales envoyées au PHP -->
            <input type="hidden" name="age"     id="ia-q-age"     value="<?= htmlspecialchars((string)($_POST['age']     ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="taille"  id="ia-q-taille"  value="<?= htmlspecialchars((string)($_POST['taille']  ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="poids"   id="ia-q-poids"   value="<?= htmlspecialchars((string)($_POST['poids']   ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="activite" id="ia-q-activite" value="<?= htmlspecialchars((string)($_POST['activite'] ?? '1.55'), ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="sexe"    id="ia-q-sexe"    value="<?= htmlspecialchars((string)($_POST['sexe']    ?? 'H'),   ENT_QUOTES, 'UTF-8') ?>" />

            <div class="ia-wizard" data-wizard="ia">
              <div class="ia-wizardTop" style="display:flex; gap:12px; align-items:center; justify-content:space-between; margin: 10px 0 12px;">
                <div style="min-width: 120px;">
                  <div class="ia-summaryLabel" style="margin:0;">Progression</div>
                  <div class="ia-summaryMeta" id="ia-wiz-stepText" style="margin-top:2px;">—</div>
                </div>
                <div class="ia-summaryBar" style="flex:1; margin:0;" aria-hidden="true">
                  <div class="ia-summaryBarFill" id="ia-wiz-progress" style="width:0%;"></div>
                </div>
              </div>

              <div id="ia-wiz-question" class="ia-field" style="margin-top: 8px;"></div>

              <div class="ia-wizardActions" style="display:flex; gap:10px; justify-content:flex-end; margin-top: 14px;">
                <button class="btn ghost" type="button" id="ia-wiz-prev">Précédent</button>
                <button class="btn"       type="button" id="ia-wiz-next">Suivant</button>
                <button class="btn big"   type="submit" id="ia-wiz-submit" style="display:none;">Enregistrer</button>
              </div>
            </div>

            <p class="ia-note">Tu pourras modifier ces infos plus tard dans "Mon Profil".</p>
          </form>
        </div>
      </div>
    </section>

    <div class="divider"></div>
  <?php elseif ($isLoggedIn && $questionnaireSaved): ?>
    <section class="ia-section ia-onboarding">
      <div class="container">
        <div class="ia-alert ia-alert--success">
          Questionnaire enregistré. Tu peux maintenant utiliser l'IA.
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="ia-hero">
    <div class="container ia-hero__inner">
      <div class="ia-heroGrid">
        <div class="ia-heroCopy">
          <p class="badge">Ton coach data & perf</p>
          <h1 class="ia-title">L'IA NAHA</h1>
          <p class="ia-sub">
            Pose une question, demande une séance, un plan repas, ou un conseil. Pour l'instant on met en place l'interface :
            tu verras clairement où brancher le vrai chatbot plus tard.
          </p>

          <div class="ia-cta">
            <?php if (isset($_SESSION['utilisateur'])): ?>
              <a class="btn big" href="tableau.php">Aller au tableau</a>
            <?php else: ?>
              <a class="btn big" href="sinscrire.php">Commencer</a>
              <a class="btn ghost" href="seconnecter.php">Se connecter</a>
            <?php endif; ?>
          </div>
        </div>

        <aside class="ia-heroArt" aria-label="Résumé personnalisé">
          <div class="ia-summaryCard">
            <div class="ia-summaryTop">
              <span class="ia-summaryBadge">Résumé perso</span>
              <span class="ia-summaryDate">Aujourd'hui</span>
            </div>

            <?php if (!$isLoggedIn): ?>
              <h3 class="ia-summaryTitle">Connecte-toi</h3>
              <p class="ia-summarySub">Pour voir ton objectif, tes calories et ton activité.</p>
              <div class="ia-summaryActions">
                <a class="btn" href="seconnecter.php">Se connecter</a>
                <a class="btn ghost" href="sinscrire.php">S'inscrire</a>
              </div>
            <?php elseif ($needsQuestionnaire): ?>
              <h3 class="ia-summaryTitle">Profil incomplet</h3>
              <p class="ia-summarySub">Complète le questionnaire pour activer le suivi et le chat.</p>
              <div class="ia-summaryHint">Le questionnaire est juste au-dessus.</div>
            <?php else: ?>
              <?php
                $p      = (int)($heroSummary['progress']     ?? 0);
                $obj    = (int)($heroSummary['objectifKcal'] ?? 0);
                $maint  = (int)($heroSummary['maintenance']  ?? 0);
                $in     = (int)($heroSummary['kcalIn']       ?? 0);
                $out    = (int)($heroSummary['kcalOut']      ?? 0);
                $solde  = (int)($heroSummary['solde']        ?? 0);
                $nomObj = (string)($heroSummary['objectifNom'] ?? '');
              ?>

              <div class="ia-summaryRow">
                <div>
                  <div class="ia-summaryLabel">Objectif</div>
                  <div class="ia-summaryBig">
                    <?= $obj > 0 ? $obj . " kcal" : "Non défini" ?>
                  </div>
                  <div class="ia-summaryMeta">
                    <?= $nomObj !== '' ? htmlspecialchars($nomObj, ENT_QUOTES, 'UTF-8') : "—" ?>
                    <?php if ($maint > 0): ?>
                      · maintenance <?= $maint ?> kcal
                    <?php endif; ?>
                  </div>
                </div>

                <div class="ia-summaryGauge" aria-label="Progression du jour">
                  <div class="ia-summaryPct"><?= $obj > 0 ? $p . "%" : "—" ?></div>
                  <div class="ia-summarySmall">atteint</div>
                </div>
              </div>

              <div class="ia-summaryBar" aria-hidden="true">
                <div class="ia-summaryBarFill" style="width: <?= $obj > 0 ? min(100, $p) : 0 ?>%;"></div>
              </div>

              <div class="ia-summaryGrid">
                <div class="ia-metric">
                  <div class="ia-metricTop"><span>🔥</span><span>In</span></div>
                  <div class="ia-metricVal"><?= $in ?> kcal</div>
                  <div class="ia-metricSub">consommées</div>
                </div>
                <div class="ia-metric">
                  <div class="ia-metricTop"><span>🏋️‍♂️</span><span>Out</span></div>
                  <div class="ia-metricVal"><?= $out ?> kcal</div>
                  <div class="ia-metricSub">dépensées</div>
                </div>
                <div class="ia-metric ia-metric--wide">
                  <div class="ia-metricTop"><span>🧮</span><span>Solde</span></div>
                  <div class="ia-metricVal <?= $solde >= 0 ? 'is-green' : 'is-red' ?>">
                    <?= $solde >= 0 ? '+' : '' ?><?= $solde ?> kcal
                  </div>
                  <div class="ia-metricSub">aujourd'hui</div>
                </div>
              </div>

              <div class="ia-summaryActions">
                <a class="btn ghost" href="calculateur.php">Modifier mon objectif</a>
                <a class="btn" href="tableau.php">Voir le tableau</a>
              </div>
            <?php endif; ?>
          </div>
        </aside>
      </div>
    </div>
  </section>

  <div class="divider"></div>

  <section class="ia-section ia-chatSection" id="chat">
    <div class="container">
      <div class="ia-panel ia-panel--full">
        <div class="ia-chatHead">
          <div>
            <h2 class="section-title" style="margin:0 0 6px">Chat IA</h2>
          </div>
        </div>

        <?php if (!$isLoggedIn): ?>
          <div class="ia-alert ia-alert--error" style="margin-top:14px">
            Connecte-toi pour accéder au chat IA.
          </div>
        <?php elseif ($needsQuestionnaire): ?>
          <div class="ia-alert ia-alert--error" style="margin-top:14px">
            Termine le questionnaire au-dessus pour débloquer le chat.
          </div>
        <?php else: ?>
          <div id="ia-chat" class="ia-chat">
            <div class="ia-chatWindow" id="ia-chatWindow" aria-live="polite">
              <div class="ia-msg ia-msg--bot">
                Salut <?= isset($_SESSION['utilisateur']['prenom']) ? htmlspecialchars($_SESSION['utilisateur']['prenom'], ENT_QUOTES, 'UTF-8') : '!' ?>.
                Dis-moi ce que tu veux faire aujourd'hui.
              </div>
            </div>

            <form id="ia-chatForm" class="ia-chatForm">
              <label class="sr-only" for="ia-message">Message</label>
              <textarea id="ia-message" class="ia-chatInput" rows="2" maxlength="600"
                        placeholder="Écris ton message… (ex: 'Séance haut du corps 45 min')"></textarea>
              <button class="btn-purple" type="submit">Envoyer</button>
            </form>

            <!--
              ==========================================================
              🔌 ICI TU BRANCHES LE CHATBOT PLUS TARD

              Option A (recommandé) : appeler une route PHP JSON, ex:
                POST /api/chat.php  { message: "...", csrf: "..." }

              Option B : faire le traitement directement dans IA.php (POST),
              mais c'est moins propre.
              ==========================================================
            -->
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="divider"></div>

  <section class="ia-section" id="features">
    <div class="container">
      <h2 class="section-title">Ce que l'IA peut faire</h2>
      <p class="ia-intro">
        Des exemples typiques (à adapter à ta vraie IA) : recommandations, plans, et réponses basées sur tes objectifs.
      </p>

      <div class="ia-cards">
        <article class="ia-card">
          <div class="ia-card__icon">📊</div>
          <h3 class="ia-card__title">Analyser tes tendances</h3>
          <p class="ia-card__text">
            Résumer tes données (poids, sport, apports) et pointer ce qui compte.
          </p>
        </article>

        <article class="ia-card">
          <div class="ia-card__icon">🎯</div>
          <h3 class="ia-card__title">Proposer un plan simple</h3>
          <p class="ia-card__text">
            Te donner une structure claire (séances / repas) selon ton objectif.
          </p>
        </article>

        <article class="ia-card">
          <div class="ia-card__icon">🧠</div>
          <h3 class="ia-card__title">Te coacher au quotidien</h3>
          <p class="ia-card__text">
            Des conseils actionnables, courts, et adaptés à ton niveau.
          </p>
        </article>
      </div>
    </div>
  </section>

  <div class="divider"></div>

  <section class="ia-section" id="how">
    <div class="container">
      <h2 class="section-title">Comment ça marche</h2>
      <p class="ia-intro">
        On récupère ton contexte (questionnaire + objectif), puis on te répond avec des recommandations.
      </p>

      <div class="ia-steps">
        <article class="ia-step">
          <div class="step-chip">1</div>
          <h3 class="ia-step__title">Tu demandes</h3>
          <p class="ia-step__text">Une séance, un plan, ou une question nutrition/perf.</p>
        </article>
        <article class="ia-step">
          <div class="step-chip">2</div>
          <h3 class="ia-step__title">NAHA comprend</h3>
          <p class="ia-step__text">On combine ton profil + ton objectif pour personnaliser.</p>
        </article>
        <article class="ia-step">
          <div class="step-chip">3</div>
          <h3 class="ia-step__title">Tu appliques</h3>
          <p class="ia-step__text">Tu testes, tu ajustes, et tu suis l'évolution.</p>
        </article>
      </div>
    </div>
  </section>

  <div class="divider"></div>

  <section class="ia-section">
    <div class="container ia-grid">
      <aside class="ia-side">
        <div class="ia-sideCard">
          <h3>Liens rapides</h3>
          <ul>
            <li><a href="calculateur.php">Calculateur</a></li>
            <li><a href="consommation.php">Consommation</a></li>
            <li><a href="tableau.php">Tableau de bord</a></li>
            <li><a href="contact.php">Contact</a></li>
          </ul>
        </div>
      </aside>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>

<script>
(() => {
  const form      = document.getElementById('ia-questionnaire');
  if (!form) return;

  const elQuestion  = document.getElementById('ia-wiz-question');
  const elPrev      = document.getElementById('ia-wiz-prev');
  const elNext      = document.getElementById('ia-wiz-next');
  const elSubmit    = document.getElementById('ia-wiz-submit');
  const elProgress  = document.getElementById('ia-wiz-progress');
  const elStepText  = document.getElementById('ia-wiz-stepText');

  const hAge      = document.getElementById('ia-q-age');
  const hTaille   = document.getElementById('ia-q-taille');
  const hPoids    = document.getElementById('ia-q-poids');
  const hActivite = document.getElementById('ia-q-activite');
  const hSexe     = document.getElementById('ia-q-sexe');

  if (!elQuestion || !elPrev || !elNext || !elSubmit || !elProgress || !elStepText
      || !hAge || !hTaille || !hPoids || !hActivite || !hSexe) return;

  // FIX 1: apostrophes escaped with double-quotes to avoid SyntaxError
  const questions = [
    {
      key: 'age',
      label: "Quel est ton âge ?",
      type: 'number',
      placeholder: 'ex: 20',
      min: 10,
      max: 99,
      step: 1,
      get: () => hAge.value,
      set: (v) => { hAge.value = v; },
    },
    {
      key: 'taille',
      label: "Quelle est ta taille (cm) ?",
      type: 'number',
      placeholder: 'ex: 178',
      min: 120,
      max: 230,
      step: 1,
      get: () => hTaille.value,
      set: (v) => { hTaille.value = v; },
    },
    {
      key: 'poids',
      label: "Quel est ton poids (kg) ?",
      type: 'number',
      placeholder: 'ex: 72',
      min: 30,
      max: 250,
      step: 0.1,
      get: () => hPoids.value,
      set: (v) => { hPoids.value = v; },
    },
    {
      key: 'activite',
      label: "Quel est ton niveau d'activité ?",   // FIX 1: double-quoted
      type: 'select',
      options: [
        { value: '1.2',   label: "Sédentaire — activité faible" },
        { value: '1.375', label: "Léger — 1-2 séances/semaines" },
        { value: '1.55',  label: "Modéré — 3-4 séances/semaines" },
        { value: '1.725', label: "Intense — 4-6 séances/semaines" },
        { value: '1.9',   label: "Très intense — 7 séances/semaines" },
      ],
      get: () => hActivite.value || '1.55',
      set: (v) => { hActivite.value = v; },
    },
    {
      key: 'sexe',
      label: "Quel est ton sexe ?",
      type: 'segmented',
      options: [
        { value: 'H', label: 'Homme' },
        { value: 'F', label: 'Femme' },
      ],
      get: () => hSexe.value || 'H',
      set: (v) => { hSexe.value = v; },
    },
  ];

  let idx = 0;

  const clamp = (n, min, max) => Math.min(max, Math.max(min, n));

  const validate = (q, raw) => {
    if (q.type === 'number') {
      const v = String(raw ?? '').trim().replace(',', '.');
      const num = Number(v);
      if (!Number.isFinite(num)) return { ok: false, msg: "Valeur invalide." };
      if (typeof q.min === 'number' && num < q.min) return { ok: false, msg: `Minimum ${q.min}.` };
      if (typeof q.max === 'number' && num > q.max) return { ok: false, msg: `Maximum ${q.max}.` };
      return { ok: true, value: String(num) };
    }
    if (q.type === 'select' || q.type === 'segmented') {
      const v = String(raw ?? '').trim();
      const allowed = new Set((q.options || []).map(o => String(o.value)));
      if (!allowed.has(v)) return { ok: false, msg: "Choix invalide." };
      return { ok: true, value: v };
    }
    return { ok: true, value: String(raw ?? '') };
  };

  // FIX 2: smooth progress — uses requestAnimationFrame to let the DOM
  // paint the element first (width:0%) before jumping to the target width,
  // so the CSS transition always fires even on the very first render.
  const setProgress = (pct) => {
    requestAnimationFrame(() => {
      elProgress.style.width = `${pct}%`;
    });
  };

  const render = () => {
    idx = clamp(idx, 0, questions.length - 1);
    const q = questions[idx];

    // Progress: 0% on step 1, 100% on last step
    const pct = Math.round((idx / (questions.length - 1)) * 100);
    setProgress(pct);
    elStepText.textContent = `Question ${idx + 1} / ${questions.length}`;

    elPrev.style.display   = idx === 0 ? 'none' : '';
    const isLast           = idx === questions.length - 1;
    elNext.style.display   = isLast ? 'none' : '';
    elSubmit.style.display = isLast ? '' : 'none';

    elQuestion.innerHTML = '';

    const label = document.createElement('label');
    label.textContent = q.label;
    label.setAttribute('for', `ia-wiz-input-${q.key}`);
    elQuestion.appendChild(label);

    const error = document.createElement('div');
    error.style.cssText = 'margin-top:8px; color:#b42318; font-size:0.95rem; display:none;';

    const showError = (msg) => {
      error.textContent = msg;
      error.style.display = msg ? '' : 'none';
    };

    const current = q.get();

    if (q.type === 'number') {
      const input = document.createElement('input');
      input.id          = `ia-wiz-input-${q.key}`;
      input.type        = 'number';
      if (q.min  != null) input.min  = String(q.min);
      if (q.max  != null) input.max  = String(q.max);
      if (q.step != null) input.step = String(q.step);
      input.placeholder = q.placeholder || '';
      input.value       = current || '';
      input.required    = true;

      input.addEventListener('input', () => showError(''));
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          if (idx < questions.length - 1) elNext.click();
        }
      });

      elQuestion.appendChild(input);
      elQuestion.appendChild(error);
      setTimeout(() => input.focus(), 0);

      // FIX 3: use a named handler assigned once — avoids stale closures
      elNext.onclick = () => {
        const res = validate(q, input.value);
        if (!res.ok) return showError(res.msg);
        q.set(res.value);
        idx += 1;
        render();
      };

    } else if (q.type === 'select') {
      const select = document.createElement('select');
      select.id       = `ia-wiz-input-${q.key}`;
      select.required = true;

      (q.options || []).forEach(o => {
        const opt      = document.createElement('option');
        opt.value      = String(o.value);
        opt.textContent = String(o.label);
        if (String(o.value) === String(current || '')) opt.selected = true;
        select.appendChild(opt);
      });

      select.addEventListener('change', () => showError(''));
      elQuestion.appendChild(select);
      elQuestion.appendChild(error);
      setTimeout(() => select.focus(), 0);

      elNext.onclick = () => {
        const res = validate(q, select.value);
        if (!res.ok) return showError(res.msg);
        q.set(res.value);
        idx += 1;
        render();
      };

    } else if (q.type === 'segmented') {
      const wrap = document.createElement('div');
      wrap.className = 'ia-segmented';
      wrap.setAttribute('role', 'group');
      wrap.setAttribute('aria-label', q.label);

      // FIX 4: initialise hidden input immediately with default value
      // so it's never empty even if the user never touches the radio
      const defaultVal = String(current || (q.options?.[0]?.value ?? ''));
      q.set(defaultVal);

      (q.options || []).forEach(o => {
        const lab = document.createElement('label');
        lab.className = 'ia-segBtn';

        const inp  = document.createElement('input');
        inp.type   = 'radio';
        inp.name   = 'ia-wiz-sexe-ui';
        inp.value  = String(o.value);
        inp.checked = String(o.value) === defaultVal;

        inp.addEventListener('change', () => {
          showError('');
          q.set(inp.value);   // keep hidden input in sync on every change
        });

        lab.appendChild(inp);
        lab.appendChild(document.createTextNode(' ' + String(o.label)));
        wrap.appendChild(lab);
      });

      elQuestion.appendChild(wrap);
      elQuestion.appendChild(error);

      // Last step: Next button hidden, submit shown — no elNext.onclick needed.
      // We still define it defensively in case the wizard order changes.
      elNext.onclick = () => {
        const res = validate(q, q.get());
        if (!res.ok) return showError(res.msg);
        idx += 1;
        render();
      };
    }

    elPrev.onclick = () => {
      idx -= 1;
      render();
    };
  };

  form.addEventListener('submit', (e) => {
    // FIX 5: sync segmented (radio) before final validation in case
    // the user never interacted with it (relies on the default set above)
    for (const q of questions) {
      if (q.type === 'segmented') {
        const checked = document.querySelector('input[name="ia-wiz-sexe-ui"]:checked');
        if (checked) q.set(checked.value);
      }

      const res = validate(q, q.get());
      if (!res.ok) {
        e.preventDefault();
        idx = questions.findIndex(x => x.key === q.key);
        render();
        return;
      }
      q.set(res.value);
    }
  });

  render();
})();
</script>

<script>
(() => {
  const form  = document.getElementById('ia-chatForm');
  const input = document.getElementById('ia-message');
  const win   = document.getElementById('ia-chatWindow');
  if (!form || !input || !win) return;

  const addMsg = (text, who) => {
    const div       = document.createElement('div');
    div.className   = 'ia-msg ' + (who === 'user' ? 'ia-msg--user' : 'ia-msg--bot');
    div.textContent = text;
    win.appendChild(div);
    win.scrollTop = win.scrollHeight;
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = (input.value || '').trim();
    if (!msg) return;

    addMsg(msg, 'user');
    input.value = '';

    // 🔌 ICI, plus tard: remplacer par un appel fetch() vers ton endpoint chatbot.
    // Exemple:
    // const res  = await fetch('api/chat.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ message: msg }) });
    // const json = await res.json();
    // addMsg(json.reply, 'bot');
    addMsg("OK — l'IA n'est pas encore branchée. (Interface prête)", 'bot');
  });
})();
</script>

</body>
</html>