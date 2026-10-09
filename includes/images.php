<?php
declare(strict_types=1);

/**
 * Validasi, perkecil, dan simpan gambar sebagai WebP (dikompresi).
 * Berkas asli tidak disimpan; metadata EXIF ikut terbuang.
 */
function store_image(array $file, string $folder, int $maxSide, int $thumbSide = 0): array
{
    if ($file['size'] > 12 * 1024 * 1024) {
        json_error('Ukuran gambar maksimal 12 MB.');
    }
    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
    if (!$info || !in_array($info[2], $allowed, true)) {
        json_error('Format gambar harus JPG, PNG, atau WebP.');
    }
    if ($info[0] * $info[1] > 40000000) {
        json_error('Resolusi gambar terlalu besar.');
    }
    $src = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
        IMAGETYPE_PNG => @imagecreatefrompng($file['tmp_name']),
        IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']),
    };
    if (!$src) {
        json_error('Gambar tidak dapat dibaca.');
    }
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
        if ($rot) {
            $src = imagerotate($src, $rot, 0);
        }
    }

    $dir = APP_ROOT . '/uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(10));
    [$w, $h] = resize_save($src, "$dir/$name.webp", $maxSide, 80);
    $thumb = "uploads/$folder/$name.webp";
    if ($thumbSide > 0) {
        resize_save($src, "$dir/{$name}_t.webp", $thumbSide, 72);
        $thumb = "uploads/$folder/{$name}_t.webp";
    }
    imagedestroy($src);
    return ['path' => "uploads/$folder/$name.webp", 'thumb' => $thumb, 'width' => $w, 'height' => $h];
}

function resize_save($src, string $dest, int $maxSide, int $quality): array
{
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, $maxSide / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagewebp($dst, $dest, $quality);
    imagedestroy($dst);
    return [$nw, $nh];
}

function store_audio(array $file): string
{
    if ($file['size'] > 15 * 1024 * 1024) {
        json_error('Ukuran audio maksimal 15 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = [
        'audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3', 'audio/x-mpeg' => 'mp3', 'audio/mpeg3' => 'mp3',
        'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'video/mp4' => 'm4a', 'audio/aac' => 'm4a',
        'audio/ogg' => 'ogg', 'application/ogg' => 'ogg',
    ][$mime] ?? null;
    // Beberapa MP3 terdeteksi generik; terima bila berawalan tag ID3 atau frame MPEG
    if (!$ext && $mime === 'application/octet-stream') {
        $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 3);
        if (strncmp($head, 'ID3', 3) === 0 || (strlen($head) >= 2 && ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0)) {
            $ext = 'mp3';
        }
    }
    if (!$ext) {
        json_error('Format audio harus MP3, M4A, atau OGG.');
    }
    $dir = APP_ROOT . '/uploads/audio';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        json_error('Gagal menyimpan audio.');
    }
    return "uploads/audio/$name";
}

function delete_upload(string $relPath): void
{
    if (!preg_match('#^uploads/[a-z]+/[A-Za-z0-9_-]+\.[a-z0-9]+$#', $relPath)) {
        return;
    }
    $full = APP_ROOT . '/' . $relPath;
    if (is_file($full)) {
        @unlink($full);
    }
}
