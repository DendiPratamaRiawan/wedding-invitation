<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/images.php';

const PUBLISH_CHECKS = [
    'names' => 'Nama lengkap mempelai dan keluarga sudah benar',
    'dates' => 'Tanggal, jam, dan zona waktu acara sudah benar',
    'maps' => 'Alamat dan tautan peta sudah diuji',
    'religious' => 'Teks salam, ayat, dan doa sudah diperiksa',
    'media' => 'Foto dan teks sudah disetujui',
];

function publish_problems(array $draft): array
{
    $problems = [];
    if (trim($draft['bride']['family']) === '' || trim($draft['groom']['family']) === '') {
        $problems[] = 'Nama keluarga mempelai belum diisi.';
    }
    if (!$draft['events']) {
        $problems[] = 'Minimal satu acara harus diisi.';
    }
    foreach ($draft['events'] as $ev) {
        $label = $ev['type'] ?: 'Acara';
        if (!$ev['date'] || !$ev['start']) {
            $problems[] = "$label: tanggal dan jam mulai belum diisi.";
        }
        if (trim($ev['venue']) === '' || trim($ev['address']) === '') {
            $problems[] = "$label: nama tempat dan alamat belum diisi.";
        }
    }
    $gift = $draft['content']['gift'];
    if (!empty($draft['content']['sections']['gift']) && !$gift['accounts']) {
        $problems[] = 'Amplop digital aktif tetapi belum ada rekening/metode hadiah.';
    }
    return $problems;
}

function sanitize_content(array $in, array $current): array
{
    $c = $current;
    $textFields = [
        'basmalah' => 200, 'cover_title' => 80, 'guest_prefix' => 40, 'salam_open' => 120,
        'quote_source' => 80, 'groom_relation' => 40, 'bride_relation' => 40,
        'salam_close' => 120, 'closing_signature' => 120,
    ];
    foreach ($textFields as $key => $max) {
        if (array_key_exists($key, $in)) {
            $c[$key] = clean_text($in[$key], $max);
        }
    }
    $multi = [
        'quote_arabic' => 1000, 'quote_translation' => 1500, 'groom_bio' => 600, 'bride_bio' => 600,
        'closing_prayer_arabic' => 1000, 'closing_prayer_translation' => 1500, 'wa_template' => 2000,
    ];
    foreach ($multi as $key => $max) {
        if (array_key_exists($key, $in)) {
            $c[$key] = clean_text($in[$key], $max, true);
        }
    }
    foreach (['groom_photo', 'bride_photo', 'couple_photo', 'music_path'] as $key) {
        if (array_key_exists($key, $in)) {
            $v = is_string($in[$key]) ? $in[$key] : '';
            $c[$key] = preg_match('#^uploads/[a-z]+/[A-Za-z0-9_-]+\.(webp|jpg|jpeg|png|mp3|m4a|ogg)$#', $v) ? $v : '';
        }
    }
    if (isset($in['sections']) && is_array($in['sections'])) {
        foreach (array_keys(default_content()['sections']) as $key) {
            $c['sections'][$key] = !empty($in['sections'][$key]);
        }
    }
    if (array_key_exists('rsvp_max_companions', $in)) {
        $c['rsvp_max_companions'] = max(0, min(10, (int) $in['rsvp_max_companions']));
    }
    if (array_key_exists('wishes_auto_approve', $in)) {
        $c['wishes_auto_approve'] = !empty($in['wishes_auto_approve']);
    }
    if (array_key_exists('music_on_open', $in)) {
        $c['music_on_open'] = !empty($in['music_on_open']);
    }
    if (isset($in['story']) && is_array($in['story'])) {
        $c['story'] = [];
        foreach (array_slice($in['story'], 0, 12) as $item) {
            if (!is_array($item)) continue;
            $title = clean_text($item['title'] ?? '', 100);
            $text = clean_text($item['text'] ?? '', 800, true);
            if ($title === '' && $text === '') continue;
            $c['story'][] = ['title' => $title, 'date_label' => clean_text($item['date_label'] ?? '', 60), 'text' => $text];
        }
    }
    if (isset($in['gift']) && is_array($in['gift'])) {
        $c['gift']['intro'] = clean_text($in['gift']['intro'] ?? '', 600, true);
        $c['gift']['accounts'] = [];
        foreach (array_slice((array) ($in['gift']['accounts'] ?? []), 0, 6) as $acc) {
            if (!is_array($acc)) continue;
            $bank = clean_text($acc['bank'] ?? '', 60);
            $number = clean_text($acc['number'] ?? '', 60);
            if ($bank === '' || $number === '') continue;
            $c['gift']['accounts'][] = ['bank' => $bank, 'number' => $number, 'holder' => clean_text($acc['holder'] ?? '', 100)];
        }
    }
    return $c;
}

