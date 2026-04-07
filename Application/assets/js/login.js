const API = window.location.origin + '/IA-NAHA/Application/api';
    // ── Tab switch ──
    function switchTab(tab, btn) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
    }

    // ── Eye toggle ──
    function toggleEye(id, btn) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.textContent = input.type === 'password' ? '👁' : '🙈';
    }

    // ── Password strength ──
    function checkStrength(val) {
        const segs = ['s1','s2','s3','s4'];
        segs.forEach(s => {
            document.getElementById(s).className = 'strength-seg';
        });
        if (!val) return;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const cls = score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
        for (let i = 0; i < score; i++) {
            document.getElementById(segs[i]).classList.add(cls);
        }
    }

    // ── Alert helpers ──
    function showAlert(id, msg, type = 'error') {
        const el = document.getElementById(id);
        el.textContent = msg;
        el.className = `alert alert-${type} show`;
        setTimeout(() => el.classList.remove('show'), 5000);
    }

    function setLoading(btn, spinner, txt, label, loading) {
        document.getElementById(btn).disabled = loading;
        document.getElementById(spinner).style.display = loading ? 'block' : 'none';
        document.getElementById(txt).textContent = loading ? '' : label;
    }

    // ── LOGIN ──
    async function doLogin() {
        const email    = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;

        if (!email || !password) {
            showAlert('login-error', 'Remplis tous les champs.');
            return;
        }

        setLoading('btn-login', 'login-spinner', 'login-txt', 'Se connecter', true);

        try {
            const res = await fetch(`${API}/login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password }),
            });
            const data = await res.json();

            if (data.error) throw new Error(data.error);

            localStorage.setItem('naha_user_id', data.user_id);
            localStorage.setItem('naha_prenom', data.prenom);
            localStorage.setItem('naha_token', data.token);
            showAlert('login-success', '✓ Connexion réussie ! Redirection…', 'success');
            setTimeout(() => {
                window.location.href = `dashboard.html`;
            }, 1200);

        } catch(e) {
            showAlert('login-error', e.message || 'Erreur de connexion.');
        } finally {
            setLoading('btn-login', 'login-spinner', 'login-txt', 'Se connecter', false);
        }
    }

    // ── REGISTER ──
    async function doRegister() {
        const prenom   = document.getElementById('reg-prenom').value.trim();
        const nom      = document.getElementById('reg-nom').value.trim();
        const email    = document.getElementById('reg-email').value.trim();
        const password = document.getElementById('reg-password').value;
        const confirm  = document.getElementById('reg-confirm').value;
        const terms    = document.getElementById('terms-box').classList.contains('checked');

        if (!prenom || !nom || !email || !password) {
            showAlert('register-error', 'Remplis tous les champs.');
            return;
        }
        if (password !== confirm) {
            showAlert('register-error', 'Les mots de passe ne correspondent pas.');
            return;
        }
        if (password.length < 8) {
            showAlert('register-error', 'Le mot de passe doit faire au moins 8 caractères.');
            return;
        }
        if (!terms) {
            showAlert('register-error', 'Accepte les conditions d\'utilisation.');
            return;
        }

        setLoading('btn-register', 'register-spinner', 'register-txt', 'Créer mon compte', true);

        try {
            const res = await fetch(`${API}/register.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prenom, nom, email, password }),
            });
            const data = await res.json();

            if (data.error) throw new Error(data.error);

            showAlert('register-success', '✓ Compte créé ! Redirection vers le formulaire…', 'success');
            setTimeout(() => {
                localStorage.setItem('naha_user_id', data.user_id);
                localStorage.setItem('naha_token', data.token);
                window.location.href = `onboarding.html`;
            }, 1500);

        } catch(e) {
            showAlert('register-error', e.message || 'Erreur lors de l\'inscription.');
        } finally {
            setLoading('btn-register', 'register-spinner', 'register-txt', 'Créer mon compte', false);
        }
    }

    // ── Enter key support ──
    document.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            const active = document.querySelector('.panel.active');
            if (active.id === 'panel-login') doLogin();
            else doRegister();
        }
    });