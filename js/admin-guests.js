/* Daftar tamu: CRUD, tautan personal, pesan WhatsApp, impor Excel/CSV/massal. */
(function () {
  'use strict';
  var el = Admin.el;
  var state = { guests: [], template: '', couple: '', selected: new Set(), page: 1, perPage: 50 };
  var $ = function (id) { return document.getElementById(id); };

  /* ================= Daftar ================= */
  function load() {
    return Admin.api('guests.php').then(function (d) {
      state.guests = d.guests;
      state.template = d.wa_template;
      state.couple = d.couple;
      state.defaultCompanions = d.default_companions;
      refreshCategories();
      render();
    }).catch(function (e) { Admin.toast(e.message, 'error'); });
  }

  function setGuests(list) {
    state.guests = list;
    state.selected.forEach(function (id) {
      if (!list.some(function (g) { return g.id === id; })) state.selected.delete(id);
    });
    refreshCategories();
    render();
  }

  function refreshCategories() {
    var cats = Array.from(new Set(state.guests.map(function (g) { return g.category; }).filter(Boolean))).sort();
    var sel = $('g-category');
    var cur = sel.value;
    sel.length = 1;
    cats.forEach(function (c) { sel.appendChild(el('option', { value: c, text: c })); });
    sel.value = cats.indexOf(cur) >= 0 ? cur : '';
    var dl = $('category-list');
    dl.innerHTML = '';
    cats.forEach(function (c) { dl.appendChild(el('option', { value: c })); });
  }

  function filtered() {
    var q = $('g-search').value.trim().toLowerCase();
    var cat = $('g-category').value;
    var rsvp = $('g-rsvp').value;
    var active = $('g-active').value;
    return state.guests.filter(function (g) {
      if (q && (g.guest_name + ' ' + g.category + ' ' + g.invitation_code).toLowerCase().indexOf(q) < 0) return false;
      if (cat && g.category !== cat) return false;
      if (rsvp === 'none' && g.rsvp_status) return false;
      if (rsvp && rsvp !== 'none' && g.rsvp_status !== rsvp) return false;
      if (active !== '' && String(+g.is_active) !== active) return false;
      return true;
    });
  }

  function message(g) { return Admin.waMessage(state.template, g, state.couple); }

  function render() {
    var list = filtered();
    var pages = Math.max(1, Math.ceil(list.length / state.perPage));
    state.page = Math.min(state.page, pages);
    var slice = list.slice((state.page - 1) * state.perPage, state.page * state.perPage);
    var body = $('g-body');
    body.innerHTML = '';
    $('g-count').textContent = list.length + ' dari ' + state.guests.length + ' tamu';

    if (!slice.length) {
      body.appendChild(el('tr', {}, [el('td', { colspan: 7, className: 'empty' }, [
        state.guests.length ? 'Tidak ada tamu yang cocok dengan filter.' : 'Belum ada tamu. Tambahkan manual atau impor dari Excel/CSV.'
      ])]));
    }

    slice.forEach(function (g) {
      var check = el('input', { type: 'checkbox', 'aria-label': 'Pilih ' + g.guest_name });
      check.checked = state.selected.has(g.id);
      check.addEventListener('change', function () {
        check.checked ? state.selected.add(g.id) : state.selected.delete(g.id);
        syncBulk();
      });
      var rsvpText = g.rsvp_status ? Admin.rsvpLabel[g.rsvp_status] + (g.rsvp_status === 'hadir' ? ' · ' + g.guest_count + ' org' : '') : 'Belum menjawab';

      body.appendChild(el('tr', { className: g.is_active ? '' : 'is-inactive' }, [
        el('td', { className: 'col-check' }, [check]),
        el('td', { 'data-label': 'Nama' }, [
          el('strong', { text: g.guest_name }),
          g.category ? el('span', { className: 'chip', text: g.category }) : null,
          !g.is_active ? el('span', { className: 'badge badge--off', text: 'Nonaktif' }) : null,
          g.notes ? el('small', { className: 'muted block', text: g.notes }) : null
        ]),
        el('td', { 'data-label': 'Tautan' }, [
          el('code', { text: g.invitation_code }),
          el('a', { href: g.link, target: '_blank', rel: 'noopener', className: 'link-small block', text: g.link.replace(/^https?:\/\//, '') })
        ]),
        el('td', { 'data-label': 'RSVP' }, [el('span', { className: 'badge badge--' + (g.rsvp_status || 'none'), text: rsvpText })]),
        el('td', { 'data-label': 'Dibuka' }, [g.opened_at ? Admin.formatDate(g.opened_at, true) : el('span', { className: 'muted', text: 'Belum' })]),
        el('td', { 'data-label': 'Dibuat' }, [Admin.formatDate(g.created_at)]),
        el('td', { className: 'col-actions' }, [el('div', { className: 'row-actions' }, [
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: 'Salin tautan', onclick: function () { Admin.copy(g.link, 'Tautan ' + g.guest_name + ' disalin'); } }),
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: 'Salin pesan', onclick: function () { Admin.copy(message(g), 'Pesan undangan disalin'); } }),
          el('a', { className: 'btn btn-sm btn-wa' + (g.is_active ? '' : ' is-disabled'), href: Admin.waUrl(message(g), g.phone), target: '_blank', rel: 'noopener', text: 'WhatsApp',
            title: g.phone ? 'Buka chat ' + g.phone : 'Pilih kontak di WhatsApp' }),
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: 'Edit', onclick: function () { openForm(g); } }),
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: g.is_active ? 'Nonaktifkan' : 'Aktifkan', onclick: function () { toggle([g.id], !g.is_active); } }),
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost text-danger', text: 'Hapus', onclick: function () { remove([g.id], g.guest_name); } })
        ])])
      ]));
    });

    renderPager(pages);
    syncBulk();
  }

  function renderPager(pages) {
    var pager = $('pager');
    pager.innerHTML = '';
    if (pages <= 1) return;
    pager.appendChild(el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: '‹ Sebelumnya', disabled: state.page === 1,
      onclick: function () { state.page--; render(); } }));
    pager.appendChild(el('span', { className: 'muted', text: 'Halaman ' + state.page + ' / ' + pages }));
    pager.appendChild(el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: 'Berikutnya ›', disabled: state.page === pages,
      onclick: function () { state.page++; render(); } }));
  }

  function syncBulk() {
    var n = state.selected.size;
    $('bulkbar').hidden = n === 0;
    $('bulk-count').textContent = n + ' tamu dipilih';
    var visible = filtered();
    $('check-all').checked = visible.length > 0 && visible.every(function (g) { return state.selected.has(g.id); });
  }

  ['g-search', 'g-category', 'g-rsvp', 'g-active'].forEach(function (id) {
    $(id).addEventListener('input', function () { state.page = 1; render(); });
  });
  $('check-all').addEventListener('change', function (e) {
    filtered().forEach(function (g) { e.target.checked ? state.selected.add(g.id) : state.selected.delete(g.id); });
    render();
  });

  function toggle(ids, active) {
    return Admin.api('guests.php?action=toggle', { ids: ids, is_active: active }).then(function (d) {
      setGuests(d.guests);
      Admin.toast(active ? 'Tautan diaktifkan' : 'Tautan dinonaktifkan — tamu akan melihat sapaan umum', 'success');
    }).catch(function (e) { Admin.toast(e.message, 'error'); });
  }

  function remove(ids, name) {
    var text = ids.length === 1 && name
      ? 'Hapus tamu "' + name + '"? RSVP tamu ini juga akan terhapus dan tautannya tidak berlaku lagi.'
      : 'Hapus ' + ids.length + ' tamu terpilih? RSVP mereka juga akan terhapus.';
    Admin.confirm(text, 'Hapus').then(function (ok) {
      if (!ok) return;
      Admin.api('guests.php?action=delete', { ids: ids }).then(function (d) {
        ids.forEach(function (id) { state.selected.delete(id); });
        setGuests(d.guests);
        Admin.toast('Tamu dihapus', 'success');
      }).catch(function (e) { Admin.toast(e.message, 'error'); });
    });
  }

  document.querySelectorAll('[data-bulk]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var ids = Array.from(state.selected);
      var act = btn.getAttribute('data-bulk');
      if (act === 'delete') remove(ids);
      else toggle(ids, act === 'activate');
    });
  });

  /* ================= Form tamu ================= */
  var dlg = $('guest-dialog');
  var form = $('guest-form');

  function openForm(g) {
    form.reset();
    $('guest-error').hidden = true;
    $('guest-dialog-title').textContent = g ? 'Edit tamu' : 'Tambah tamu';
    form.id.value = g ? g.id : '';
    form.guest_name.value = g ? g.guest_name : '';
    form.category.value = g ? g.category : '';
    form.phone.value = g ? g.phone : '';
    form.max_companions.value = g && g.max_companions !== null ? g.max_companions : '';
    form.max_companions.placeholder = 'Bawaan (' + state.defaultCompanions + ')';
    form.notes.value = g ? g.notes : '';
    form.is_active.checked = g ? g.is_active : true;
    var hint = $('guest-code-hint');
    hint.hidden = !g;
    if (g) hint.textContent = 'Kode undangan ' + g.invitation_code + ' tetap sama walaupun nama diubah.';
    dlg.showModal();
    form.guest_name.focus();
  }

  function saveGuest(allowDuplicate) {
    var name = form.guest_name.value.trim();
    var err = $('guest-error');
    if (!name) {
      err.textContent = 'Nama tamu wajib diisi.';
      err.hidden = false;
      return;
    }
    var payload = {
      id: form.id.value ? +form.id.value : 0,
      guest_name: name,
      category: form.category.value.trim(),
      phone: form.phone.value.trim(),
      max_companions: form.max_companions.value,
      notes: form.notes.value.trim(),
      is_active: form.is_active.checked,
      allow_duplicate: !!allowDuplicate
    };
    Admin.api('guests.php?action=save', payload).then(function (d) {
      dlg.close();
      setGuests(d.guests);
      Admin.toast(payload.id ? 'Data tamu diperbarui' : 'Tamu ditambahkan — tautan personal sudah dibuat', 'success');
    }).catch(function (e) {
      if (e.status === 409) {
        Admin.confirm('Nama "' + name + '" sudah ada di daftar. Tetap simpan sebagai data terpisah?', 'Tetap simpan').then(function (ok) {
          if (ok) saveGuest(true);
        });
        return;
      }
      err.textContent = e.message;
      err.hidden = false;
    });
  }

  form.addEventListener('submit', function (e) { e.preventDefault(); saveGuest(false); });
  $('btn-add').addEventListener('click', function () { openForm(null); });
  document.querySelectorAll('[data-close]').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('dialog').close(); });
  });

  /* ================= Impor ================= */
  var imp = { rows: [], sheetData: null, workbook: null, source: 'file', parsed: [] };
  var importDlg = $('import-dialog');
  var XLSX_SRC = '../assets/vendor/xlsx.full.min.js';

  function gotoStep(n) {
    document.querySelectorAll('#import-dialog .step').forEach(function (s) { s.hidden = +s.getAttribute('data-step') !== n; });
    document.querySelectorAll('#import-steps li').forEach(function (li, i) {
      li.classList.toggle('active', i === n - 1);
      li.classList.toggle('done', i < n - 1);
    });
  }
  function importError(msg) {
    var e = $('import-error');
    e.textContent = msg || '';
    e.hidden = !msg;
  }

  function openImport() {
    importError('');
    $('import-file').value = '';
    $('sheet-picker').hidden = true;
    gotoStep(1);
    importDlg.showModal();
  }
  $('btn-import').addEventListener('click', openImport);
  document.querySelectorAll('[data-goto]').forEach(function (b) {
    b.addEventListener('click', function () { gotoStep(+b.getAttribute('data-goto')); });
  });
  $('import-again').addEventListener('click', openImport);

  document.querySelectorAll('#import-dialog .tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      document.querySelectorAll('#import-dialog .tab').forEach(function (t) {
        t.classList.toggle('active', t === tab);
        t.setAttribute('aria-selected', String(t === tab));
      });
      document.querySelectorAll('#import-dialog .tab-panel').forEach(function (p) {
        p.hidden = p.getAttribute('data-panel') !== tab.getAttribute('data-tab');
      });
    });
  });

  /** Parser CSV dengan deteksi pemisah (koma, titik koma, tab) dan dukungan tanda kutip. */
  function parseCSV(text) {
    text = text.replace(/^﻿/, '');
    var firstLine = text.split(/\r?\n/)[0] || '';
    var delim = [',', ';', '\t'].map(function (d) { return [d, firstLine.split(d).length]; })
      .sort(function (a, b) { return b[1] - a[1]; })[0][0];
    var rows = [], row = [], field = '', inQuotes = false;
    for (var i = 0; i < text.length; i++) {
      var ch = text[i];
      if (inQuotes) {
        if (ch === '"') {
          if (text[i + 1] === '"') { field += '"'; i++; } else { inQuotes = false; }
        } else { field += ch; }
      } else if (ch === '"') { inQuotes = true; }
      else if (ch === delim) { row.push(field); field = ''; }
      else if (ch === '\n' || ch === '\r') {
        if (ch === '\r' && text[i + 1] === '\n') i++;
        row.push(field); rows.push(row); row = []; field = '';
      } else { field += ch; }
    }
    if (field !== '' || row.length) { row.push(field); rows.push(row); }
    return rows;
  }

  function handleFile(file) {
    importError('');
    if (!file) return;
    var ext = (file.name.split('.').pop() || '').toLowerCase();
    if (['xlsx', 'xls', 'csv'].indexOf(ext) < 0) {
      importError('Format tidak didukung. Gunakan .xlsx, .xls, atau .csv.');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      importError('Ukuran berkas maksimal 5 MB.');
      return;
    }
    var reader = new FileReader();
    if (ext === 'csv') {
      reader.onload = function () { useMatrix(parseCSV(String(reader.result)), file.name); };
      reader.readAsText(file, 'UTF-8');
      return;
    }
    // Library spreadsheet hanya dimuat saat dibutuhkan
    Admin.loadScript(XLSX_SRC).then(function () {
      reader.onload = function () {
        try {
          var wb = window.XLSX.read(new Uint8Array(reader.result), { type: 'array' });
          imp.workbook = wb;
          var picker = $('sheet-picker');
          var sel = $('sheet-select');
          sel.innerHTML = '';
          wb.SheetNames.forEach(function (n) { sel.appendChild(el('option', { value: n, text: n })); });
          picker.hidden = wb.SheetNames.length < 2;
          useSheet(wb.SheetNames[0], file.name);
        } catch (err) {
          importError('Berkas tidak dapat dibaca: ' + err.message);
        }
      };
      reader.readAsArrayBuffer(file);
    }).catch(function (e) { importError(e.message); });
  }

  function useSheet(name, fileName) {
    var sheet = imp.workbook.Sheets[name];
    var matrix = window.XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '', blankrows: false });
    useMatrix(matrix, fileName + ' — ' + name);
  }
  $('sheet-select').addEventListener('change', function (e) { useSheet(e.target.value, $('import-file').files[0].name); });

  function useMatrix(matrix, label) {
    matrix = matrix.filter(function (r) { return r.some(function (c) { return String(c).trim() !== ''; }); });
    if (!matrix.length) {
      importError('Berkas kosong atau tidak berisi data.');
      return;
    }
    imp.matrix = matrix;
    imp.source = 'file';
    $('map-info').textContent = label + ' · ' + matrix.length + ' baris terbaca';
    buildMapping();
    gotoStep(2);
  }

  $('import-file').addEventListener('change', function (e) { handleFile(e.target.files[0]); });
  var dz = $('dropzone');
  ['dragenter', 'dragover'].forEach(function (t) { dz.addEventListener(t, function (e) { e.preventDefault(); dz.classList.add('drag'); }); });
  ['dragleave', 'drop'].forEach(function (t) { dz.addEventListener(t, function () { dz.classList.remove('drag'); }); });
  dz.addEventListener('drop', function (e) {
    e.preventDefault();
    var f = e.dataTransfer.files[0];
    if (f) handleFile(f);
  });

  function columnCount() {
    return imp.matrix.reduce(function (m, r) { return Math.max(m, r.length); }, 0);
  }

  function buildMapping() {
    var hasHeader = $('has-header').checked;
    var cols = columnCount();
    var header = imp.matrix[0];
    var names = [];
    for (var i = 0; i < cols; i++) {
      names.push(hasHeader && String(header[i] || '').trim() ? String(header[i]).trim() : 'Kolom ' + String.fromCharCode(65 + (i % 26)) + (i >= 26 ? Math.floor(i / 26) : ''));
    }
    var guess = function (re) {
      if (!hasHeader) return -1;
      for (var j = 0; j < cols; j++) if (re.test(String(header[j] || '').toLowerCase())) return j;
      return -1;
    };
    var fill = function (sel, idx, optional) {
      sel.innerHTML = '';
      if (optional) sel.appendChild(el('option', { value: '-1', text: '— Tidak ada —' }));
      names.forEach(function (n, i) { sel.appendChild(el('option', { value: i, text: n })); });
      sel.value = String(idx >= 0 ? idx : (optional ? -1 : 0));
    };
    fill($('map-name'), guess(/nama|name|tamu|guest/), false);
    fill($('map-category'), guess(/kategori|kelompok|category|group|grup/), true);
    fill($('map-phone'), guess(/whatsapp|wa|telepon|phone|hp|nomor|no\.?/), true);

    var table = $('raw-preview');
    table.innerHTML = '';
    table.appendChild(el('thead', {}, [el('tr', {}, names.map(function (n) { return el('th', { text: n }); }))]));
    var tb = el('tbody');
    imp.matrix.slice(hasHeader ? 1 : 0, (hasHeader ? 1 : 0) + 8).forEach(function (r) {
      var tr = el('tr');
      for (var k = 0; k < cols; k++) tr.appendChild(el('td', { text: String(r[k] === undefined ? '' : r[k]) }));
      tb.appendChild(tr);
    });
    table.appendChild(tb);
  }
  $('has-header').addEventListener('change', buildMapping);

  $('map-next').addEventListener('click', function () {
    var hasHeader = $('has-header').checked;
    var ni = +$('map-name').value, ci = +$('map-category').value, pi = +$('map-phone').value;
    imp.rows = imp.matrix.slice(hasHeader ? 1 : 0).map(function (r, i) {
      return {
        line: i + (hasHeader ? 2 : 1),
        guest_name: String(r[ni] === undefined ? '' : r[ni]).trim(),
        category: ci >= 0 ? String(r[ci] === undefined ? '' : r[ci]).trim() : '',
        phone: pi >= 0 ? String(r[pi] === undefined ? '' : r[pi]).trim() : ''
      };
    });
    if (!imp.rows.length) {
      Admin.toast('Tidak ada baris data setelah judul kolom.', 'error');
      return;
    }
    dryRun();
  });

  $('paste-next').addEventListener('click', function () {
    var cat = $('paste-category').value.trim();
    var lines = $('paste-names').value.split(/\r?\n/);
    imp.rows = [];
    lines.forEach(function (l, i) {
      if (l.trim() === '') return;
      imp.rows.push({ line: i + 1, guest_name: l.trim(), category: cat, phone: '' });
    });
    if (!imp.rows.length) {
      importError('Tempelkan minimal satu nama.');
      return;
    }
    importError('');
    imp.source = 'paste';
    dryRun();
  });

  $('dry-back').addEventListener('click', function () { gotoStep(imp.source === 'paste' ? 1 : 2); });

  function dupMode() { return document.querySelector('#dup-mode input:checked').value; }
  document.querySelectorAll('#dup-mode input').forEach(function (r) { r.addEventListener('change', dryRun); });

  var statusLabel = { created: 'Akan diimpor', updated: 'Akan diperbarui', skipped: 'Dilewati', invalid: 'Gagal' };
  var statusLabelDone = { created: 'Berhasil', updated: 'Diperbarui', skipped: 'Dilewati', invalid: 'Gagal' };

  function summary(target, r, done) {
    var box = $(target);
    box.innerHTML = '';
    [
      ['Total baris', r.total, ''],
      [done ? 'Berhasil diimpor' : 'Akan diimpor', r.created, 'ok'],
      [done ? 'Diperbarui' : 'Akan diperbarui', r.updated, 'info'],
      ['Duplikat', r.duplicates, 'warn'],
      ['Dilewati', r.skipped, ''],
      ['Gagal', r.failed, 'error']
    ].forEach(function (s) {
      box.appendChild(el('div', { className: 'summary__item ' + s[2] }, [el('strong', { text: String(s[1]) }), el('span', { text: s[0] })]));
    });
  }

  function rowsTable(table, rows, labels) {
    table.innerHTML = '';
    table.appendChild(el('thead', {}, [el('tr', {}, ['Baris', 'Nama tamu', 'Kategori', 'No. WA', 'Status', 'Keterangan'].map(function (h) { return el('th', { text: h }); }))]));
    var tb = el('tbody');
    rows.forEach(function (r) {
      tb.appendChild(el('tr', {}, [
        el('td', { text: String(r.line) }), el('td', { text: r.guest_name || '—' }), el('td', { text: r.category }),
        el('td', { text: r.phone }), el('td', {}, [el('span', { className: 'badge badge--st-' + r.status, text: labels[r.status] })]),
        el('td', { className: 'muted', text: r.reason || '' })
      ]));
    });
    table.appendChild(tb);
  }

  function dryRun() {
    Admin.api('guests.php?action=import', { rows: imp.rows, mode: dupMode(), dry_run: true }).then(function (d) {
      summary('dry-summary', d.report, false);
      $('dup-mode').hidden = d.report.duplicates === 0;
      rowsTable($('dry-table'), d.report.rows, statusLabel);
      $('import-commit').disabled = d.report.created + d.report.updated === 0;
      gotoStep(3);
    }).catch(function (e) { Admin.toast(e.message, 'error'); });
  }

  $('import-commit').addEventListener('click', function () {
    var btn = $('import-commit');
    btn.disabled = true;
    btn.textContent = 'Menyimpan…';
    Admin.api('guests.php?action=import', { rows: imp.rows, mode: dupMode(), dry_run: false }).then(function (d) {
      summary('final-summary', d.report, true);
      var failed = d.report.rows.filter(function (r) { return r.status === 'invalid' || r.status === 'skipped'; });
      var box = $('final-failed');
      box.innerHTML = '';
      if (failed.length) {
        box.appendChild(el('h3', { text: 'Baris yang tidak diimpor' }));
        var t = el('table', { className: 'table table--compact' });
        rowsTable(t, failed, statusLabelDone);
        box.appendChild(el('div', { className: 'table-wrap table-wrap--short' }, [t]));
      }
      setGuests(d.guests);
      gotoStep(4);
    }).catch(function (e) { Admin.toast(e.message, 'error'); }).then(function () {
      btn.disabled = false;
      btn.textContent = 'Konfirmasi & simpan';
    });
  });

  $('template-xlsx').addEventListener('click', function (e) {
    e.preventDefault();
    Admin.loadScript(XLSX_SRC).then(function () {
      var ws = window.XLSX.utils.aoa_to_sheet([
        ['Nama Tamu', 'Kategori', 'No. WhatsApp'],
        ['Bapak Ahmad Fauzi', 'Keluarga', '081234567890'],
        ['Ibu Siti Rahma', 'Teman Kantor', ''],
        ['Keluarga Bapak Hendra', 'Tetangga', '']
      ]);
      ws['!cols'] = [{ wch: 32 }, { wch: 18 }, { wch: 18 }];
      var wb = window.XLSX.utils.book_new();
      window.XLSX.utils.book_append_sheet(wb, ws, 'Tamu');
      window.XLSX.writeFile(wb, 'template-tamu.xlsx');
    }).catch(function (err) { Admin.toast(err.message, 'error'); });
  });

  /* ================= Mulai ================= */
  load().then(function () {
    var params = new URLSearchParams(location.search);
    if (params.get('add')) openForm(null);
    if (params.get('import')) openImport();
  });
})();
