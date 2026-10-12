(() => {
  if (document.querySelector('.liva-app-shell')) {
    document.body.classList.add('liva-has-app-shell');
  }
  const nav = document.querySelector('.liva-primary-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
    });
  }
  document.addEventListener('input', (event) => {
    const form = event.target.closest('.liva-edit-question-form');
    if (form && event.target.classList.contains('liva-option-input')) {
      updateCorrectHint(form);
    }
  });

  document.addEventListener('change', (event) => {
    const form = event.target.closest('.liva-edit-question-form');
    if (form && event.target.classList.contains('liva-correct-radio')) {
      updateCorrectHint(form);
    }
  });

  function updateCorrectHint(form) {
    const checked = form.querySelector('.liva-correct-radio:checked');
    const hint = form.querySelector('.liva-correct-hint');
    if (!checked || !hint) return;
    const row = checked.closest('.liva-choice');
    const text = row ? row.querySelector('.liva-option-input').value : '';
    hint.textContent = 'Currently correct: ' + checked.dataset.letter + '. ' + (text || '—');
  }

  document.addEventListener('submit', (event) => {
    const form = event.target.closest('.liva-confirm-delete');
    if (form && !window.confirm(form.dataset.confirm || 'Are you sure?')) {
      event.preventDefault();
    }
  });

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-liva-modal-open]');
    if (trigger) {
      const modal = document.getElementById(trigger.dataset.livaModalOpen);
      if (modal) { modal.hidden = false; document.body.classList.add('liva-modal-open'); }
    }
    const close = event.target.closest('[data-liva-modal-close]');
    if (close) {
      const modal = close.closest('.liva-modal-backdrop');
      if (modal) { modal.hidden = true; document.body.classList.remove('liva-modal-open'); }
    }
  });

  // Automatically dismiss notifications after 3 seconds with a smooth fade-out
  const notifications = document.querySelectorAll('.liva-form-success, .liva-form-error');
  if (notifications.length > 0) {
    setTimeout(() => {
      notifications.forEach((notice) => {
        notice.style.transition = 'opacity 0.4s ease, transform 0.4s ease, margin 0.4s ease';
        notice.style.opacity = '0';
        notice.style.transform = 'translateY(-6px)';
        setTimeout(() => notice.remove(), 400);
      });

      // Clear the query parameters from the browser address bar to avoid re-triggering on page refresh
      if (window.history.replaceState && (location.search.includes('liva_status') || location.search.includes('liva_message'))) {
        const cleanUrl = new URL(window.location);
        cleanUrl.searchParams.delete('liva_status');
        cleanUrl.searchParams.delete('liva_message');
        window.history.replaceState({}, document.title, cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);
      }
    }, 3000);
  }
})();