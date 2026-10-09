<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Konfigurasi belum dibuat. Salin includes/config.sample.php menjadi includes/config.php.');
}
$GLOBALS['APP_CONFIG'] = require $configFile;

date_default_timezone_set('Asia/Jakarta');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/defaults.php';

function config(string $key, $default = null)
{
    return $GLOBALS['APP_CONFIG'][$key] ?? $default;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            config('db_host'), (int) config('db_port', 3306), config('db_name')
        );
        $pdo = new PDO($dsn, config('db_user'), config('db_pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
    }
    return $pdo;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Path URL folder aplikasi, mis. "/wedding-invitations" atau "" bila di root domain. */
function base_path(): string
{
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $appRoot = realpath(APP_ROOT) ?: APP_ROOT;
    if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        $rel = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        return rtrim('/' . ltrim($rel, '/'), '/');
    }
    return '';
}

function app_url(): string
{
    $configured = rtrim((string) config('app_url', ''), '/');
    if ($configured !== '') {
        return $configured;
    }
    $scheme = is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . base_path();
}

function guest_link(string $code): string
{
    return app_url() . '/i/' . rawurlencode($code);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function send_security_headers(bool $admin = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    $csp = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; media-src 'self'; "
         . "connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
    header('Content-Security-Policy: ' . $csp);
    if ($admin) {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store');
    }
}

/* ---------- Sesi & autentikasi ---------- */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('wi_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => is_https() || (bool) config('force_https', false),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    // Sesi kedaluwarsa setelah 8 jam tidak aktif
    $now = time();
    if (isset($_SESSION['last_seen']) && $now - $_SESSION['last_seen'] > 8 * 3600) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = $now;
}

function current_admin(): ?array
{
    start_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    return ['id' => (int) $_SESSION['admin_id'], 'username' => (string) $_SESSION['admin_username']];
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Untuk halaman admin (HTML): alihkan ke login bila belum masuk. */
function require_admin_page(): array
{
    send_security_headers(true);
    $admin = current_admin();
    if (!$admin) {
        header('Location: login.php');
        exit;
    }
    return $admin;
}

/** Untuk endpoint API admin: tolak tanpa sesi + wajib token CSRF pada permintaan yang mengubah data. */
function require_admin_api(): array
{
    $admin = current_admin();
    if (!$admin) {
        json_error('Tidak terautentikasi.', 401);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
            json_error('Token keamanan tidak valid. Muat ulang halaman.', 419);
        }
    }
    return $admin;
}

/* ---------- JSON API ---------- */

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status = 400, array $extra = []): void
{
    json_response(['ok' => false, 'error' => $message] + $extra, $status);
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return $_POST;
    }
    if (strlen($raw) > 5 * 1024 * 1024) {
        json_error('Data terlalu besar.', 413);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_error('Format data tidak valid.');
    }
    return $data;
}

