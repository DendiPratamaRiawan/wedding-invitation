<?php
declare(strict_types=1);

function admin_header(string $title, string $active, array $admin): void
{
    $nav = [
        'index' => ['Dashboard', 'index.php'],
        'guests' => ['Daftar Tamu', 'guests.php'],
        'responses' => ['RSVP & Ucapan', 'responses.php'],
        'settings' => ['Pengaturan Undangan', 'settings.php'],
    ];
    ?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($title) ?> · Admin Undangan</title>
<link rel="stylesheet" href="../css/admin.css">
<script src="../js/admin.js" defer></script>
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand"><span class="brand__moon" aria-hidden="true"></span><span>Admin Undangan</span></div>
    <nav>
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a href="<?= $href ?>" class="<?= $key === $active ? 'active' : '' ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar__foot">
      <a href="../" target="_blank" rel="noopener">Lihat undangan ↗</a>
      <span class="muted">Masuk sebagai <strong><?= e($admin['username']) ?></strong></span>
      <button type="button" class="btn btn-ghost btn-sm" id="logout-btn">Keluar</button>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <button type="button" class="btn btn-ghost btn-sm menu-btn" id="menu-btn" aria-controls="sidebar" aria-expanded="false">Menu</button>
      <h1><?= e($title) ?></h1>
    </header>
    <main class="page">
<?php
}

function admin_footer(array $scripts = []): void
{
    ?>
    </main>
  </div>
</div>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<dialog class="modal modal--sm" id="confirm-dialog">
  <form method="dialog">
    <p id="confirm-text"></p>
    <div class="modal__actions">
      <button value="cancel" class="btn btn-ghost">Batal</button>
      <button value="ok" class="btn btn-danger" id="confirm-ok">Ya, lanjutkan</button>
    </div>
  </form>
</dialog>
<?php foreach ($scripts as $src): ?>
<script src="<?= e($src) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php
}
