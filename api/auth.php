<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

api_guard(function (): void {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($action === 'login' && $method === 'POST') {
        start_session();
        $in = json_input();
        $username = clean_text($in['username'] ?? '', 60);
        $password = is_string($in['password'] ?? null) ? $in['password'] : '';
        $ip = client_ip();

        // Maks. 5 percobaan gagal per IP+username dan 20 per IP dalam 15 menit
        if (!rate_limit('login', $ip . '|' . mb_strtolower($username), 5, 900, false)
            || !rate_limit('login_ip', $ip, 20, 900, false)) {
            json_error('Terlalu banyak percobaan. Coba lagi dalam 15 menit.', 429);
        }

        $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        // Verifikasi tetap dijalankan agar waktu respons tidak membocorkan username yang valid
        $hash = $admin['password_hash'] ?? password_hash(random_bytes(8), PASSWORD_DEFAULT);
        $valid = password_verify($password, $hash) && $admin;

        if (!$valid) {
            rate_limit('login', $ip . '|' . mb_strtolower($username), 5, 900);
            rate_limit('login_ip', $ip, 20, 900);
            json_error('Username atau password salah.', 401);
        }

        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
        }
        rate_limit_clear('login', $ip . '|' . mb_strtolower($username));
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        unset($_SESSION['csrf']);
        db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$admin['id']]);
        json_response(['ok' => true]);
    }

    if ($action === 'logout' && $method === 'POST') {
        require_admin_api();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        json_response(['ok' => true]);
    }

    if ($action === 'password' && $method === 'POST') {
        $admin = require_admin_api();
        $in = json_input();
        $current = (string) ($in['current'] ?? '');
        $new = (string) ($in['new'] ?? '');
        if (strlen($new) < 10) {
            json_error('Password baru minimal 10 karakter.');
        }
        $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $stmt->execute([$admin['id']]);
        if (!password_verify($current, (string) $stmt->fetchColumn())) {
            json_error('Password saat ini salah.', 403);
        }
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
        session_regenerate_id(true);
        json_response(['ok' => true]);
    }

    json_error('Permintaan tidak dikenal.', 404);
});
