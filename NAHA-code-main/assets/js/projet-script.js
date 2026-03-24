// projet-script.js

document.addEventListener('DOMContentLoaded', () => {

  /* =========================================================
   * 1) FEEDBACK AJAX (projet.php?ajax=feedback)
   * =========================================================*/
  const feedbackForm = document.getElementById('feedback-form');
  const feedbackMsg  = document.getElementById('feedback-status');

  if (feedbackForm && feedbackMsg) {

    feedbackForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const formData = new FormData(feedbackForm);
      const message  = (formData.get('message') || '').toString().trim();

      if (!message) {
        feedbackMsg.textContent = 'Écris au moins une petite phrase 😉';
        feedbackMsg.style.color = '#b91c1c';
        return;
      }

      feedbackMsg.textContent = 'Envoi en cours...';
      feedbackMsg.style.color = '#4b5563';

      fetch('projet.php?ajax=feedback', {
        method: 'POST',
        body: formData,
      })
          .then(res => res.json())
          .then(data => {
            if (data.ok) {
              feedbackMsg.textContent = data.msg || 'Merci pour ton retour 🙌';
              feedbackMsg.style.color = '#15803d';
              feedbackForm.reset();
            } else {
              feedbackMsg.textContent = data.error || 'Erreur lors de l’envoi.';
              feedbackMsg.style.color = '#b91c1c';
            }
          })
          .catch(err => {
            console.error(err);
            feedbackMsg.textContent = 'Erreur réseau. Réessaie dans un instant.';
            feedbackMsg.style.color = '#b91c1c';
          });
    });
  }

  /* =========================================================
   * 2) VISION : cartes interactives (hover + pin au clic)
   * =========================================================*/
  const visionCards = document.querySelectorAll('.vision-card');

  if (visionCards.length > 0) {
    visionCards.forEach((card) => {

      // Animation au survol
      card.addEventListener('mouseenter', () => {
        visionCards.forEach(c => c.classList.remove('is-hovered'));
        card.classList.add('is-hovered');
      });

      card.addEventListener('mouseleave', () => {
        card.classList.remove('is-hovered');
      });

      // Épinglage au clic
      card.addEventListener('click', () => {
        const alreadyPinned = card.classList.contains('is-pinned');

        visionCards.forEach(c => c.classList.remove('is-pinned'));

        if (!alreadyPinned) {
          card.classList.add('is-pinned');
        }
      });
    });
  }

  /* =========================================================
   * 3) ÉQUIPE : cartes interactives (hover + focus au clic)
   * =========================================================*/
  const teamCards = document.querySelectorAll('.team-card');

  if (teamCards.length > 0) {
    teamCards.forEach((card) => {

      // Survol joli
      card.addEventListener('mouseenter', () => {
        card.classList.add('is-hovered');
      });

      card.addEventListener('mouseleave', () => {
        card.classList.remove('is-hovered');
      });

      // Clic : focus sur une seule carte
      card.addEventListener('click', () => {
        const isActive = card.classList.contains('is-active');

        teamCards.forEach(c => c.classList.remove('is-active'));

        if (!isActive) {
          card.classList.add('is-active');
        }
      });
    });
  }

  /* =========================================================
   * 4) SMOOTH SCROLL SUR LES ANCRES
   * =========================================================*/
  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', (e) => {
      const id = link.getAttribute('href');
      if (id && id.length > 1) {
        const target = document.querySelector(id);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

});