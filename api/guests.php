<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

function guest_rows(): array
{
    $rows = db()->query('SELECT g.id, g.guest_name, g.category, g.phone, g.invitation_code, g.max_companions, g.notes,
            g.is_active, g.opened_at, g.created_at, r.status AS rsvp_status, r.guest_count, r.message AS rsvp_message, r.updated_at AS rsvp_at
        FROM guests g LEFT JOIN rsvps r ON r.guest_id = g.id ORDER BY g.created_at DESC, g.id DESC')->fetchAll();
    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['is_active'] = (bool) $r['is_active'];
        $r['guest_count'] = $r['guest_count'] !== null ? (int) $r['guest_count'] : null;
        $r['max_companions'] = $r['max_companions'] !== null ? (int) $r['max_companions'] : null;
        $r['link'] = guest_link($r['invitation_code']);
    }
    return $rows;
}

function clean_phone($value): string
{
    $digits = preg_replace('/\D+/', '', is_scalar($value) ? (string) $value : '') ?? '';
    if ($digits === '') return '';
    if (str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1);
    // Nomor dari Excel sering kehilangan angka 0 di depan (mis. 812xxxx)
    elseif (str_starts_with($digits, '8') && strlen($digits) >= 9 && strlen($digits) <= 13) $digits = '62' . $digits;
    return substr($digits, 0, 20);
}

function import_rows(array $rows, string $mode, bool $dryRun): array
{
    $pdo = db();
    $existing = [];
    foreach ($pdo->query('SELECT id, name_key FROM guests') as $g) {
        $existing[$g['name_key']] = (int) $g['id'];
    }
    $seen = [];
    $report = ['total' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'duplicates' => 0, 'failed' => 0, 'rows' => []];
    $insert = $pdo->prepare('INSERT INTO guests (guest_name, name_key, category, phone, invitation_code) VALUES (?, ?, ?, ?, ?)');
    $update = $pdo->prepare("UPDATE guests SET category = IF(? = '', category, ?), phone = IF(? = '', phone, ?) WHERE id = ?");

    if (!$dryRun) $pdo->beginTransaction();
    foreach ($rows as $i => $row) {
        $report['total']++;
        $line = (int) ($row['line'] ?? $i + 1);
        $name = clean_text($row['guest_name'] ?? '', 160);
        $category = clean_text($row['category'] ?? '', 80);
        $phone = clean_phone($row['phone'] ?? '');
        $entry = ['line' => $line, 'guest_name' => $name, 'category' => $category, 'phone' => $phone];

        if ($name === '') {
            $report['failed']++;
            $report['rows'][] = $entry + ['status' => 'invalid', 'reason' => 'Nama tamu kosong'];
            continue;
        }
        if (mb_strlen(trim((string) ($row['guest_name'] ?? ''))) > 160) {
            $report['failed']++;
            $report['rows'][] = $entry + ['status' => 'invalid', 'reason' => 'Nama lebih dari 160 karakter'];
            continue;
        }
        $key = name_key($name);
        $dupDb = isset($existing[$key]);
        $dupFile = isset($seen[$key]);
        $seen[$key] = true;

        if ($dupDb || $dupFile) {
            $report['duplicates']++;
            $reason = $dupFile ? 'Duplikat di dalam berkas' : 'Sudah ada di daftar tamu';
            if ($mode === 'update' && $dupDb && !$dupFile) {
                if (!$dryRun) $update->execute([$category, $category, $phone, $phone, $existing[$key]]);
                $report['updated']++;
                $report['rows'][] = $entry + ['status' => 'updated', 'reason' => $reason];
                continue;
            }
            if ($mode !== 'import') {
                $report['skipped']++;
                $report['rows'][] = $entry + ['status' => 'skipped', 'reason' => $reason];
                continue;
            }
        }
        if (!$dryRun) {
            $insert->execute([$name, $key, $category, $phone, generate_invitation_code()]);
            $existing[$key] = (int) $pdo->lastInsertId();
        }
        $report['created']++;
        $report['rows'][] = $entry + ['status' => 'created', 'reason' => ($dupDb || $dupFile) ? 'Duplikat, tetap diimpor' : ''];
    }
    if (!$dryRun) $pdo->commit();
    return $report;
}

