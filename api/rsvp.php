<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/public.php';

const RSVP_STATUSES = ['hadir', 'tidak_hadir', 'ragu'];

api_guard(function (): void {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    $pdo = db();

    // Publik: tamu mengirim/memperbarui RSVP miliknya sendiri (diidentifikasi lewat kode undangan)
    if ($method === 'POST' && $action === 'submit') {
        $in = json_input();
        $preview = !empty($in['preview']);
        $content = public_content($preview);
        if (!$content || empty($content['content']['sections']['rsvp'])) {
            json_error('RSVP tidak tersedia.', 403);
        }
        if (!rate_limit('rsvp', client_ip(), 15, 600)) {
            json_error('Terlalu banyak permintaan. Coba lagi beberapa menit lagi.', 429);
        }
        $guest = find_active_guest((string) ($in['code'] ?? ''));
        if (!$guest) {
            json_error('RSVP hanya dapat dikirim melalui tautan undangan personal.', 403);
        }
        $status = in_array($in['status'] ?? '', RSVP_STATUSES, true) ? $in['status'] : null;
        if (!$status) {
            json_error('Pilih status kehadiran.');
        }
        $maxCompanions = $guest['max_companions'] !== null
            ? (int) $guest['max_companions']
            : (int) $content['content']['rsvp_max_companions'];
        $count = (int) ($in['guest_count'] ?? 1);
        if ($status === 'hadir') {
            if ($count < 1 || $count > 1 + $maxCompanions) {
                json_error('Jumlah tamu maksimal ' . (1 + $maxCompanions) . ' orang.');
            }
        } else {
            $count = 0;
        }
        $message = clean_text($in['message'] ?? '', 300, true);
        $pdo->prepare('INSERT INTO rsvps (guest_id, status, guest_count, message) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), guest_count = VALUES(guest_count), message = VALUES(message), updated_at = NOW()')
            ->execute([$guest['id'], $status, $count, $message]);
        json_response(['ok' => true, 'status' => $status, 'guest_count' => $count]);
    }

    // Publik: tandai tautan personal sudah dibuka (hanya waktu pertama)
    if ($method === 'POST' && $action === 'opened') {
        $in = json_input();
        if (rate_limit('opened', client_ip(), 30, 600) && ($guest = find_active_guest((string) ($in['code'] ?? '')))) {
            $pdo->prepare('UPDATE guests SET opened_at = NOW(), updated_at = updated_at WHERE id = ? AND opened_at IS NULL')->execute([$guest['id']]);
        }
        json_response(['ok' => true]);
    }

    // Publik: tamu melihat RSVP miliknya sendiri saja
    if ($method === 'GET' && $action === 'mine') {
        $guest = find_active_guest((string) ($_GET['code'] ?? ''));
        if (!$guest) {
            json_response(['ok' => true, 'rsvp' => null]);
        }
        $stmt = $pdo->prepare('SELECT status, guest_count, message FROM rsvps WHERE guest_id = ?');
        $stmt->execute([$guest['id']]);
        json_response(['ok' => true, 'rsvp' => $stmt->fetch() ?: null]);
    }

    // Admin: rekap RSVP
    if ($method === 'GET' && $action === 'list') {
        require_admin_api();
        $rows = $pdo->query('SELECT g.guest_name, g.category, r.status, r.guest_count, r.message, r.updated_at
            FROM rsvps r JOIN guests g ON g.id = r.guest_id ORDER BY r.updated_at DESC')->fetchAll();
        json_response(['ok' => true, 'rsvps' => $rows]);
    }

    json_error('Permintaan tidak dikenal.', 404);
});
