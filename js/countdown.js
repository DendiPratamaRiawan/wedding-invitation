/* Hitung mundur menuju acara pertama. Data waktu berasal dari admin (bukan ditulis di kode). */
(function () {
  'use strict';

  var box = document.getElementById('countdown');
  var dataEl = document.getElementById('invitation-data');
  if (!box || !dataEl) return;

  var data = JSON.parse(dataEl.textContent);
  var target = null;
  (data.events || []).some(function (ev) {
    if (ev.date && ev.start) {
      target = new Date(ev.date + 'T' + ev.start + ':00' + ev.offset).getTime();
      return true;
    }
    return false;
  });
  if (!target || isNaN(target)) {
    box.hidden = true;
    return;
  }

  var units = {};
  box.querySelectorAll('[data-unit]').forEach(function (el) { units[el.getAttribute('data-unit')] = el; });
  var timer = null;

  function pad(n) { return n < 10 ? '0' + n : String(n); }

  function tick() {
    var diff = target - Date.now();
    if (diff <= 0) {
      box.hidden = true;
      document.getElementById('countdown-done').hidden = false;
      stop();
      return;
    }
    var s = Math.floor(diff / 1000);
    units.days.textContent = Math.floor(s / 86400);
    units.hours.textContent = pad(Math.floor((s % 86400) / 3600));
    units.minutes.textContent = pad(Math.floor((s % 3600) / 60));
    units.seconds.textContent = pad(s % 60);
  }

  function start() { if (!timer) { tick(); timer = setInterval(tick, 1000); } }
  function stop() { clearInterval(timer); timer = null; }

  tick();
  // Hanya berdetak saat terlihat & tab aktif
  var inView = true;
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      inView = entries[0].isIntersecting;
      inView && !document.hidden ? start() : stop();
    }).observe(box);
  } else {
    start();
  }
  document.addEventListener('visibilitychange', function () {
    document.hidden ? stop() : (inView && start());
  });
})();
