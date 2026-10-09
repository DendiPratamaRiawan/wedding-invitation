<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';

$admin = require_admin_page();
admin_header('Dashboard', 'index', $admin);
?>
<div id="dash-alerts"></div>

<section class="stats" aria-label="Ringkasan">
  <div class="stat"><span class="stat__label">Total tamu</span><strong class="stat__value" data-stat="guests.total">–</strong><small data-stat-note="guests"></small></div>
  <div class="stat stat--sage"><span class="stat__label">RSVP hadir</span><strong class="stat__value" data-stat="rsvp.hadir">–</strong><small data-stat-note="people"></small></div>
  <div class="stat stat--rose"><span class="stat__label">Tidak hadir</span><strong class="stat__value" data-stat="rsvp.tidak_hadir">–</strong></div>
  <div class="stat stat--gold"><span class="stat__label">Belum pasti</span><strong class="stat__value" data-stat="rsvp.ragu">–</strong></div>
  <div class="stat"><span class="stat__label">Belum menjawab</span><strong class="stat__value" data-stat="rsvp.belum">–</strong></div>
  <div class="stat"><span class="stat__label">Ucapan</span><strong class="stat__value" data-stat="wishes.total">–</strong><small data-stat-note="wishes"></small></div>
</section>

<div class="grid-2">
  <section class="panel">
    <h2>Status undangan</h2>
    <div id="dash-status" class="stack-sm"><p class="muted">Memuat…</p></div>
    <div class="actions">
      <a class="btn btn-primary" href="settings.php">Pengaturan acara</a>
      <a class="btn btn-ghost" href="../" target="_blank" rel="noopener">Lihat undangan</a>
    </div>
  </section>
  <section class="panel">
    <h2>Akses cepat</h2>
    <div class="quick-links">
      <a href="guests.php?add=1" class="quick">+ Tambah tamu</a>
      <a href="guests.php?import=1" class="quick">Impor Excel / CSV</a>
      <a href="guests.php" class="quick">Salin tautan &amp; pesan WhatsApp</a>
      <a href="responses.php" class="quick">Moderasi ucapan</a>
    </div>
  </section>
</div>

<section class="panel">
  <h2>RSVP terbaru</h2>
  <div id="dash-recent"><p class="muted">Memuat…</p></div>
</section>
<?php admin_footer(['../js/admin-dashboard.js']); ?>
