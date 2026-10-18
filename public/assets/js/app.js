document.addEventListener('DOMContentLoaded', function () {
  const countdownEl = document.getElementById('countdown');
  const dateValue = countdownEl?.dataset.targetDate;

  if (countdownEl && dateValue) {
    const eventDate = new Date(dateValue);

    function updateCountdown() {
      const diff = eventDate.getTime() - Date.now();
      if (diff <= 0) {
        countdownEl.innerHTML = '<span class="text-gold">Event Live</span>';
        return;
      }

      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
      const minutes = Math.floor((diff / (1000 * 60)) % 60);
      const seconds = Math.floor((diff / 1000) % 60);

      countdownEl.innerHTML = [
        '<div class="flex gap-3 text-xs uppercase text-slate-300">',
        '<span class="rounded-full bg-white/5 px-3 py-2">'+days+'d</span>',
        '<span class="rounded-full bg-white/5 px-3 py-2">'+hours+'h</span>',
        '<span class="rounded-full bg-white/5 px-3 py-2">'+minutes+'m</span>',
        '<span class="rounded-full bg-white/5 px-3 py-2">'+seconds+'s</span>',
        '</div>'
      ].join('');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
  }

  const statEls = document.querySelectorAll('[data-number]');
  statEls.forEach((el) => {
    const target = Number(el.dataset.number || 0);
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    let current = 0;
    const interval = setInterval(() => {
      current += Math.max(1, Math.ceil(target / 25));
      if (current >= target) {
        current = target;
        clearInterval(interval);
      }
      el.textContent = prefix + current + suffix;
    }, 40);
  });
});
