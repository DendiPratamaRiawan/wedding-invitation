<?php
declare(strict_types=1);

const ID_DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
const ID_MONTHS = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function id_date(?string $ymd, bool $withDay = true): string
{
    if (!$ymd) return '';
    $ts = strtotime($ymd . ' 12:00:00');
    if ($ts === false) return '';
    $text = (int) date('j', $ts) . ' ' . ID_MONTHS[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withDay ? ID_DAYS[(int) date('w', $ts)] . ', ' . $text : $text;
}

function short_date(?string $ymd): string
{
    if (!$ymd) return '';
    $ts = strtotime($ymd . ' 12:00:00');
    return $ts === false ? '' : date('d . m . Y', $ts);
}

function event_time_label(array $ev): string
{
    if (!$ev['start']) return '';
    $tz = TIMEZONES[$ev['timezone']]['label'] ?? 'WIB';
    $start = str_replace(':', '.', $ev['start']);
    if ($ev['end']) {
        return $start . ' – ' . str_replace(':', '.', $ev['end']) . ' ' . $tz;
    }
    return $start . ' ' . $tz . ' – ' . ($ev['end_label'] !== '' ? $ev['end_label'] : 'selesai');
}

/** Teks multibaris aman: escape lalu pertahankan baris baru lewat CSS (white-space: pre-line). */
function text_block(string $value): string
{
    return e($value);
}

/** "Bapak/Ibu Nama" — tanpa awalan bila nama sudah diawali sapaan/gelar (Bapak, Ibu, Keluarga, Dr., dll.). */
function guest_greeting(string $prefix, string $name): string
{
    $honorific = '/^(bapak|bpk\.?|pak|ibu|bu|bunda|saudara|saudari|sdr\.?|sdri\.?|keluarga|kel\.|tuan|nyonya|tn\.?|ny\.?|kak|mas|mbak|dr\.?|drs\.?|prof\.?|h\.|hj\.|kh\.?|ustadz|ustadzah|ust\.?)(\s|$)/iu';
    $name = trim($name);
    return preg_match($honorific, $name) || trim($prefix) === '' ? $name : trim($prefix) . ' ' . $name;
}

function initial(string $name): string
{
    return mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '•';
}
