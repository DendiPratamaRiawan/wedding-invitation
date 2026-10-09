<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';

$admin = require_admin_page();
admin_header('RSVP & Ucapan', 'responses', $admin);
?>
<section class="panel" id="rsvp">
  <div class="panel__head">
    <h2>Rekap RSVP</h2>
    <a class="btn btn-ghost btn-sm" href="../api/guests.php?action=export">Ekspor CSV lengkap</a>
  </div>
  <div class="summary" id="rsvp-summary"></div>
  <div class="toolbar">
    <input type="search" id="r-search" placeholder="Cari nama…" aria-label="Cari RSVP">
    <select id="r-status" aria-label="Filter status">
      <option value="">Semua status</option>
      <option value="hadir">Hadir</option>
      <option value="tidak_hadir">Tidak hadir</option>
      <option value="ragu">Belum pasti</option>
    </select>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Nama tamu</th><th>Status</th><th>Jumlah</th><th>Catatan</th><th>Diperbarui</th></tr></thead>
      <tbody id="rsvp-body"><tr><td colspan="5" class="muted">Memuat…</td></tr></tbody>
    </table>
  </div>
</section>

<section class="panel" id="ucapan">
  <div class="panel__head">
    <h2>Moderasi ucapan &amp; doa</h2>
    <select id="w-status" aria-label="Filter ucapan">
      <option value="pending">Menunggu persetujuan</option>
      <option value="approved">Disetujui (tampil)</option>
      <option value="hidden">Disembunyikan</option>
      <option value="">Semua</option>
    </select>
  </div>
  <ul class="wish-admin" id="wish-list"><li class="muted">Memuat…</li></ul>
</section>
<?php admin_footer(['../js/admin-responses.js']); ?>