api_guard(function (): void {
    require_admin_api();
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    $pdo = db();

    if ($method === 'GET' && $action === '') {
        $s = load_settings_row();
        json_response([
            'ok' => true,
            'guests' => guest_rows(),
            'wa_template' => $s['content']['wa_template'],
            'couple' => $s['groom_name'] . ' & ' . $s['bride_name'],
            'default_companions' => (int) $s['content']['rsvp_max_companions'],
            'app_url' => app_url(),
        ]);
    }

    if ($method === 'GET' && $action === 'export') {
        $s = load_settings_row();
        $template = $s['content']['wa_template'];
        $couple = $s['groom_name'] . ' & ' . $s['bride_name'];
        $labels = ['hadir' => 'Hadir', 'tidak_hadir' => 'Tidak hadir', 'ragu' => 'Belum pasti'];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="daftar-tamu-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
        fputcsv($out, ['Nama Tamu', 'Kategori', 'No. WhatsApp', 'Kode Undangan', 'Tautan Personal', 'Status Tautan',
            'Status RSVP', 'Jumlah Kehadiran', 'Dibuka', 'Tanggal Dibuat', 'Pesan WhatsApp']);
        foreach (guest_rows() as $g) {
            $msg = str_replace(['[Nama Tamu]', '[Tautan Undangan]', '[Nama Mempelai]'], [$g['guest_name'], $g['link'], $couple], $template);
            $row = [$g['guest_name'], $g['category'], $g['phone'], $g['invitation_code'], $g['link'],
                $g['is_active'] ? 'Aktif' : 'Nonaktif', $labels[$g['rsvp_status']] ?? 'Belum menjawab',
                $g['guest_count'] ?? '', $g['opened_at'] ? 'Ya' : 'Belum', $g['created_at'], $msg];
            // Cegah formula injection saat dibuka di spreadsheet
            $row = array_map(static fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $row);
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    if ($method === 'POST' && $action === 'save') {
        $in = json_input();
        $id = (int) ($in['id'] ?? 0);
        $name = clean_text($in['guest_name'] ?? '', 160);
        if ($name === '') {
            json_error('Nama tamu wajib diisi.');
        }
        $category = clean_text($in['category'] ?? '', 80);
        $phone = clean_phone($in['phone'] ?? '');
        $notes = clean_text($in['notes'] ?? '', 500);
        $max = ($in['max_companions'] ?? '') === '' || ($in['max_companions'] ?? null) === null
            ? null : max(0, min(10, (int) $in['max_companions']));
        $active = !array_key_exists('is_active', $in) || !empty($in['is_active']);

        $dup = $pdo->prepare('SELECT id FROM guests WHERE name_key = ? AND id <> ? LIMIT 1');
        $dup->execute([name_key($name), $id]);
        if ($dup->fetchColumn() && empty($in['allow_duplicate'])) {
            json_error('Nama tamu ini sudah ada. Simpan tetap?', 409, ['duplicate' => true]);
        }

        if ($id > 0) {
            // Kode undangan tidak berubah saat nama diedit
            $stmt = $pdo->prepare('UPDATE guests SET guest_name=?, name_key=?, category=?, phone=?, notes=?, max_companions=?, is_active=? WHERE id=?');
            $stmt->execute([$name, name_key($name), $category, $phone, $notes, $max, $active ? 1 : 0, $id]);
        } else {
            $pdo->prepare('INSERT INTO guests (guest_name, name_key, category, phone, notes, max_companions, is_active, invitation_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$name, name_key($name), $category, $phone, $notes, $max, $active ? 1 : 0, generate_invitation_code()]);
        }
        json_response(['ok' => true, 'guests' => guest_rows()]);
    }

    if ($method === 'POST' && $action === 'toggle') {
        $in = json_input();
        $ids = array_map('intval', (array) ($in['ids'] ?? []));
        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE guests SET is_active = ? WHERE id IN ($marks)")
                ->execute([!empty($in['is_active']) ? 1 : 0, ...$ids]);
        }
        json_response(['ok' => true, 'guests' => guest_rows()]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $in = json_input();
        $ids = array_map('intval', (array) ($in['ids'] ?? []));
        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM guests WHERE id IN ($marks)")->execute($ids);
        }
        json_response(['ok' => true, 'guests' => guest_rows()]);
    }

    if ($method === 'POST' && $action === 'import') {
        $in = json_input();
        $rows = is_array($in['rows'] ?? null) ? array_values($in['rows']) : [];
        if (!$rows) {
            json_error('Tidak ada baris untuk diimpor.');
        }
        if (count($rows) > 3000) {
            json_error('Maksimal 3.000 baris per impor.');
        }
        $mode = in_array($in['mode'] ?? '', ['skip', 'update', 'import'], true) ? $in['mode'] : 'skip';
        $report = import_rows($rows, $mode, !empty($in['dry_run']));
        json_response(['ok' => true, 'report' => $report, 'guests' => empty($in['dry_run']) ? guest_rows() : null]);
    }

    json_error('Permintaan tidak dikenal.', 404);
});
