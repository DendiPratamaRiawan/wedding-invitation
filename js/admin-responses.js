(function () {
  'use strict';
  var el = Admin.el;
  var $ = function (id) { return document.getElementById(id); };
  var rsvps = [], wishes = [];

  /* ---------- RSVP ---------- */
  function renderRsvp() {
    var q = $('r-search').value.trim().toLowerCase();
    var st = $('r-status').value;
    var body = $('rsvp-body');
    body.innerHTML = '';
    var list = rsvps.filter(function (r) {
      return (!q || r.guest_name.toLowerCase().indexOf(q) >= 0) && (!st || r.status === st);
    });
    if (!list.length) body.appendChild(el('tr', {}, [el('td', { colspan: 5, className: 'empty', text: 'Belum ada RSVP.' })]));
    list.forEach(function (r) {
      body.appendChild(el('tr', {}, [
        el('td', { 'data-label': 'Nama' }, [el('strong', { text: r.guest_name }), r.category ? el('span', { className: 'chip', text: r.category }) : null]),
        el('td', { 'data-label': 'Status' }, [el('span', { className: 'badge badge--' + r.status, text: Admin.rsvpLabel[r.status] })]),
        el('td', { 'data-label': 'Jumlah', text: r.status === 'hadir' ? r.guest_count + ' orang' : '—' }),
        el('td', { 'data-label': 'Catatan', text: r.message || '—' }),
        el('td', { 'data-label': 'Diperbarui', text: Admin.formatDate(r.updated_at, true) })
      ]));
    });
  }

  function loadRsvp() {
    Admin.api('dashboard.php').then(function (d) {
      var box = $('rsvp-summary');
      box.innerHTML = '';
      [['Hadir', d.rsvp.hadir, 'ok'], ['Total orang hadir', d.rsvp.people, 'ok'], ['Tidak hadir', d.rsvp.tidak_hadir, 'error'],
        ['Belum pasti', d.rsvp.ragu, 'warn'], ['Belum menjawab', d.rsvp.belum, '']].forEach(function (s) {
        box.appendChild(el('div', { className: 'summary__item ' + s[2] }, [el('strong', { text: String(s[1]) }), el('span', { text: s[0] })]));
      });
    }).catch(function () {});
    Admin.api('rsvp.php?action=list').then(function (d) { rsvps = d.rsvps; renderRsvp(); })
      .catch(function (e) { Admin.toast(e.message, 'error'); });
  }
  $('r-search').addEventListener('input', renderRsvp);
  $('r-status').addEventListener('change', renderRsvp);

  /* ---------- Ucapan ---------- */
  var statusText = { pending: 'Menunggu', approved: 'Tampil', hidden: 'Disembunyikan' };

  function renderWishes() {
    var st = $('w-status').value;
    var list = $('wish-list');
    list.innerHTML = '';
    var items = wishes.filter(function (w) { return !st || w.status === st; });
    if (!items.length) list.appendChild(el('li', { className: 'empty', text: 'Tidak ada ucapan pada filter ini.' }));
    items.forEach(function (w) {
      var act = function (status) {
        return function () {
          Admin.api('wishes.php?action=moderate', { id: w.id, status: status }).then(function () {
            w.status = status;
            renderWishes();
            Admin.toast(status === 'approved' ? 'Ucapan ditampilkan' : status === 'hidden' ? 'Ucapan disembunyikan' : 'Status diperbarui', 'success');
          }).catch(function (e) { Admin.toast(e.message, 'error'); });
        };
      };
      list.appendChild(el('li', { className: 'wish-admin__item' }, [
        el('div', { className: 'wish-admin__meta' }, [
          el('strong', { text: w.guest_name }),
          w.linked_guest ? el('span', { className: 'chip', text: 'Tamu: ' + w.linked_guest }) : el('span', { className: 'chip', text: 'Tanpa tautan personal' }),
          el('span', { className: 'badge badge--w-' + w.status, text: statusText[w.status] }),
          el('span', { className: 'muted', text: Admin.formatDate(w.created_at, true) })
        ]),
        el('p', { className: 'wish-admin__msg', text: w.message }),
        el('div', { className: 'row-actions' }, [
          w.status !== 'approved' ? el('button', { type: 'button', className: 'btn btn-sm btn-primary', text: 'Setujui', onclick: act('approved') }) : null,
          w.status !== 'hidden' ? el('button', { type: 'button', className: 'btn btn-sm btn-ghost', text: 'Sembunyikan', onclick: act('hidden') }) : null,
          el('button', { type: 'button', className: 'btn btn-sm btn-ghost text-danger', text: 'Hapus', onclick: function () {
            Admin.confirm('Hapus ucapan dari ' + w.guest_name + ' secara permanen?', 'Hapus').then(function (ok) {
              if (!ok) return;
              Admin.api('wishes.php?action=delete', { id: w.id }).then(function () {
                wishes = wishes.filter(function (x) { return x.id !== w.id; });
                renderWishes();
                Admin.toast('Ucapan dihapus', 'success');
              }).catch(function (e) { Admin.toast(e.message, 'error'); });
            });
          } })
        ])
      ]));
    });
  }

  Admin.api('wishes.php?action=admin').then(function (d) {
    wishes = d.wishes;
    if (!wishes.some(function (w) { return w.status === 'pending'; })) $('w-status').value = '';
    renderWishes();
  }).catch(function (e) { Admin.toast(e.message, 'error'); });
  $('w-status').addEventListener('change', renderWishes);

  loadRsvp();
})();
