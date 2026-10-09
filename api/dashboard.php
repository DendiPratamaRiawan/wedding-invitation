<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

api_guard(function (): void {
    require_admin_api();
    $pdo = db();
    $guests = $pdo->query('SELECT COUNT(*) total, SUM(is_active) active, SUM(opened_at IS NOT NULL) opened FROM guests')->fetch();
    $rsvp = $pdo->query("SELECT
            SUM(r.status = 'hadir') hadir, SUM(r.status = 'tidak_hadir') tidak_hadir, SUM(r.status = 'ragu') ragu,
            COALESCE(SUM(CASE WHEN r.status = 'hadir' THEN r.guest_count END), 0) people, COUNT(r.id) answered
        FROM guests g LEFT JOIN rsvps r ON r.guest_id = g.id")->fetch();
    $wishes = $pdo->query("SELECT COUNT(*) total, SUM(status = 'pending') pending, SUM(status = 'approved') approved FROM wishes")->fetch();
    $settings = $pdo->query('SELECT groom_name, bride_name, published, published_at, bride_family_confirmed FROM wedding_settings WHERE id = 1')->fetch();
    $next = $pdo->query('SELECT event_type, event_date, start_time, timezone FROM events WHERE event_date IS NOT NULL ORDER BY event_date, start_time LIMIT 1')->fetch();
    $recent = $pdo->query('SELECT g.guest_name, r.status, r.guest_count, r.updated_at FROM rsvps r JOIN guests g ON g.id = r.guest_id ORDER BY r.updated_at DESC LIMIT 6')->fetchAll();

    $int = static fn($v) => (int) ($v ?? 0);
    json_response([
        'ok' => true,
        'guests' => ['total' => $int($guests['total']), 'active' => $int($guests['active']), 'opened' => $int($guests['opened'])],
        'rsvp' => [
            'hadir' => $int($rsvp['hadir']), 'tidak_hadir' => $int($rsvp['tidak_hadir']), 'ragu' => $int($rsvp['ragu']),
            'belum' => $int($guests['total']) - $int($rsvp['answered']), 'people' => $int($rsvp['people']),
        ],
        'wishes' => ['total' => $int($wishes['total']), 'pending' => $int($wishes['pending']), 'approved' => $int($wishes['approved'])],
        'settings' => [
            'couple' => $settings['groom_name'] . ' & ' . $settings['bride_name'],
            'published' => (bool) $settings['published'],
            'published_at' => $settings['published_at'],
            'bride_family_confirmed' => (bool) $settings['bride_family_confirmed'],
        ],
        'next_event' => $next ?: null,
        'recent_rsvps' => $recent,
        'app_url' => app_url(),
    ]);
});
