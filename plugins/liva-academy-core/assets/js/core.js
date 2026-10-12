(() => {
  document.addEventListener('change', (event) => {
    if (!event.target.matches('.liva-type-option input[type="radio"]')) return;
    const picker = event.target.closest('.liva-type-picker');
    if (!picker) return;
    picker.querySelectorAll('.liva-type-option').forEach(el => el.classList.remove('active'));
    event.target.closest('.liva-type-option')?.classList.add('active');
  });
})();