function api_guard(callable $fn): void
{
    try {
        $fn();
    } catch (Throwable $ex) {
        error_log('[wedding-api] ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
        json_error('Terjadi kesalahan pada server.', 500);
    }
}

/* ---------- Validasi ---------- */

function clean_text($value, int $max, bool $multiline = false): string
{
    $value = is_scalar($value) ? (string) $value : '';
    $value = str_replace("\r\n", "\n", $value);
    // Buang karakter kontrol (kecuali baris baru untuk teks multibaris)
    $value = preg_replace($multiline ? '/[\x00-\x09\x0B-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    $value = trim($value);
    if ($multiline) {
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;
    } else {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
    return mb_substr($value, 0, $max);
}

function clean_url($value): string
{
    $value = trim(is_string($value) ? $value : '');
    if ($value === '') {
        return '';
    }
    if (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $value)) {
        return '';
    }
    return mb_substr($value, 0, 500);
}

function name_key(string $name): string
{
    $name = mb_strtolower(trim($name));
    return mb_substr(preg_replace('/\s+/u', ' ', $name) ?? $name, 0, 160);
}

/* ---------- Rate limiting ---------- */

/** Kembalikan false bila batas terlampaui. $record=true mencatat percobaan ini. */
function rate_limit(string $bucket, string $ident, int $max, int $windowSeconds, bool $record = true): bool
{
    $hash = hash('sha256', $ident . '|' . $bucket);
    $pdo = db();
    if (random_int(1, 50) === 1) {
        $pdo->exec('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND ident = ? AND created_at > (NOW() - INTERVAL ? SECOND)');
    $stmt->execute([$bucket, $hash, $windowSeconds]);
    if ((int) $stmt->fetchColumn() >= $max) {
        return false;
    }
    if ($record) {
        $pdo->prepare('INSERT INTO rate_limits (bucket, ident) VALUES (?, ?)')->execute([$bucket, $hash]);
    }
    return true;
}

function rate_limit_clear(string $bucket, string $ident): void
{
    db()->prepare('DELETE FROM rate_limits WHERE bucket = ? AND ident = ?')
        ->execute([$bucket, hash('sha256', $ident . '|' . $bucket)]);
}

/* ---------- Kode undangan ---------- */

function generate_invitation_code(int $length = 8): string
{
    // Tanpa karakter yang mirip (0/O, 1/l/I) agar mudah dibaca bila diketik ulang
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;
    $stmt = db()->prepare('SELECT 1 FROM guests WHERE invitation_code = ?');
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());
    return $code;
}

/* ---------- Data undangan ---------- */

function load_settings_row(): array
{
    $row = db()->query('SELECT * FROM wedding_settings WHERE id = 1')->fetch();
    if (!$row) {
        throw new RuntimeException('Data pengaturan belum ada. Jalankan installer.');
    }
    $row['content'] = array_replace_recursive(default_content(), json_decode((string) $row['content'], true) ?: []);
    $row['theme_settings'] = array_replace(default_theme(), json_decode((string) $row['theme_settings'], true) ?: []);
    return $row;
}

function load_events(): array
{
    return db()->query('SELECT * FROM events ORDER BY sort_order, event_date, start_time, id')->fetchAll();
}

function load_gallery(): array
{
    return db()->query('SELECT id, file_path, thumb_path, width, height, caption FROM gallery ORDER BY sort_order, id')->fetchAll();
}

/** Data draf (sumber kebenaran admin) dalam bentuk yang sama dengan snapshot publikasi. */
function build_draft_payload(): array
{
    $s = load_settings_row();
    return [
        'groom' => [
            'name' => $s['groom_name'],
            'full_name' => $s['groom_full_name'],
            'family' => $s['groom_family'],
        ],
        'bride' => [
            'name' => $s['bride_name'],
            'full_name' => $s['bride_full_name'],
            'family' => $s['bride_family'],
            'family_confirmed' => (bool) $s['bride_family_confirmed'],
        ],
        'opening_text' => (string) $s['opening_text'],
        'closing_text' => (string) $s['closing_text'],
        'theme' => $s['theme_settings'],
        'content' => $s['content'],
        'events' => array_map(static function (array $ev): array {
            return [
                'id' => (int) $ev['id'],
                'type' => $ev['event_type'],
                'date' => $ev['event_date'],
                'start' => $ev['start_time'] ? substr($ev['start_time'], 0, 5) : null,
                'end' => $ev['end_time'] ? substr($ev['end_time'], 0, 5) : null,
                'end_label' => $ev['end_label'],
                'timezone' => $ev['timezone'],
                'venue' => $ev['venue_name'],
                'address' => (string) $ev['address'],
                'maps_url' => $ev['maps_url'],
            ];
        }, load_events()),
    ];
}

/** Hapus data yang hanya relevan untuk admin sebelum dikirim ke halaman publik. */
function public_payload(array $payload): array
{
    unset($payload['content']['wa_template'], $payload['bride']['family_confirmed']);
    if (empty($payload['content']['sections']['gift'])) {
        $payload['content']['gift'] = ['intro' => '', 'accounts' => []];
    }
    return $payload;
}

const TIMEZONES = [
    'Asia/Jakarta' => ['label' => 'WIB', 'offset' => '+07:00'],
    'Asia/Makassar' => ['label' => 'WITA', 'offset' => '+08:00'],
    'Asia/Jayapura' => ['label' => 'WIT', 'offset' => '+09:00'],
];
