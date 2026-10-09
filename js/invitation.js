/* Fitur undangan: kalender, galeri, RSVP, ucapan & doa. */
(function () {
  'use strict';

  var dataEl = document.getElementById('invitation-data');
  if (!dataEl) return;
  var data = JSON.parse(dataEl.textContent);

  function api(url, body) {
    var opts = { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' };
    if (body) {
      opts.method = 'POST';
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    return fetch(url, opts).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (json) {
        if (!res.ok || json.ok === false) throw new Error(json.error || 'Terjadi kesalahan. Coba lagi.');
        return json;
      });
    });
  }

  /* Catat bahwa tautan personal dibuka (hanya setelah tamu menekan "Buka Undangan") */
  document.addEventListener('invitation:opened', function () {
    if (data.code && !data.preview) {
      api('api/rsvp.php?action=opened', { code: data.code }).catch(function () {});
    }
  });

  /* ---------- Kalender ---------- */
  function eventTimes(ev) {
    var start = new Date(ev.date + 'T' + ev.start + ':00' + ev.offset);
    var end = ev.end ? new Date(ev.date + 'T' + ev.end + ':00' + ev.offset) : new Date(start.getTime() + 2 * 3600 * 1000);
    return { start: start, end: end };
  }
  function utcStamp(d) { return d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, ''); }
  function eventTitle(ev) { return ev.type + ' ' + data.couple; }
  function eventLocation(ev) { return [ev.venue, ev.address].filter(Boolean).join(', ').replace(/\n/g, ', '); }

  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-calendar]');
    document.querySelectorAll('.cal-menu__list').forEach(function (list) {
      if (!toggle || list !== toggle.nextElementSibling) {
        list.hidden = true;
        list.previousElementSibling.setAttribute('aria-expanded', 'false');
      }
    });
    if (toggle) {
      var list = toggle.nextElementSibling;
      list.hidden = !list.hidden;
      toggle.setAttribute('aria-expanded', String(!list.hidden));
      return;
    }

    var g = e.target.closest('[data-cal-google]');
    if (g) {
      var ev = data.events[+g.getAttribute('data-cal-google')];
      var t = eventTimes(ev);
      g.href = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
        + '&text=' + encodeURIComponent(eventTitle(ev))
        + '&dates=' + utcStamp(t.start) + '/' + utcStamp(t.end)
        + '&location=' + encodeURIComponent(eventLocation(ev))
        + '&details=' + encodeURIComponent('Undangan pernikahan ' + data.couple + '\n' + location.href);
      return; // biarkan tautan terbuka di tab baru
    }

    var ics = e.target.closest('[data-cal-ics]');
    if (ics) {
      e.preventDefault();
      var ev2 = data.events[+ics.getAttribute('data-cal-ics')];
      var t2 = eventTimes(ev2);
      var esc = function (s) { return String(s).replace(/([,;\\])/g, '\\$1').replace(/\n/g, '\\n'); };
      var body = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Undangan//ID', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
        'UID:' + utcStamp(t2.start) + '-' + Math.random().toString(36).slice(2) + '@undangan',
        'DTSTAMP:' + utcStamp(new Date()),
        'DTSTART:' + utcStamp(t2.start), 'DTEND:' + utcStamp(t2.end),
        'SUMMARY:' + esc(eventTitle(ev2)), 'LOCATION:' + esc(eventLocation(ev2)),
        'DESCRIPTION:' + esc('Undangan pernikahan ' + data.couple),
        'END:VEVENT', 'END:VCALENDAR'
      ].join('\r\n');
      var url = URL.createObjectURL(new Blob([body], { type: 'text/calendar;charset=utf-8' }));
      var a = document.createElement('a');
      a.href = url;
      a.download = ev2.type.toLowerCase().replace(/\s+/g, '-') + '.ics';
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }
  });

  /* ---------- Galeri + lightbox ---------- */
  var gallery = document.getElementById('gallery');
  var lb = document.getElementById('lightbox');
  if (gallery && lb && typeof lb.showModal === 'function') {
    var items = Array.prototype.slice.call(gallery.querySelectorAll('.gallery__item'));
    var img = document.getElementById('lightbox-img');
    var cap = document.getElementById('lightbox-cap');
    var current = 0;
    var show = function (i) {
      current = (i + items.length) % items.length;
      img.src = items[current].getAttribute('href');
      img.alt = items[current].querySelector('img').alt;
      cap.textContent = items[current].getAttribute('data-caption') || '';
    };
    gallery.addEventListener('click', function (e) {
      var item = e.target.closest('.gallery__item');
      if (!item) return;
      e.preventDefault();
      show(+item.getAttribute('data-index'));
      lb.showModal();
    });
    lb.addEventListener('click', function (e) {
      var act = e.target.closest('[data-lb]');
      if (act) {
        var a = act.getAttribute('data-lb');
        if (a === 'close') lb.close();
        if (a === 'prev') show(current - 1);
        if (a === 'next') show(current + 1);
      } else if (e.target === lb) {
        lb.close();
      }
    });
    lb.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') show(current - 1);
      if (e.key === 'ArrowRight') show(current + 1);
    });
    var touchX = null;
    lb.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) {
      if (touchX === null) return;
      var dx = e.changedTouches[0].clientX - touchX;
      if (Math.abs(dx) > 50) show(current + (dx < 0 ? 1 : -1));
      touchX = null;
    });
  }

  /* ---------- Bantuan formulir ---------- */
  function setBusy(form, busy) {
    var btn = form.querySelector('[type="submit"]');
    btn.disabled = busy;
    if (busy) { btn.dataset.label = btn.textContent; btn.textContent = 'Mengirim…'; }
    else if (btn.dataset.label) { btn.textContent = btn.dataset.label; }
  }
  function showError(form, msg) {
    var el = form.querySelector('.form__error');
    el.textContent = msg || '';
    el.hidden = !msg;
  }

  /* ---------- RSVP ---------- */
  var rsvpForm = document.getElementById('rsvp-form');
  if (rsvpForm) {
    var countField = document.getElementById('rsvp-count-field');
    var done = document.getElementById('rsvp-done');
    var doneText = document.getElementById('rsvp-done-text');
    var labels = { hadir: 'Insya Allah hadir', tidak_hadir: 'Tidak dapat hadir', ragu: 'Belum pasti' };

    var syncCount = function () {
      var checked = rsvpForm.querySelector('[name="status"]:checked');
      countField.hidden = !checked || checked.value !== 'hadir' || data.maxGuests <= 1;
    };
    rsvpForm.addEventListener('change', syncCount);

    var showDone = function (r) {
      var text = 'Konfirmasi Anda: ' + labels[r.status];
      if (r.status === 'hadir') text += ' (' + r.guest_count + ' orang)';
      text += '. Jazakumullahu khairan.';
      doneText.textContent = text;
      rsvpForm.hidden = true;
      done.hidden = false;
    };

    api('api/rsvp.php?action=mine&code=' + encodeURIComponent(data.code)).then(function (res) {
      if (!res.rsvp) return;
      var radio = rsvpForm.querySelector('[name="status"][value="' + res.rsvp.status + '"]');
      if (radio) radio.checked = true;
      if (res.rsvp.guest_count > 0) rsvpForm.guest_count.value = res.rsvp.guest_count;
      rsvpForm.message.value = res.rsvp.message || '';
      syncCount();
      showDone(res.rsvp);
    }).catch(function () {});

    document.getElementById('rsvp-edit').addEventListener('click', function () {
      done.hidden = true;
      rsvpForm.hidden = false;
    });

    rsvpForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var status = rsvpForm.querySelector('[name="status"]:checked');
      if (!status) { showError(rsvpForm, 'Silakan pilih status kehadiran.'); return; }
      showError(rsvpForm, '');
      setBusy(rsvpForm, true);
      api('api/rsvp.php?action=submit', {
        code: data.code,
        preview: data.preview,
        status: status.value,
        guest_count: status.value === 'hadir' ? +(rsvpForm.guest_count.value || 1) : 0,
        message: rsvpForm.message.value.trim()
      }).then(showDone).catch(function (err) {
        showError(rsvpForm, err.message);
      }).then(function () { setBusy(rsvpForm, false); });
    });
  }

  /* ---------- Ucapan & doa ---------- */
  var wishForm = document.getElementById('wish-form');
  var wishList = document.getElementById('wish-list');
  var moreBtn = document.getElementById('wish-more');
  if (wishForm && wishList) {
    var offset = 0;
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var fmt = function (s) {
      var d = new Date(String(s).replace(' ', 'T') + '+07:00');
      return isNaN(d) ? '' : d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    };
    var renderWish = function (w) {
      // Semua teks dimasukkan via textContent untuk mencegah XSS
      var li = document.createElement('li');
      li.className = 'wish';
      var name = document.createElement('p'); name.className = 'wish__name'; name.textContent = w.guest_name;
      var date = document.createElement('span'); date.className = 'wish__date'; date.textContent = fmt(w.created_at);
      var msg = document.createElement('p'); msg.className = 'wish__msg'; msg.textContent = w.message;
      li.appendChild(name); li.appendChild(date); li.appendChild(msg);
      return li;
    };
    var load = function () {
      api('api/wishes.php?offset=' + offset + (data.preview ? '&preview=1' : '')).then(function (res) {
        if (offset === 0) wishList.innerHTML = '';
        if (offset === 0 && !res.wishes.length) {
          var empty = document.createElement('li');
          empty.className = 'wishes__empty';
          empty.textContent = 'Jadilah yang pertama mengirimkan doa untuk kedua mempelai.';
          wishList.appendChild(empty);
        }
        res.wishes.forEach(function (w) { wishList.appendChild(renderWish(w)); });
        offset += res.wishes.length;
        moreBtn.hidden = offset >= res.total;
      }).catch(function () {});
    };
    load();
    moreBtn.addEventListener('click', load);

    var counter = document.getElementById('wish-count');
    wishForm.message.addEventListener('input', function () { counter.textContent = wishForm.message.value.length; });

    wishForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var name = wishForm.name.value.trim();
      var message = wishForm.message.value.trim();
      var ok = wishForm.querySelector('.form__success');
      ok.hidden = true;
      if (name.length < 2) { showError(wishForm, 'Nama minimal 2 karakter.'); return; }
      if (message.length < 3) { showError(wishForm, 'Ucapan minimal 3 karakter.'); return; }
      showError(wishForm, '');
      setBusy(wishForm, true);
      api('api/wishes.php?action=submit', {
        name: name, message: message, code: data.code, preview: data.preview, website: wishForm.website.value
      }).then(function (res) {
        wishForm.message.value = '';
        counter.textContent = '0';
        ok.textContent = res.pending
          ? 'Terima kasih! Ucapan Anda akan tampil setelah ditinjau.'
          : 'Terima kasih atas doa dan ucapannya.';
        ok.hidden = false;
        if (!res.pending) { offset = 0; load(); }
      }).catch(function (err) {
        showError(wishForm, err.message);
      }).then(function () { setBusy(wishForm, false); });
    });
  }
})();
