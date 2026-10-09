/* Pengaturan konten undangan: mempelai, acara, teks, bagian, media, tema, template WA, publikasi. */
(function () {
  'use strict';
  var el = Admin.el;
  var $ = function (id) { return document.getElementById(id); };
  var form = $('settings-form');
  var S = { draft: null, dirty: false, timezones: {}, checks: {}, presets: {}, gallery: [], status: {} };

  /* ---------- Util path ---------- */
  function getPath(obj, path) {
    return path.split('.').reduce(function (o, k) { return o == null ? undefined : o[k]; }, obj);
  }
  function setPath(obj, path, value) {
    var keys = path.split('.');
    var last = keys.pop();
    var target = keys.reduce(function (o, k) { if (o[k] == null) o[k] = {}; return o[k]; }, obj);
    target[last] = value;
  }

  function markDirty() {
    S.dirty = true;
    $('save-state').textContent = 'Ada perubahan yang belum disimpan';
    $('savebar').classList.add('is-dirty');
  }
  function markClean() {
    S.dirty = false;
    $('save-state').textContent = 'Semua perubahan tersimpan';
    $('savebar').classList.remove('is-dirty');
  }
  window.addEventListener('beforeunload', function (e) {
    if (S.dirty) { e.preventDefault(); e.returnValue = ''; }
  });

  /* ---------- Bidang sederhana ---------- */
  function fillFields() {
    form.querySelectorAll('[data-field]').forEach(function (input) {
      var v = getPath(S.draft, input.getAttribute('data-field'));
      if (input.type === 'checkbox') input.checked = !!v;
      else input.value = v == null ? '' : v;
    });
  }
  function collectFields() {
    form.querySelectorAll('[data-field]').forEach(function (input) {
      var path = input.getAttribute('data-field');
      var v = input.type === 'checkbox' ? input.checked : (input.type === 'number' ? (input.value === '' ? 0 : +input.value) : input.value);
      setPath(S.draft, path, v);
    });
  }
  form.addEventListener('input', function (e) {
    if (e.target.closest('#password-form')) return;
    markDirty();
    if (e.target.id === 'wa-template') renderWaPreview();
  });
  form.addEventListener('change', markDirty);

  /* ---------- Daftar berulang (acara, kisah, rekening) ---------- */
  function repeater(container, items, fields, opts) {
    var box = $(container);
    box.innerHTML = '';
    if (!items.length) box.appendChild(el('p', { className: 'muted', text: opts.empty }));
    items.forEach(function (item, i) {
      var card = el('div', { className: 'repeat-card' });
      var head = el('div', { className: 'repeat-card__head' }, [
        el('strong', { text: opts.title(item, i) }),
        el('div', { className: 'row-actions' }, [
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: '↑', title: 'Naikkan', disabled: i === 0,
            onclick: function () { items.splice(i - 1, 0, items.splice(i, 1)[0]); markDirty(); opts.rerender(); } }),
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: '↓', title: 'Turunkan', disabled: i === items.length - 1,
            onclick: function () { items.splice(i + 1, 0, items.splice(i, 1)[0]); markDirty(); opts.rerender(); } }),
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost text-danger', text: 'Hapus',
            onclick: function () {
              Admin.confirm('Hapus "' + opts.title(item, i) + '"?', 'Hapus').then(function (ok) {
                if (ok) { items.splice(i, 1); markDirty(); opts.rerender(); }
              });
            } })
        ])
      ]);
      card.appendChild(head);
      var grid = el('div', { className: 'repeat-card__grid' });
      fields.forEach(function (f) {
        var input;
        if (f.type === 'select') {
          input = el('select');
          Object.keys(f.options).forEach(function (k) { input.appendChild(el('option', { value: k, text: f.options[k] })); });
        } else if (f.type === 'textarea') {
          input = el('textarea', { rows: f.rows || 2, maxlength: f.max });
        } else {
          input = el('input', { type: f.type || 'text', maxlength: f.max, placeholder: f.placeholder });
        }
        input.value = item[f.key] == null ? '' : item[f.key];
        input.addEventListener('input', function () {
          item[f.key] = input.value === '' && (f.type === 'date' || f.type === 'time') ? null : input.value;
          if (f.onInput) f.onInput(item, input, card);
        });
        var label = el('label', { className: f.wide ? 'span-2' : '' }, [f.label, input]);
        if (f.after) label.appendChild(f.after(item, input));
        grid.appendChild(label);
      });
      card.appendChild(grid);
      box.appendChild(card);
    });
  }

  function renderEvents() {
    var tz = {};
    Object.keys(S.timezones).forEach(function (k) { tz[k] = S.timezones[k].label + ' (' + k + ')'; });
    repeater('events', S.draft.events, [
      { key: 'type', label: 'Nama acara *', max: 80, placeholder: 'Akad Nikah' },
      { key: 'date', label: 'Tanggal *', type: 'date' },
      { key: 'start', label: 'Jam mulai *', type: 'time' },
      { key: 'end', label: 'Jam selesai', type: 'time' },
      { key: 'end_label', label: 'Jika tanpa jam selesai, tulis', max: 40, placeholder: 'selesai' },
      { key: 'timezone', label: 'Zona waktu', type: 'select', options: tz },
      { key: 'venue', label: 'Nama tempat *', max: 160, wide: true },
      { key: 'address', label: 'Alamat lengkap *', type: 'textarea', max: 600, wide: true },
      { key: 'maps_url', label: 'Tautan Google Maps', type: 'url', max: 500, wide: true, placeholder: 'https://maps.app.goo.gl/…',
        after: function (item, input) {
          var a = el('a', { className: 'link-small', target: '_blank', rel: 'noopener', text: 'Uji tautan ↗' });
          var sync = function () { a.href = input.value; a.hidden = !/^https?:\/\//.test(input.value); };
          input.addEventListener('input', sync);
          sync();
          return a;
        } }
    ], { title: function (ev) { return ev.type || 'Acara baru'; }, empty: 'Belum ada acara.', rerender: renderEvents });
  }
  $('add-event').addEventListener('click', function () {
    S.draft.events.push({ id: 0, type: '', date: null, start: null, end: null, end_label: '', timezone: 'Asia/Jakarta', venue: '', address: '', maps_url: '' });
    markDirty();
    renderEvents();
  });

  function renderStory() {
    repeater('story', S.draft.content.story, [
      { key: 'title', label: 'Judul', max: 100 },
      { key: 'date_label', label: 'Waktu (bebas)', max: 60, placeholder: 'mis. Juni 2022' },
      { key: 'text', label: 'Cerita', type: 'textarea', rows: 3, max: 800, wide: true }
    ], { title: function (s, i) { return s.title || 'Cerita ' + (i + 1); }, empty: 'Belum ada cerita.', rerender: renderStory });
  }
  $('add-story').addEventListener('click', function () {
    S.draft.content.story.push({ title: '', date_label: '', text: '' });
    markDirty();
    renderStory();
  });

  function renderAccounts() {
    repeater('accounts', S.draft.content.gift.accounts, [
      { key: 'bank', label: 'Bank / dompet digital', max: 60, placeholder: 'BSI, BCA, DANA…' },
      { key: 'number', label: 'Nomor rekening', max: 60 },
      { key: 'holder', label: 'Atas nama', max: 100, wide: true }
    ], { title: function (a, i) { return a.bank || 'Rekening ' + (i + 1); }, empty: 'Belum ada rekening.', rerender: renderAccounts });
  }
  $('add-account').addEventListener('click', function () {
    S.draft.content.gift.accounts.push({ bank: '', number: '', holder: '' });
    markDirty();
    renderAccounts();
  });

  /* ---------- Unggahan foto/audio ---------- */
  function renderUploads() {
    document.querySelectorAll('[data-upload]').forEach(function (box) {
      var kind = box.getAttribute('data-upload');
      var target = box.getAttribute('data-target');
      var current = getPath(S.draft, target);
      box.innerHTML = '';
      if (kind === 'music') {
        box.appendChild(current ? el('audio', { controls: true, preload: 'none', src: '../' + current }) : el('p', { className: 'muted', text: 'Belum ada audio.' }));
      } else {
        box.appendChild(current
          ? el('img', { src: '../' + current, alt: 'Pratinjau foto', className: 'upload__img', width: 120, height: 120 })
          : el('div', { className: 'upload__empty', text: 'Belum ada foto' }));
      }
      var input = el('input', { type: 'file', hidden: true, accept: kind === 'music' ? 'audio/mpeg,audio/mp4,audio/ogg,.mp3,.m4a,.ogg' : 'image/jpeg,image/png,image/webp' });
      input.addEventListener('change', function () {
        if (!input.files[0]) return;
        var fd = new FormData();
        fd.append('kind', kind);
        fd.append('file', input.files[0]);
        Admin.toast('Mengunggah…');
        Admin.api('wedding.php?action=upload', fd).then(function (d) {
          setPath(S.draft, target, d.path);
          if (kind === 'music') {
            // Musik yang baru diunggah langsung diaktifkan
            S.draft.content.sections.music = true;
            $('music-section').checked = true;
          }
          markDirty();
          renderUploads();
          Admin.toast('Berkas diunggah — klik "Simpan" untuk menerapkan', 'success');
        }).catch(function (e) { Admin.toast(e.message, 'error'); });
      });
      var actions = el('div', { className: 'row-actions' }, [
        el('label', { className: 'btn btn-sm btn-ghost file-btn' }, [current ? 'Ganti' : 'Unggah', input]),
        current ? el('button', { type: 'button', className: 'btn btn-sm btn-ghost text-danger', text: 'Hapus',
          onclick: function () { setPath(S.draft, target, ''); markDirty(); renderUploads(); } }) : null
      ]);
      box.appendChild(actions);
    });
  }

  /* ---------- Galeri (langsung tersimpan) ---------- */
  function renderGallery() {
    var box = $('gallery');
    box.innerHTML = '';
    if (!S.gallery.length) box.appendChild(el('p', { className: 'muted', text: 'Belum ada foto galeri.' }));
    S.gallery.forEach(function (g, i) {
      var caption = el('input', { value: g.caption, maxlength: 160, placeholder: 'Keterangan (opsional)', 'aria-label': 'Keterangan foto' });
      caption.addEventListener('change', function () {
        Admin.api('wedding.php?action=gallery', { id: g.id, caption: caption.value }).then(function () { Admin.toast('Keterangan disimpan', 'success'); });
      });
      var move = function (dir) {
        return function () {
          var order = S.gallery.map(function (x) { return x.id; });
          order.splice(i + dir, 0, order.splice(i, 1)[0]);
          Admin.api('wedding.php?action=gallery', { order: order }).then(function (d) { S.gallery = d.gallery; renderGallery(); });
        };
      };
      box.appendChild(el('div', { className: 'gallery-admin__item' }, [
        el('img', { src: '../' + g.thumb_path, alt: g.caption || 'Foto ' + (i + 1), loading: 'lazy', width: 160, height: 160 }),
        caption,
        el('div', { className: 'row-actions' }, [
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: '←', title: 'Geser ke kiri', disabled: i === 0, onclick: move(-1) }),
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: '→', title: 'Geser ke kanan', disabled: i === S.gallery.length - 1, onclick: move(1) }),
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost text-danger', text: 'Hapus', onclick: function () {
            Admin.confirm('Hapus foto ini dari galeri?', 'Hapus').then(function (ok) {
              if (!ok) return;
              Admin.api('wedding.php?action=gallery', { delete: g.id }).then(function (d) { S.gallery = d.gallery; renderGallery(); });
            });
          } })
        ])
      ]));
    });
  }
  $('gallery-input').addEventListener('change', function (e) {
    var files = Array.from(e.target.files);
    e.target.value = '';
    var chain = Promise.resolve();
    files.forEach(function (file, idx) {
      chain = chain.then(function () {
        Admin.toast('Mengunggah foto ' + (idx + 1) + ' dari ' + files.length + '…');
        var fd = new FormData();
        fd.append('kind', 'gallery');
        fd.append('file', file);
        return Admin.api('wedding.php?action=upload', fd).then(function (d) { S.gallery = d.gallery; renderGallery(); })
          .catch(function (err) { Admin.toast(file.name + ': ' + err.message, 'error'); });
      });
    });
    chain.then(function () { if (files.length) Admin.toast('Unggahan selesai', 'success'); });
  });

  /* ---------- Tema ---------- */
  function renderPresets() {
    var box = $('presets');
    box.innerHTML = '';
    Object.keys(S.presets).forEach(function (key) {
      var p = S.presets[key];
      var input = el('input', { type: 'radio', name: 'preset', value: key });
      input.checked = S.draft.theme.preset === key;
      input.addEventListener('change', function () { S.draft.theme.preset = key; markDirty(); });
      var sw = function (c) { var s = el('span', { className: 'swatch' }); s.style.background = c; return s; };
      box.appendChild(el('label', { className: 'preset' }, [input,
        el('span', { className: 'preset__swatches' }, [sw('#25324A'), sw(p.primary), sw(p.accent), sw('#F3E7BE'), sw('#FFF9F1')]),
        el('span', { text: p.label })]));
    });
  }

  /* ---------- Pratinjau pesan WA ---------- */
  function renderWaPreview() {
    var couple = $('settings-form').querySelector('[data-field="groom.name"]').value + ' & ' + $('settings-form').querySelector('[data-field="bride.name"]').value;
    var msg = Admin.waMessage($('wa-template').value, { guest_name: 'Bapak Ahmad Fauzi', link: S.appUrl + '/i/a8F3kQxy' }, couple);
    var box = $('wa-preview');
    box.innerHTML = '';
    // Tampilkan *tebal* seperti di WhatsApp, tetap aman (tanpa innerHTML dari input)
    msg.split(/(\*[^*\n]+\*)/).forEach(function (part) {
      box.appendChild(/^\*[^*\n]+\*$/.test(part) ? el('strong', { text: part.slice(1, -1) }) : document.createTextNode(part));
    });
  }

  /* ---------- Data yang belum lengkap (hanya pengingat, tidak memblokir) ---------- */
  function renderStatus(problems) {
    var list = $('problems');
    list.innerHTML = '';
    (problems || []).forEach(function (p) { list.appendChild(el('li', { text: p })); });
    $('problems-box').hidden = !(problems && problems.length);
  }

  function reload() {
    return Admin.api('wedding.php').then(function (d) {
      S.draft = d.draft;
      S.gallery = d.gallery;
      S.presets = d.presets;
      S.timezones = d.timezones;
      S.checks = d.publish_checks;
      S.status = d.status;
      S.appUrl = d.app_url;
      fillFields();
      renderEvents();
      renderStory();
      renderAccounts();
      renderUploads();
      renderGallery();
      renderPresets();
      renderWaPreview();
      renderStatus(d.problems);
      markClean();
    });
  }

  /* ---------- Simpan ---------- */
  function save() {
    collectFields();
    if (!S.draft.groom.name.trim() || !S.draft.bride.name.trim()) {
      Admin.toast('Nama tampilan kedua mempelai wajib diisi.', 'error');
      return Promise.reject(new Error('invalid'));
    }
    var btn = $('btn-save');
    btn.disabled = true;
    return Admin.api('wedding.php?action=save', S.draft).then(function () {
      Admin.toast('Tersimpan — undangan sudah diperbarui', 'success');
      return reload();
    }).catch(function (e) {
      Admin.toast(e.message, 'error');
      throw e;
    }).finally(function () { btn.disabled = false; });
  }
  form.addEventListener('submit', function (e) { e.preventDefault(); save().catch(function () {}); });

  $('btn-preview').addEventListener('click', function (e) {
    if (S.dirty) {
      e.preventDefault();
      var win = window.open('about:blank', '_blank');
      save().then(function () { win.location = '../'; }).catch(function () { win.close(); });
    }
  });

  /* ---------- Ganti password ---------- */
  $('password-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target;
    if (f.new.value.length < 10) { Admin.toast('Password baru minimal 10 karakter.', 'error'); return; }
    Admin.api('auth.php?action=password', { current: f.current.value, new: f.new.value }).then(function () {
      f.reset();
      Admin.toast('Password diganti', 'success');
    }).catch(function (err) { Admin.toast(err.message, 'error'); });
  });

  reload().catch(function (e) { Admin.toast(e.message, 'error'); });
})();
