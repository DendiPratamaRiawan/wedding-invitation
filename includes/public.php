<?php
declare(strict_types=1);

/** Konten yang dilihat publik: langsung data tersimpan terbaru (tanpa tahap publikasi). */
function public_content(bool $preview = false): ?array
{
    return build_draft_payload();
}

function find_active_guest(string $code): ?array
{
    if (!preg_match('/^[A-Za-z0-9]{4,16}$/', $code)) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, guest_name, max_companions FROM guests WHERE invitation_code = ? AND is_active = 1');
    $stmt->execute([$code]);
    return $stmt->fetch() ?: null;
}
