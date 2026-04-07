const _local = ['localhost','127.0.0.1'].includes(window.location.hostname);
    const API      = window.location.origin + (_local ? '/SD4/IA-NAHA/Application/api' : '/IA-NAHA/Application/api');
    const TOTAL    = 13;
    let current    = 0;

    // user_id depuis localStorage
    const USER_ID   = localStorage.getItem('naha_user_id') || sessionStorage.getItem('naha_user_id') || null;
    const getToken  = () => localStorage.getItem('naha_token') || '';
    const authHeaders = (extra={}) => ({ 'Content-Type':'application/json', 'Authorization':'Bearer '+getToken(), 'X-Auth-Token': getToken(), ...extra });

    // ── Init dots ──
    function initDots() {
        const wrap = document.getElementById('step-dots');
        wrap.innerHTML = '';
        for (let i = 0; i < TOTAL; i++) {
            const d = document.createElement('div');
            d.className = 'dot' + (i === 0 ? ' active' : '');
            wrap.appendChild(d);
        }
    }
    initDots();
    document.getElementById('nav-total').textContent = TOTAL;

    // ── Update UI ──
    function updateUI() {
        const pct = Math.round(((current + 1) / TOTAL) * 100);
        document.getElementById('progress-fill').style.width = pct + '%';
        document.getElementById('nav-current').textContent = current + 1;
        document.getElementById('bottom-pct').textContent = pct + '%';

        document.querySelectorAll('.dot').forEach((d, i) => {
            d.className = 'dot' + (i < current ? ' done' : i === current ? ' active' : '');
        });
    }

    // ── Wrap height (fix scroll for tall questions) ──
    function updateWrapHeight() {
        const active = document.querySelector('.question.active');
        const wrap   = document.getElementById('questions-wrap');
        const main   = document.querySelector('.main');
        if (active && wrap) {
            wrap.style.minHeight = active.scrollHeight + 'px';
        }
        if (main) main.scrollTop = 0;
    }

    // ── Navigation ──
    function next() {
        if (!validate(current)) return;

        const qs = document.querySelectorAll('.question');
        qs[current].classList.add('exit-up');

        setTimeout(() => {
            qs[current].classList.remove('active', 'exit-up');
            if (current < TOTAL - 1) {
                current++;
                qs[current].classList.add('active');
                updateUI();
                focusQuestion(current);
                updateWrapHeight();
            }
        }, 300);
    }

    function prev() {
        if (current === 0) return;
        const qs = document.querySelectorAll('.question');
        qs[current].classList.remove('active');
        current--;
        qs[current].classList.add('active');
        updateUI();
        focusQuestion(current);
        updateWrapHeight();
    }

    function focusQuestion(idx) {
        setTimeout(() => {
            const inp = document.querySelector(`.question[data-q="${idx}"] input`);
            if (inp) inp.focus();
        }, 400);
    }

    // ── Validation ──
    function setError(id, msg) {
        const el  = document.getElementById('err-' + id);
        const inp = document.getElementById('q-' + id);
        if (el)  el.textContent = msg;
        if (inp) inp.style.borderColor = msg ? '#ef4444' : '';
    }

    function validate(idx) {
        switch(idx) {
            case 0: {
                const v = document.getElementById('q-prenom').value.trim();
                return !!v;
            }
            case 1: {
                const v = +document.getElementById('q-age').value;
                if (!v)      { setError('age', 'Veuillez saisir votre âge.'); return false; }
                if (v < 10)  { setError('age', 'Âge minimum : 10 ans.'); return false; }
                if (v > 110) { setError('age', 'Âge maximum : 110 ans.'); return false; }
                setError('age', ''); return true;
            }
            case 2: return !!document.querySelector('.choice[data-group="sexe"].selected');
            case 3: {
                const v = +document.getElementById('q-poids').value;
                if (!v)      { setError('poids', 'Veuillez saisir votre poids.'); return false; }
                if (v < 30)  { setError('poids', 'Poids minimum : 30 kg.'); return false; }
                if (v > 250) { setError('poids', 'Poids maximum : 250 kg.'); return false; }
                setError('poids', ''); return true;
            }
            case 4: {
                const v = +document.getElementById('q-taille').value;
                if (!v)      { setError('taille', 'Veuillez saisir votre taille.'); return false; }
                if (v < 100) { setError('taille', 'Taille minimum : 100 cm.'); return false; }
                if (v > 250) { setError('taille', 'Taille maximum : 250 cm.'); return false; }
                setError('taille', ''); return true;
            }
            case 5: return !!document.querySelector('.choice[data-group="activite"].selected');
            case 6: return true; // stress : toujours valide (counter initialisé à 5)
            case 7: return !!document.querySelector('.choice[data-group="activity_type"].selected');
            case 8: {
                const dur = +document.getElementById('q-duration').value;
                const stp = +document.getElementById('q-steps').value;
                const hyd = +document.getElementById('q-hydration').value;
                let ok = true;
                if (!dur || dur < 5)   { setError('duration', 'Minimum 5 minutes par séance.'); ok = false; }
                else if (dur > 240)    { setError('duration', 'Maximum 4h (240 min) par séance.'); ok = false; }
                else                   { setError('duration', ''); }
                if (!stp || stp < 500)  { setError('steps', 'Minimum 500 pas par jour.'); ok = false; }
                else if (stp > 40000)   { setError('steps', 'Maximum 40 000 pas par jour.'); ok = false; }
                else                    { setError('steps', ''); }
                if (!hyd || hyd < 0.5) { setError('hydration', 'Minimum 0,5 L par jour.'); ok = false; }
                else if (hyd > 5)      { setError('hydration', 'Maximum 5 L par jour — au-delà c\'est dangereux.'); ok = false; }
                else                   { setError('hydration', ''); }
                return ok;
            }
            case 9:  return !!document.querySelector('.choice[data-group="smoking_status"].selected');
            case 10: return true; // objectifs optionnels
            case 11: return true; // restrictions optionnelles
            case 12: return !!(
                document.querySelector('.choice[data-group="duree"].selected') &&
                document.querySelector('.choice[data-group="repas"].selected')
            );
            default: return true;
        }
    }

    // ── Choice selection ──
    function selectChoice(el) {
        const group = el.dataset.group;
        document.querySelectorAll(`.choice[data-group="${group}"]`)
            .forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
    }

    // ── Collect data ──
    function collectData() {
        const getSelected = (group, multi = false) => {
            const els = [...document.querySelectorAll(
                multi ? `.mchip[data-group="${group}"].selected`
                    : `.choice[data-group="${group}"].selected`
            )];
            if (multi) return els.map(e => e.dataset.val).join(', ');
            return els[0]?.dataset.val || '';
        };

        return {
            prenom:           document.getElementById('q-prenom').value.trim(),
            age:              document.getElementById('q-age').value,
            sexe:             getSelected('sexe'),
            poids:            document.getElementById('q-poids').value,
            taille:           document.getElementById('q-taille').value,
            activite:         getSelected('activite'),
            // nouvelles variables ML
            stress_level:     document.getElementById('q-stress').value,
            activity_type:    getSelected('activity_type'),
            duration_minutes: document.getElementById('q-duration').value,
            daily_steps:      document.getElementById('q-steps').value,
            hydration_level:  document.getElementById('q-hydration').value,
            smoking_status:   getSelected('smoking_status'),
            // nutrition
            objectif:         getSelected('objectifs', true),
            restrictions:     getSelected('restrictions', true),
            allergies:        document.getElementById('q-allergies').value.trim(),
            duree:            getSelected('duree'),
            repas:            getSelected('repas'),
        };
    }

    // ── Counter stress ──
    function changeCounter(key, delta) {
        const inp = document.getElementById('q-' + key);
        const lbl = document.getElementById(key + '-val');
        let val = parseInt(inp.value) + delta;
        val = Math.max(1, Math.min(10, val));
        inp.value = val;
        lbl.textContent = val;
    }

    // ── Submit ──
    async function submitProfile() {
        if (!validate(12)) return;

        document.getElementById('loader').classList.add('show');

        const data = collectData();

        try {
            // Sauvegarde profil si user connecté
            if (USER_ID) {
                await fetch(`${API}/save_profile.php`, {
                    method: 'POST',
                    headers: authHeaders(),
                    body: JSON.stringify(data),
                });
            }

            // Prédiction sommeil via modèle ML
            try {
                const sleepRes = await fetch(`${API}/predict_sleep.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data),
                });
                const sleepData = await sleepRes.json();
                if (sleepData.success) {
                    sessionStorage.setItem('naha_sleep_prediction', sleepData.sleep_hours);
                }
            } catch(e) {
                console.warn('Prédiction sommeil indisponible :', e.message);
            }

            // Stocke dans sessionStorage pour la page de génération
            sessionStorage.setItem('naha_profil', JSON.stringify(data));
            sessionStorage.setItem('naha_user_id', USER_ID || '');

            // Redirige vers la génération
            window.location.href = 'generate.html';

        } catch(e) {
            document.getElementById('loader').classList.remove('show');
            alert('Erreur : ' + e.message);
        }
    }

    // ── Keyboard ──
    document.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            if (current === TOTAL - 1) submitProfile();
            else next();
        }
        if (e.key === 'Backspace' && e.target.tagName !== 'INPUT') prev();

        // Raccourcis clavier pour les choices
        const keyMap = { 'h':'homme','f':'femme','a':'autre', '1':'sedentaire','2':'leger','3':'modere','4':'actif','5':'tres_actif' };
        if (keyMap[e.key.toLowerCase()]) {
            const el = document.querySelector(`.choice[data-val="${keyMap[e.key.toLowerCase()]}"]`);
            if (el && document.querySelector(`.question[data-q="${current}"].active`)) {
                const group = el.dataset.group;
                document.querySelectorAll(`.choice[data-group="${group}"]`).forEach(c => c.classList.remove('selected'));
                el.classList.add('selected');
            }
        }
    });

    // ── Récapitulatif ──
    const RECAP_LABELS = {
        prenom:'Prénom', age:'Âge', sexe:'Sexe', poids:'Poids', taille:'Taille',
        activite:'Niveau d\'activité', stress_level:'Stress', activity_type:'Sport principal',
        duration_minutes:'Durée séance', daily_steps:'Pas / jour', hydration_level:'Hydratation',
        smoking_status:'Tabac', objectif:'Objectifs', restrictions:'Restrictions',
        allergies:'Allergies', duree:'Durée du plan', repas:'Repas / jour',
    };
    const RECAP_UNITS = {
        age:' ans', poids:' kg', taille:' cm', duration_minutes:' min',
        daily_steps:' pas', hydration_level:' L/j', duree:' jour(s)', repas:' repas/j', stress_level:'/10',
    };

    function showRecap() {
        if (!validate(12)) return;
        const data = collectData();
        const content = document.getElementById('recap-content');
        content.innerHTML = Object.entries(data)
            .filter(([, v]) => v !== '' && v !== null && v !== undefined)
            .map(([k, v]) => `
                <div style="background:var(--bg-soft);border:1px solid var(--border);border-radius:14px;padding:.9rem 1.1rem;">
                    <div style="font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:.3rem;font-weight:700">${RECAP_LABELS[k] || k}</div>
                    <div style="font-size:.95rem;font-weight:800;color:var(--ink)">${v}${RECAP_UNITS[k] || ''}</div>
                </div>
            `).join('');
        document.getElementById('recap-screen').style.display = 'flex';
    }

    function hideRecap() {
        document.getElementById('recap-screen').style.display = 'none';
    }

    // Focus premier input
    focusQuestion(0);
    updateWrapHeight();