function save_events(array $events): void
{
    $pdo = db();
    $keep = [];
    $insert = $pdo->prepare('INSERT INTO events (event_type, event_date, start_time, end_time, end_label, timezone, venue_name, address, maps_url, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $update = $pdo->prepare('UPDATE events SET event_type=?, event_date=?, start_time=?, end_time=?, end_label=?, timezone=?, venue_name=?, address=?, maps_url=?, sort_order=? WHERE id=?');
    foreach (array_values(array_slice($events, 0, 6)) as $i => $ev) {
        if (!is_array($ev)) continue;
        $type = clean_text($ev['type'] ?? '', 80);
        if ($type === '') {
            json_error('Nama acara wajib diisi (mis. Akad Nikah).');
        }
        $date = is_string($ev['date'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ev['date']) ? $ev['date'] : null;
        if ($date && !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
            json_error("$type: tanggal tidak valid.");
        }
        $time = static fn($t) => is_string($t) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t) ? $t . ':00' : null;
        $start = $time($ev['start'] ?? null);
        $end = $time($ev['end'] ?? null);
        if ($start && $end && $end <= $start) {
            json_error("$type: jam selesai harus setelah jam mulai.");
        }
        $tz = array_key_exists($ev['timezone'] ?? '', TIMEZONES) ? $ev['timezone'] : 'Asia/Jakarta';
        $mapsRaw = trim((string) ($ev['maps_url'] ?? ''));
        $maps = clean_url($mapsRaw);
        if ($mapsRaw !== '' && $maps === '') {
            json_error("$type: tautan peta harus berupa URL http/https yang valid.");
        }
        $params = [
            $type, $date, $start, $end, clean_text($ev['end_label'] ?? '', 40), $tz,
            clean_text($ev['venue'] ?? '', 160), clean_text($ev['address'] ?? '', 600, true), $maps, $i + 1,
        ];
        $id = (int) ($ev['id'] ?? 0);
        if ($id > 0) {
            $update->execute([...$params, $id]);
            if ($update->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT 1 FROM events WHERE id = ?');
                $exists->execute([$id]);
                if (!$exists->fetchColumn()) {
                    $insert->execute($params);
                    $id = (int) $pdo->lastInsertId();
                }
            }
        } else {
            $insert->execute($params);
            $id = (int) $pdo->lastInsertId();
        }
        $keep[] = $id;
    }
    if ($keep) {
        $in = implode(',', array_fill(0, count($keep), '?'));
        $pdo->prepare("DELETE FROM events WHERE id NOT IN ($in)")->execute($keep);
    } else {
        $pdo->exec('DELETE FROM events');
    }
}

api_guard(function (): void {
    require_admin_api();
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    $pdo = db();

    if ($method === 'GET' && $action === '') {
        $row = load_settings_row();
        $draft = build_draft_payload();
        json_response([
            'ok' => true,
            'draft' => $draft,
            'gallery' => load_gallery(),
            'presets' => theme_presets(),
            'timezones' => TIMEZONES,
            'publish_checks' => PUBLISH_CHECKS,
            'problems' => publish_problems($draft),
            'status' => [
                'published' => (bool) $row['published'],
                'published_at' => $row['published_at'],
                'updated_at' => $row['updated_at'],
                'has_unpublished_changes' => $row['published']
                    ? json_decode((string) $row['published_snapshot'], true) != $draft
                    : true,
            ],
            'app_url' => app_url(),
        ]);
    }

    if ($method === 'POST' && $action === 'save') {
        $in = json_input();
        $row = load_settings_row();
        $groom = is_array($in['groom'] ?? null) ? $in['groom'] : [];
        $bride = is_array($in['bride'] ?? null) ? $in['bride'] : [];
        $groomName = clean_text($groom['name'] ?? $row['groom_name'], 80);
        $brideName = clean_text($bride['name'] ?? $row['bride_name'], 80);
        if ($groomName === '' || $brideName === '') {
            json_error('Nama tampilan kedua mempelai wajib diisi.');
        }
        $brideFamily = clean_text($bride['family'] ?? $row['bride_family'], 200);
        // Konfirmasi otomatis gugur bila nama keluarga diubah tanpa dikonfirmasi ulang
        $confirmed = !empty($bride['family_confirmed']);
        $preset = $in['theme']['preset'] ?? $row['theme_settings']['preset'];
        if (!array_key_exists($preset, theme_presets())) {
            $preset = 'purnama';
        }
        $content = sanitize_content(is_array($in['content'] ?? null) ? $in['content'] : [], $row['content']);

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE wedding_settings SET groom_name=?, groom_full_name=?, groom_family=?, bride_name=?, bride_full_name=?,
                bride_family=?, bride_family_confirmed=?, opening_text=?, closing_text=?, theme_settings=?, content=? WHERE id=1')
            ->execute([
                $groomName,
                clean_text($groom['full_name'] ?? $row['groom_full_name'], 160),
                clean_text($groom['family'] ?? $row['groom_family'], 200),
                $brideName,
                clean_text($bride['full_name'] ?? $row['bride_full_name'], 160),
                $brideFamily,
                $confirmed ? 1 : 0,
                clean_text($in['opening_text'] ?? $row['opening_text'], 1500, true),
                clean_text($in['closing_text'] ?? $row['closing_text'], 1500, true),
                json_encode(['preset' => $preset]),
                json_encode($content, JSON_UNESCAPED_UNICODE),
            ]);
        if (isset($in['events']) && is_array($in['events'])) {
            save_events($in['events']);
        }
        $pdo->exec('UPDATE wedding_settings SET updated_at = NOW() WHERE id = 1');
        $pdo->commit();
        $draft = build_draft_payload();
        json_response(['ok' => true, 'draft' => $draft, 'problems' => publish_problems($draft)]);
    }

    if ($method === 'POST' && $action === 'publish') {
        $in = json_input();
        $draft = build_draft_payload();
        $problems = publish_problems($draft);
        $checks = is_array($in['checks'] ?? null) ? $in['checks'] : [];
        foreach (PUBLISH_CHECKS as $key => $label) {
            if (empty($checks[$key])) {
                $problems[] = "Belum dicentang: $label.";
            }
        }
        if (!empty($draft['content']['sections']['gift']) && empty($checks['gift'])) {
            $problems[] = 'Belum dicentang: nomor rekening/metode hadiah sudah diperiksa.';
        }
        if ($problems) {
            json_error('Undangan belum dapat dipublikasikan.', 422, ['problems' => $problems]);
        }
        $pdo->prepare('UPDATE wedding_settings SET published = 1, published_snapshot = ?, published_at = NOW(), updated_at = updated_at WHERE id = 1')
            ->execute([json_encode($draft, JSON_UNESCAPED_UNICODE)]);
        json_response(['ok' => true]);
    }

    if ($method === 'POST' && $action === 'unpublish') {
        $pdo->exec('UPDATE wedding_settings SET published = 0, updated_at = updated_at WHERE id = 1');
        json_response(['ok' => true]);
    }

    if ($method === 'POST' && $action === 'upload') {
        $kind = $_POST['kind'] ?? '';
        if (!in_array($kind, ['groom', 'bride', 'couple', 'gallery', 'music'], true)) {
            json_error('Jenis unggahan tidak dikenal.');
        }
        $file = $_FILES['file'] ?? null;
        if (!$file || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            json_error('Berkas gagal diunggah (maks. ' . ini_get('upload_max_filesize') . ').');
        }
        if ($kind === 'music') {
            $path = store_audio($file);
            json_response(['ok' => true, 'path' => $path]);
        }
        $maxSide = $kind === 'gallery' ? 1600 : 900;
        $img = store_image($file, $kind === 'gallery' ? 'gallery' : 'profile', $maxSide, $kind === 'gallery' ? 600 : 0);
        if ($kind === 'gallery') {
            $next = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery')->fetchColumn();
            $pdo->prepare('INSERT INTO gallery (file_path, thumb_path, width, height, caption, sort_order) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$img['path'], $img['thumb'], $img['width'], $img['height'], clean_text($_POST['caption'] ?? '', 160), $next]);
            json_response(['ok' => true, 'gallery' => load_gallery()]);
        }
        json_response(['ok' => true, 'path' => $img['path']]);
    }

    if ($method === 'POST' && $action === 'gallery') {
        $in = json_input();
        if (isset($in['order']) && is_array($in['order'])) {
            $stmt = $pdo->prepare('UPDATE gallery SET sort_order = ? WHERE id = ?');
            foreach (array_values($in['order']) as $i => $id) {
                $stmt->execute([$i + 1, (int) $id]);
            }
        }
        if (isset($in['caption'], $in['id'])) {
            $pdo->prepare('UPDATE gallery SET caption = ? WHERE id = ?')->execute([clean_text($in['caption'], 160), (int) $in['id']]);
        }
        if (isset($in['delete'])) {
            $stmt = $pdo->prepare('SELECT file_path, thumb_path FROM gallery WHERE id = ?');
            $stmt->execute([(int) $in['delete']]);
            if ($photo = $stmt->fetch()) {
                $pdo->prepare('DELETE FROM gallery WHERE id = ?')->execute([(int) $in['delete']]);
                delete_upload($photo['file_path']);
                delete_upload($photo['thumb_path']);
            }
        }
        json_response(['ok' => true, 'gallery' => load_gallery()]);
    }

    json_error('Permintaan tidak dikenal.', 404);
});
