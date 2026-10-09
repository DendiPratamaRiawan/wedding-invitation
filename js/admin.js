/* Utilitas bersama panel admin. */
(function () {
  'use strict';

  var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  var Admin = window.Admin = {};

  Admin.api = function (url, body, opts) {
    opts = opts || {};
    var init = { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrf } };
    if (body instanceof FormData) {
      init.method = 'POST';
      init.body = body;
    } else if (body !== undefined) {
      init.method = 'POST';
      init.headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(body);
    }
    return fetch('../api/' + url, init).then(function (res) {
      if (res.status === 401) {
        location.href = 'login.php';
        throw new Error('Sesi berakhir.');
      }
      return res.json().catch(function () { return { ok: false, error: 'Respons server tidak valid.' }; }).then(function (json) {
        if (!res.ok || json.ok === false) {
          var err = new Error(json.error || 'Terjadi kesalahan.');
          err.data = json;
          err.status = res.status;
          throw err;
        }
        return json;
      });
    });
  };

  var toastTimer;
  Admin.toast = function (msg, type) {
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast' + (type ? ' toast--' + type : '');
    el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.hidden = true; }, 3200);
  };

  Admin.confirm = function (text, okLabel) {
    var dlg = document.getElementById('confirm-dialog');
    document.getElementById('confirm-text').textContent = text;
    document.getElementById('confirm-ok').textContent = okLabel || 'Ya, lanjutkan';
    dlg.returnValue = '';
    dlg.showModal();
    return new Promise(function (resolve) {
      dlg.addEventListener('close', function handler() {
        dlg.removeEventListener('close', handler);
        resolve(dlg.returnValue === 'ok');
      });
    });
  };

  Admin.copy = function (text, okMsg) {
    var done = function () { Admin.toast(okMsg || 'Disalin ke clipboard', 'success'); };
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text).then(done, function () { Admin.toast('Gagal menyalin', 'error'); });
    }
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    ta.remove();
    ok ? done() : Admin.toast('Gagal menyalin', 'error');
    return Promise.resolve();
  };

  /** Buat elemen dengan aman (teks selalu lewat textContent). */
  Admin.el = function (tag, attrs, children) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      var v = attrs[k];
      if (v === null || v === undefined || v === false) return;
      if (k === 'text') node.textContent = v;
      else if (k === 'className') node.className = v;
      else if (k.indexOf('on') === 0) node.addEventListener(k.slice(2), v);
      else node.setAttribute(k, v === true ? '' : v);
    });
    (children || []).forEach(function (c) {
      if (c === null || c === undefined || c === false) return;
      node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
    });
    return node;
  };

  Admin.formatDate = function (s, withTime) {
    if (!s) return '—';
    var d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d)) return s;
    var m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var out = d.getDate() + ' ' + m[d.getMonth()] + ' ' + d.getFullYear();
    if (withTime) out += ', ' + String(d.getHours()).padStart(2, '0') + '.' + String(d.getMinutes()).padStart(2, '0');
    return out;
  };

  Admin.rsvpLabel = { hadir: 'Hadir', tidak_hadir: 'Tidak hadir', ragu: 'Belum pasti' };

  Admin.waMessage = function (template, guest, couple) {
    return template
      .split('[Nama Tamu]').join(guest.guest_name)
      .split('[Tautan Undangan]').join(guest.link)
      .split('[Nama Mempelai]').join(couple);
  };

  Admin.waUrl = function (message, phone) {
    return 'https://wa.me/' + (phone || '') + '?text=' + encodeURIComponent(message);
  };

  Admin.loadScript = function (src) {
    return new Promise(function (resolve, reject) {
      if (document.querySelector('script[src="' + src + '"]')) return resolve();
      var s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = function () { reject(new Error('Gagal memuat ' + src)); };
      document.head.appendChild(s);
    });
  };

  // Navigasi & keluar
  var menuBtn = document.getElementById('menu-btn');
  var sidebar = document.getElementById('sidebar');
  if (menuBtn) {
    menuBtn.addEventListener('click', function () {
      var open = sidebar.classList.toggle('open');
      menuBtn.setAttribute('aria-expanded', String(open));
    });
  }
  var logout = document.getElementById('logout-btn');
  if (logout) {
    logout.addEventListener('click', function () {
      Admin.api('auth.php?action=logout', {}).then(function () { location.href = 'login.php'; })
        .catch(function () { location.href = 'login.php'; });
    });
  }
})();
