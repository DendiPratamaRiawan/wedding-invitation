<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/public.php';

api_guard(function (): void {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    $pdo = db();

    // Publik: hanya ucapan yang disetujui, tanpa data pribadi
    if ($method === 'GET' && $action === '') {
        $content = public_content(!empty($_GET['preview']));
        if (!$content || empty($content['content']['sections']['wishes'])) {
            json_response(['ok' => true, 'wishes' => [], 'total' => 0]);
        }
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $stmt = $pdo->prepare("SELECT guest_name, message, created_at FROM wishes WHERE status = 'approved' ORDER BY created_at DESC, id DESC LIMIT 20 OFFSET $offset");
        $stmt->execute();
        $total = (int) $pdo->query("SELECT COUNT(*) FROM wishes WHERE status = 'approved'")->fetchColumn();
        json_response(['ok' => true, 'wishes' => $stmt->fetchAll(), 'total' => $total]);
    }

    if ($method === 'POST' && $action === 'submit') {
        $in = json_input();
        $content = public_content(!empty($in['preview']));
        if (!$content || empty($content['content']['sections']['wishes'])) {
            json_error('Fitur ucapan tidak tersedia.', 403);
        }
        // Honeypot sederhana untuk bot
        if (!empty($in['website'])) {
            json_response(['ok' => true, 'pending' => true]);
        }
        if (!rate_limit('wish', client_ip(), 3, 600)) {
            json_error('Terlalu banyak ucapan terkirim. Coba lagi beberapa menit lagi.', 429);
        }
        $name = clean_text($in['name'] ?? '', 60);
        $message = clean_text($in['message'] ?? '', 500, true);
        if (mb_strlen($name) < 2) {
            json_error('Nama minimal 2 karakter.');
        }
        if (mb_strlen($message) < 3) {
            json_error('Ucapan minimal 3 karakter.');
        }
        $guest = find_active_guest((string) ($in['code'] ?? ''));
        $status = !empty($content['content']['wishes_auto_approve']) ? 'approved' : 'pending';
        $pdo->prepare('INSERT INTO wishes (guest_id, guest_name, message, status) VALUES (?, ?, ?, ?)')
            ->execute([$guest['id'] ?? null, $name, $message, $status]);
        json_response(['ok' => true, 'pending' => $status === 'pending']);
    }

    // ---- Admin ----
    if ($method === 'GET' && $action === 'admin') {
        require_admin_api();
        $rows = $pdo->query('SELECT w.id, w.guest_name, w.message, w.status, w.created_at, g.guest_name AS linked_guest
            FROM wishes w LEFT JOIN guests g ON g.id = w.guest_id ORDER BY w.created_at DESC, w.id DESC')->fetchAll();
        json_response(['ok' => true, 'wishes' => $rows]);
    }

    if ($method === 'POST' && $action === 'moderate') {
        require_admin_api();
        $in = json_input();
        $status = in_array($in['status'] ?? '', ['pending', 'approved', 'hidden'], true) ? $in['status'] : null;
        if (!$status) {
            json_error('Status tidak valid.');
        }
        $pdo->prepare('UPDATE wishes SET status = ? WHERE id = ?')->execute([$status, (int) ($in['id'] ?? 0)]);
        json_response(['ok' => true]);
    }

    if ($method === 'POST' && $action === 'delete') {
        require_admin_api();
        $in = json_input();
        $pdo->prepare('DELETE FROM wishes WHERE id = ?')->execute([(int) ($in['id'] ?? 0)]);
        json_response(['ok' => true]);
    }

    json_error('Permintaan tidak dikenal.', 404);
});
