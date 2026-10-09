/* Interaksi umum halaman undangan: sampul, animasi muncul, musik, salin, toast. */
(function () {
  'use strict';

  var root = document.documentElement;
  root.classList.remove('no-js');
  root.classList.add('js');

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Toast ---------- */
  var toastTimer;
  window.showToast = function (message) {
    var el = document.getElementById('toast');
    if (!el) return;
    el.textContent = message;
    el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.hidden = true; }, 2600);
  };

  /* ---------- Salin teks ---------- */
  window.copyText = function (text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy') ? resolve() : reject(); } catch (e) { reject(e); }
      document.body.removeChild(ta);
    });
  };

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-copy]');
    if (!btn) return;
    window.copyText(btn.getAttribute('data-copy')).then(
      function () { window.showToast('Nomor berhasil disalin'); },
      function () { window.showToast('Gagal menyalin, silakan salin manual'); }
    );
  });

  /* ---------- Animasi saat terlihat ---------- */
  var reveals = document.querySelectorAll('.reveal');
  var animated = document.querySelectorAll('.night, .bloom-zone');

  // Kelopak bunga berjatuhan (dimatikan bila pengguna memilih kurangi gerakan)
  var petals = document.querySelector('.petals');
  if (petals && !reduceMotion) petals.classList.add('is-on');

  if ('IntersectionObserver' in window && !reduceMotion) {
    var revealObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObs.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    reveals.forEach(function (el) { revealObs.observe(el); });

    // Animasi dekoratif (bintang, halo, bunga) hanya berjalan saat bagiannya terlihat
    var loopObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        entry.target.classList.toggle('in-view', entry.isIntersecting);
      });
    });
    animated.forEach(function (el) { loopObs.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('is-visible'); });
    animated.forEach(function (el) { el.classList.add('in-view'); });
  }

  /* ---------- Buka sampul ---------- */
  var cover = document.getElementById('cover');
  var openBtn = document.getElementById('open-invitation');
  var musicBtn = document.getElementById('music-toggle');

  function openInvitation() {
    document.body.classList.remove('is-locked');
    if (musicBtn) musicBtn.hidden = false;
    // Dipanggil langsung di dalam klik agar browser mengizinkan audio diputar
    if (audio && audio.hasAttribute('data-play-on-open')) playMusic(true);
    document.dispatchEvent(new CustomEvent('invitation:opened'));
    if (reduceMotion) {
      cover.hidden = true;
    } else {
      cover.classList.add('is-opening');
      setTimeout(function () { cover.hidden = true; }, 900);
    }
    var content = document.getElementById('content');
    if (content) {
      content.setAttribute('tabindex', '-1');
      content.focus({ preventScroll: true });
    }
  }

  /* ---------- Musik latar ----------
     Tidak diputar saat halaman dimuat; mulai ketika tamu menekan "Buka Undangan"
     (bila diaktifkan admin) dan selalu bisa dijeda lewat tombol musik. */
  var audio = document.getElementById('music');
  var fadeTimer = null;
  var pausedByUser = false;
  var pausedByHidden = false;

  function setMusicState(playing) {
    if (!musicBtn) return;
    musicBtn.setAttribute('aria-pressed', String(playing));
    musicBtn.setAttribute('aria-label', playing ? 'Jeda musik' : 'Putar musik');
  }

  function playMusic(fade) {
    clearInterval(fadeTimer);
    var target = 0.8;
    audio.volume = fade && !reduceMotion ? 0 : target;
    var p = audio.play();
    if (p && p.catch) {
      p.catch(function () { setMusicState(false); });
    }
    setMusicState(true);
    if (fade && !reduceMotion) {
      // Volume naik perlahan selama ±2 detik
      fadeTimer = setInterval(function () {
        audio.volume = Math.min(target, audio.volume + 0.04);
        if (audio.volume >= target) clearInterval(fadeTimer);
      }, 100);
    }
  }

  function pauseMusic() {
    clearInterval(fadeTimer);
    audio.pause();
    setMusicState(false);
  }

  if (audio && musicBtn) {
    musicBtn.addEventListener('click', function () {
      if (audio.paused) {
        pausedByUser = false;
        playMusic(false);
      } else {
        pausedByUser = true;
        pauseMusic();
      }
    });
    // Jeda saat tab/aplikasi ditinggal, lanjut saat kembali (kecuali tamu sendiri yang menjeda)
    document.addEventListener('visibilitychange', function () {
      if (document.hidden && !audio.paused) {
        pausedByHidden = true;
        pauseMusic();
      } else if (!document.hidden && pausedByHidden && !pausedByUser) {
        pausedByHidden = false;
        playMusic(true);
      }
    });
    audio.addEventListener('error', function () {
      setMusicState(false);
      musicBtn.hidden = true;
    });
  }

  if (cover && openBtn) {
    openBtn.addEventListener('click', openInvitation);
  } else {
    document.body.classList.remove('is-locked');
  }
})();
