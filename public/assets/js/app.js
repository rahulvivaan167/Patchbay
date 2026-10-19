(() => {
  const sidebar = document.querySelector('#sidebar');
  document.querySelector('[data-menu-toggle]')?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
  });

  document.addEventListener('click', async (event) => {
    const copy = event.target.closest('[data-copy]');
    if (copy) {
      try {
        await navigator.clipboard.writeText(copy.dataset.copy || '');
        const before = copy.textContent;
        copy.textContent = 'Copied';
        setTimeout(() => { copy.textContent = before; }, 1300);
      } catch (_) {
        window.prompt('Copy this value:', copy.dataset.copy || '');
      }
    }

    const alertClose = event.target.closest('[data-alert] button');
    if (alertClose) alertClose.closest('[data-alert]')?.remove();
  });

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'Are you sure?')) event.preventDefault();
    });
  });
})();
