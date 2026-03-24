document.addEventListener('DOMContentLoaded', () => {
    const form          = document.getElementById('contact-form');
    const submitBtn     = document.getElementById('contact-submit');
    const feedbackBlock = document.getElementById('form-feedback');

    if (!form) return;

    const errorElems = {
        nom: document.querySelector('[data-error-for="nom"]'),
        email: document.querySelector('[data-error-for="email"]'),
        sujet: document.querySelector('[data-error-for="sujet"]'),
        message: document.querySelector('[data-error-for="message"]'),
    };

    function clearErrors() {
        for (const key in errorElems) {
            if (errorElems[key]) {
                errorElems[key].textContent = '';
            }
        }
        if (feedbackBlock) {
            feedbackBlock.textContent = '';
            feedbackBlock.style.color = '';
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();

        submitBtn.disabled = true;
        const originalLabel = submitBtn.textContent;
        submitBtn.textContent = 'Envoi en cours...';

        const formData = new FormData(form);

        try {
            const response = await fetch('contact.php?ajax=contact', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (!data.ok) {
                // erreurs de validation champs
                if (data.errors) {
                    Object.entries(data.errors).forEach(([field, message]) => {
                        if (errorElems[field]) {
                            errorElems[field].textContent = message;
                        }
                    });
                }
                // erreur globale
                if (data.error && feedbackBlock) {
                    feedbackBlock.textContent = data.error;
                    feedbackBlock.style.color = '#ef4444';
                }
            } else {
                // succès
                if (feedbackBlock) {
                    feedbackBlock.textContent = data.message || 'Message envoyé avec succès.';
                    feedbackBlock.style.color = '#22c55e';
                }
                form.reset();
            }
        } catch (err) {
            if (feedbackBlock) {
                feedbackBlock.textContent = 'Erreur réseau, vérifie ta connexion.';
                feedbackBlock.style.color = '#ef4444';
            }
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalLabel;
        }
    });
});
