(function () {
  'use strict';
  var el = Admin.el;

  Admin.api('dashboard.php').then(function (d) {
    document.querySelectorAll('[data-stat]').forEach(function (node) {
      var path = node.getAttribute('data-stat').split('.');
      node.textContent = d[path[0]][path[1]];
    });
    document.querySelector('[data-stat-note="guests"]').textContent = d.guests.opened + ' sudah membuka · ' + (d.guests.total - d.guests.active) + ' nonaktif';
    document.querySelector('[data-stat-note="people"]').textContent = d.rsvp.people + ' orang akan hadir';
    document.querySelector('[data-stat-note="wishes"]').textContent = d.wishes.pending + ' menunggu moderasi';

    var status = document.getElementById('dash-status');
    status.innerHTML = '';
    status.appendChild(el('p', {}, [el('span', { className: 'badge badge--ok', text: 'Aktif' }), '  ', el('strong', { text: d.settings.couple })]));
    if (d.next_event) {
      status.appendChild(el('p', { className: 'muted', text: 'Acara terdekat: ' + d.next_event.event_type + ' — ' + Admin.formatDate(d.next_event.event_date) }));
    } else {
      status.appendChild(el('p', { className: 'muted', text: 'Tanggal acara belum diisi.' }));
    }

    var alerts = document.getElementById('dash-alerts');
    if (d.wishes.pending > 0) {
      alerts.appendChild(el('p', { className: 'notice info' }, [
        d.wishes.pending + ' ucapan menunggu persetujuan. ', el('a', { href: 'responses.php#ucapan', text: 'Moderasi sekarang' }), '.'
      ]));
    }

    var recent = document.getElementById('dash-recent');
    recent.innerHTML = '';
    if (!d.recent_rsvps.length) {
      recent.appendChild(el('p', { className: 'muted', text: 'Belum ada RSVP.' }));
      return;
    }
    var list = el('ul', { className: 'simple-list' });
    d.recent_rsvps.forEach(function (r) {
      list.appendChild(el('li', {}, [
        el('strong', { text: r.guest_name }),
        el('span', { className: 'badge badge--' + r.status, text: Admin.rsvpLabel[r.status] + (r.status === 'hadir' ? ' · ' + r.guest_count + ' org' : '') }),
        el('span', { className: 'muted', text: Admin.formatDate(r.updated_at, true) })
      ]));
    });
    recent.appendChild(list);
  }).catch(function (e) { Admin.toast(e.message, 'error'); });
})();
