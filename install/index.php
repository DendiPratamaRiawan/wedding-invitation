<?php
/**
 * Installer: membuat tabel, data awal, dan akun admin pertama.
 * Hanya berfungsi selama belum ada akun admin. Hapus folder install/ setelah selesai.
 *
 * CLI: php install/index.php <username> <password>
 */
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

function install_schema(): void
{
    $pdo = db();
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        $pdo->exec($statement);
    }
    $exists = (int) $pdo->query('SELECT COUNT(*) FROM wedding_settings')->fetchColumn();
    if ($exists === 0) {
        $stmt = $pdo->prepare('INSERT INTO wedding_settings
            (id, groom_name, groom_full_name, groom_family, bride_name, bride_full_name, bride_family,
             bride_family_confirmed, opening_text, closing_text, theme_settings, content, published)
            VALUES (1, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0)');
        $stmt->execute([
            'Dendi', 'Dendi Pratama Riawan', 'Keluarga Besar Bapak Iwan Tardiwan',
            'Amora', '', 'Keluarga Besar Bapak Arman Pratama',
            default_opening_text(), default_closing_text(),
            json_encode(default_theme()), json_encode(default_content(), JSON_UNESCAPED_UNICODE),
        ]);
        // Acara contoh TANPA tanggal: wajib diisi admin sebelum publikasi
        $ev = $pdo->prepare('INSERT INTO events (event_type, timezone, venue_name, address, sort_order) VALUES (?, ?, ?, ?, ?)');
        $ev->execute(['Akad Nikah', 'Asia/Jakarta', '', '', 1]);
        $ev->execute(['Resepsi', 'Asia/Jakarta', '', '', 2]);
    }
}

function admin_count(): int
{
    try {
        return (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function create_admin(string $username, string $password): ?string
{
    if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
        return 'Username 3–60 karakter (huruf, angka, titik, garis bawah, tanda hubung).';
    }
    if (strlen($password) < 10) {
        return 'Password minimal 10 karakter.';
    }
    db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
        ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    return null;
}

if (PHP_SAPI === 'cli') {
    [$_, $user, $pass] = array_pad($argv, 3, '');
    install_schema();
    if (admin_count() > 0) {
        exit("Tabel siap. Akun admin sudah ada, tidak ada yang diubah.\n");
    }
    $err = create_admin($user, $pass);
    exit($err ? "Gagal: $err\n" : "Instalasi selesai. Admin '$user' dibuat.\n");
}

send_security_headers(true);
$message = '';
$done = false;

try {
    install_schema();
} catch (Throwable $ex) {
    $message = 'Gagal terhubung/membuat tabel: periksa includes/config.php. (' . $ex->getMessage() . ')';
}

if (!$message && admin_count() > 0) {
    http_response_code(403);
    exit('Instalasi sudah selesai. Hapus folder install/ dari server.');
}

if (!$message && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = create_admin(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''));
    if ($err) {
        $message = $err;
    } else {
        $done = true;
    }
}
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Instalasi</title>
<link rel="stylesheet" href="../css/admin.css">
</head>
<body class="auth-page">
<main class="auth-card">
  <h1>Instalasi Undangan</h1>
  <?php if ($done): ?>
    <p class="notice success">Instalasi selesai. <strong>Hapus folder <code>install/</code></strong> dari server, lalu masuk melalui URL panel admin.</p>
  <?php else: ?>
    <?php if ($message): ?><p class="notice error"><?= e($message) ?></p><?php endif; ?>
    <p>Buat akun admin pertama.</p>
    <form method="post" class="form-stack">
      <label>Username <input name="username" required minlength="3" maxlength="60" autocomplete="username"></label>
      <label>Password (min. 10 karakter) <input name="password" type="password" required minlength="10" autocomplete="new-password"></label>
      <button class="btn btn-primary" type="submit">Pasang</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
