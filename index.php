<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/public.php';
require __DIR__ . '/includes/format.php';

send_security_headers();

$preview = isset($_GET['preview']) && current_admin() !== null;
try {
    $data = public_content($preview);
} catch (Throwable $ex) {
    error_log('[wedding] ' . $ex->getMessage());
    $data = null;
}
$base = base_path() . '/';

$code = is_string($_GET['code'] ?? null) ? $_GET['code'] : '';
$guest = ($data && $code !== '') ? find_active_guest($code) : null;
if (!$guest) {
    $code = '';
}

if (!$data) {
    header('X-Robots-Tag: noindex');
    ?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Undangan Pernikahan</title>
<base href="<?= e($base) ?>">
<link rel="stylesheet" href="css/style.css">
</head>
<body class="theme-purnama">
<main class="soon">
  <div class="moon moon--sm" aria-hidden="true"></div>
  <p class="eyebrow">Undangan Pernikahan</p>
  <h1 class="script-title">Mohon Maaf</h1>
  <p>Undangan sedang tidak dapat dimuat. Silakan coba beberapa saat lagi.</p>
</main>
</body>
</html>
<?php
    exit;
}

$c = $data['content'];
$s = $c['sections'];
$groom = $data['groom'];
$bride = $data['bride'];
$couple = $groom['name'] . ' & ' . $bride['name'];
$events = $data['events'];
$firstDate = $events[0]['date'] ?? null;
$preset = $data['theme']['preset'] ?? 'purnama';
$gallery = $s['gallery'] ? load_gallery() : [];
$maxCompanions = $guest && $guest['max_companions'] !== null ? (int) $guest['max_companions'] : (int) $c['rsvp_max_companions'];

$jsData = [
    'code' => $code,
    'preview' => $preview,
    'couple' => $couple,
    'guestName' => $guest['guest_name'] ?? '',
    'maxGuests' => 1 + $maxCompanions,
    'events' => array_map(static fn($ev) => [
        'type' => $ev['type'], 'date' => $ev['date'], 'start' => $ev['start'], 'end' => $ev['end'],
        'offset' => TIMEZONES[$ev['timezone']]['offset'] ?? '+07:00', 'venue' => $ev['venue'], 'address' => $ev['address'],
    ], $events),
];
$ogDesc = 'Undangan pernikahan ' . $couple . ($firstDate ? ' — ' . id_date($firstDate) : '');
$photo = static fn(string $p) => $p !== '' && is_file(APP_ROOT . '/' . $p) ? $p : '';
?><!doctype html>
<html lang="id" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Undangan Pernikahan <?= e($couple) ?></title>
<meta name="description" content="<?= e($ogDesc) ?>">
<meta name="theme-color" content="#25324A">
<?php if ($preview || $guest): ?><meta name="robots" content="noindex"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($couple) ?> — Undangan Pernikahan">
<meta property="og:description" content="<?= e($ogDesc) ?>">
<?php if ($photo($c['couple_photo'])): ?><meta property="og:image" content="<?= e(app_url() . '/' . $c['couple_photo']) ?>"><?php endif; ?>
<base href="<?= e($base) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/responsive.css">
<script src="js/main.js" defer></script>
<script src="js/countdown.js" defer></script>
<script src="js/invitation.js" defer></script>
</head>
<body class="theme-<?= e($preset) ?> is-locked">
<?php require __DIR__ . '/includes/sprite.php'; ?>
<script type="application/json" id="invitation-data"><?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<!-- Kelopak bunga berjatuhan (dekoratif, hanya transform & opacity) -->
<div class="petals" aria-hidden="true">
  <?php for ($i = 0; $i < 12; $i++): ?>
  <span class="petal"><svg><use href="#petal-<?= $i % 3 === 2 ? 'leaf' : ($i % 2 ? 'b' : 'a') ?>"/></svg></span>
  <?php endfor; ?>
</div>

<!-- ============ SAMPUL ============ -->
<section class="cover night" id="cover" aria-label="Sampul undangan">
  <div class="stars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
  <svg class="corner corner--tl corner--sm" aria-hidden="true"><use href="#floral-corner"/></svg>
  <svg class="corner corner--tr corner--sm" aria-hidden="true"><use href="#floral-corner"/></svg>
  <svg class="corner corner--bl" aria-hidden="true"><use href="#floral-corner"/></svg>
  <svg class="corner corner--br" aria-hidden="true"><use href="#floral-corner"/></svg>
  <div class="cover__inner">
    <div class="moon" aria-hidden="true"></div>
    <p class="arabic arabic--sm" lang="ar" dir="rtl"><?= e($c['basmalah']) ?></p>
    <p class="eyebrow"><?= e($c['cover_title']) ?></p>
    <h1 class="couple-title"><span><?= e($groom['name']) ?></span><em>&amp;</em><span><?= e($bride['name']) ?></span></h1>
    <?php if ($firstDate): ?><p class="cover__date"><?= e(short_date($firstDate)) ?></p><?php endif; ?>
    <div class="cover__guest">
      <p class="muted-light">Kepada Yth.</p>
      <?php if ($guest): ?>
        <p class="guest-name"><?= e(guest_greeting($c['guest_prefix'], $guest['guest_name'])) ?></p>
      <?php else: ?>
        <p class="guest-name">Bapak/Ibu/Saudara/i<br><span>Tamu Undangan</span></p>
      <?php endif; ?>
    </div>
    <button type="button" class="btn btn-moon" id="open-invitation">
      <svg class="icon" aria-hidden="true"><use href="#i-mail"/></svg> Buka Undangan
    </button>
  </div>
</section>

<main id="content">

  <!-- ============ HERO ============ -->
  <header class="hero night">
    <div class="stars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
    <svg class="corner corner--tl" aria-hidden="true"><use href="#floral-corner"/></svg>
    <svg class="corner corner--tr" aria-hidden="true"><use href="#floral-corner"/></svg>
    <svg class="corner corner--bl corner--sm" aria-hidden="true"><use href="#floral-corner"/></svg>
    <svg class="corner corner--br corner--sm" aria-hidden="true"><use href="#floral-corner"/></svg>
    <div class="hero__inner reveal">
      <div class="moon moon--lg" aria-hidden="true">
        <?php if ($photo($c['couple_photo'])): ?>
          <img src="<?= e($c['couple_photo']) ?>" alt="Foto <?= e($couple) ?>" width="480" height="480" fetchpriority="high">
        <?php endif; ?>
      </div>
      <p class="eyebrow">Di Bawah Cahaya Purnama</p>
      <h2 class="couple-title couple-title--hero"><span><?= e($groom['name']) ?></span><em>&amp;</em><span><?= e($bride['name']) ?></span></h2>
      <?php if ($firstDate): ?><p class="hero__date"><?= e(id_date($firstDate)) ?></p><?php endif; ?>
    </div>
  </header>

  <?php if ($s['opening']): ?>
  <!-- ============ PEMBUKA ============ -->
  <section class="section section--opening" id="pembuka">
    <div class="container narrow reveal">
      <svg class="ornament" aria-hidden="true"><use href="#ornament"/></svg>
      <p class="salam"><?= e($c['salam_open']) ?></p>
      <p class="lead pre"><?= text_block($data['opening_text']) ?></p>
      <?php if ($c['quote_arabic'] || $c['quote_translation']): ?>
      <figure class="quote">
        <?php if ($c['quote_arabic']): ?><p class="arabic" lang="ar" dir="rtl"><?= e($c['quote_arabic']) ?></p><?php endif; ?>
        <blockquote class="pre"><?= text_block($c['quote_translation']) ?></blockquote>
        <?php if ($c['quote_source']): ?><figcaption><?= e($c['quote_source']) ?></figcaption><?php endif; ?>
      </figure>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($s['couple']): ?>
  <!-- ============ MEMPELAI ============ -->
  <section class="section section--couple bloom-zone" id="mempelai">
    <svg class="corner corner--tl corner--soft" aria-hidden="true"><use href="#floral-corner"/></svg>
    <svg class="corner corner--br corner--soft" aria-hidden="true"><use href="#floral-corner"/></svg>
    <div class="container">
      <h2 class="section-title reveal">Mempelai</h2>
      <svg class="sprig reveal" aria-hidden="true"><use href="#floral-sprig"/></svg>
      <div class="couple-grid">
        <?php foreach ([['p' => $groom, 'rel' => $c['groom_relation'], 'bio' => $c['groom_bio'], 'img' => $photo($c['groom_photo'])],
                        ['p' => $bride, 'rel' => $c['bride_relation'], 'bio' => $c['bride_bio'], 'img' => $photo($c['bride_photo'])]] as $i => $m): ?>
          <?php if ($i === 1): ?><div class="couple-amp reveal" aria-hidden="true">&amp;</div><?php endif; ?>
          <article class="person reveal">
            <div class="person__frame">
              <?php if ($m['img']): ?>
                <img src="<?= e($m['img']) ?>" alt="<?= e($m['p']['name']) ?>" width="320" height="320" loading="lazy" decoding="async">
              <?php else: ?>
                <span class="person__initial" aria-hidden="true"><?= e(initial($m['p']['name'])) ?></span>
              <?php endif; ?>
            </div>
            <h3 class="person__name"><?= e($m['p']['full_name'] ?: $m['p']['name']) ?></h3>
            <?php if ($m['p']['family']): ?>
              <p class="person__family"><?= e($m['rel']) ?><br><strong><?= e($m['p']['family']) ?></strong></p>
            <?php endif; ?>
            <?php if ($m['bio']): ?><p class="person__bio pre"><?= text_block($m['bio']) ?></p><?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($s['events'] && $events): ?>
  <!-- ============ ACARA ============ -->
  <section class="section section--events night" id="acara">
    <div class="stars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
    <div class="container">
      <h2 class="section-title reveal">Waktu &amp; Tempat</h2>
      <svg class="ornament reveal" aria-hidden="true"><use href="#ornament"/></svg>

      <?php if ($s['countdown'] && $firstDate): ?>
      <div class="countdown reveal" id="countdown" aria-live="off">
        <div><span data-unit="days">0</span><small>Hari</small></div>
        <div><span data-unit="hours">0</span><small>Jam</small></div>
        <div><span data-unit="minutes">0</span><small>Menit</small></div>
        <div><span data-unit="seconds">0</span><small>Detik</small></div>
      </div>
      <p class="countdown-done" id="countdown-done" hidden>Alhamdulillah, acara telah berlangsung. Terima kasih atas doa restunya.</p>
      <?php endif; ?>

      <div class="events-grid">
        <?php foreach ($events as $i => $ev): ?>
        <article class="event-card reveal">
          <h3><?= e($ev['type']) ?></h3>
          <?php if ($ev['date']): ?>
            <p class="event-card__row"><svg class="icon" aria-hidden="true"><use href="#i-cal"/></svg><?= e(id_date($ev['date'])) ?></p>
          <?php else: ?>
            <p class="event-card__row muted">Tanggal akan diumumkan</p>
          <?php endif; ?>
          <?php if ($ev['start']): ?>
            <p class="event-card__row"><svg class="icon" aria-hidden="true"><use href="#i-clock"/></svg><?= e(event_time_label($ev)) ?></p>
          <?php endif; ?>
          <?php if ($ev['venue'] || $ev['address']): ?>
            <div class="event-card__place">
              <svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg>
              <div><strong><?= e($ev['venue']) ?></strong><span class="pre"><?= text_block($ev['address']) ?></span></div>
            </div>
          <?php endif; ?>
          <div class="event-card__actions">
            <?php if ($ev['maps_url']): ?>
              <a class="btn btn-primary" href="<?= e($ev['maps_url']) ?>" target="_blank" rel="noopener noreferrer">
                <svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg> Buka Lokasi</a>
            <?php endif; ?>
            <?php if ($ev['date'] && $ev['start']): ?>
              <div class="cal-menu">
                <button type="button" class="btn btn-ghost" data-calendar="<?= $i ?>" aria-expanded="false">
                  <svg class="icon" aria-hidden="true"><use href="#i-cal"/></svg> Simpan ke Kalender</button>
                <div class="cal-menu__list" hidden>
                  <a href="#" data-cal-google="<?= $i ?>" target="_blank" rel="noopener noreferrer">Google Calendar</a>
                  <a href="#" data-cal-ics="<?= $i ?>">Kalender lain (.ics)</a>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($s['story'] && $c['story']): ?>
  <!-- ============ KISAH ============ -->
  <section class="section section--story" id="kisah">
    <div class="container narrow">
      <h2 class="section-title reveal">Kisah Kami</h2>
      <svg class="sprig reveal" aria-hidden="true"><use href="#floral-sprig"/></svg>
      <ol class="timeline">
        <?php foreach ($c['story'] as $item): ?>
        <li class="reveal">
          <?php if ($item['date_label']): ?><span class="timeline__date"><?= e($item['date_label']) ?></span><?php endif; ?>
          <h3><?= e($item['title']) ?></h3>
          <p class="pre"><?= text_block($item['text']) ?></p>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($gallery): ?>
  <!-- ============ GALERI ============ -->
  <section class="section section--gallery" id="galeri">
    <div class="container">
      <h2 class="section-title reveal">Galeri</h2>
      <svg class="ornament reveal" aria-hidden="true"><use href="#ornament"/></svg>
      <div class="gallery" id="gallery">
        <?php foreach ($gallery as $i => $g): ?>
          <a class="gallery__item reveal" href="<?= e($g['file_path']) ?>" data-index="<?= $i ?>" data-caption="<?= e($g['caption']) ?>">
            <img src="<?= e($g['thumb_path']) ?>" alt="<?= e($g['caption'] ?: 'Foto ' . ($i + 1)) ?>"
                 width="<?= (int) $g['width'] ?>" height="<?= (int) $g['height'] ?>" loading="lazy" decoding="async">
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <dialog class="lightbox" id="lightbox" aria-label="Pratinjau foto">
      <button type="button" class="lightbox__close" data-lb="close" aria-label="Tutup"><svg class="icon"><use href="#i-close"/></svg></button>
      <button type="button" class="lightbox__nav lightbox__nav--prev" data-lb="prev" aria-label="Sebelumnya"><svg class="icon"><use href="#i-left"/></svg></button>
      <figure><img alt="" id="lightbox-img"><figcaption id="lightbox-cap"></figcaption></figure>
      <button type="button" class="lightbox__nav lightbox__nav--next" data-lb="next" aria-label="Berikutnya"><svg class="icon"><use href="#i-right"/></svg></button>
    </dialog>
  </section>
  <?php endif; ?>

  <?php if ($s['rsvp']): ?>
  <!-- ============ RSVP ============ -->
  <section class="section section--rsvp bloom-zone" id="rsvp">
    <svg class="corner corner--tr corner--soft" aria-hidden="true"><use href="#floral-corner"/></svg>
    <svg class="corner corner--bl corner--soft" aria-hidden="true"><use href="#floral-corner"/></svg>
    <div class="container narrow">
      <h2 class="section-title reveal">Konfirmasi Kehadiran</h2>
      <svg class="sprig reveal" aria-hidden="true"><use href="#floral-sprig"/></svg>
      <?php if ($guest): ?>
      <form class="card form reveal" id="rsvp-form" novalidate>
        <p class="form__hello">Untuk <strong><?= e($guest['guest_name']) ?></strong></p>
        <fieldset class="choice">
          <legend>Apakah Bapak/Ibu/Saudara/i akan hadir?</legend>
          <label><input type="radio" name="status" value="hadir" required> <span>Insya Allah hadir</span></label>
          <label><input type="radio" name="status" value="tidak_hadir"> <span>Mohon maaf, tidak dapat hadir</span></label>
          <label><input type="radio" name="status" value="ragu"> <span>Belum pasti</span></label>
        </fieldset>
        <label class="field" id="rsvp-count-field" hidden>
          <span>Jumlah yang hadir (termasuk Anda)</span>
          <select name="guest_count">
            <?php for ($n = 1; $n <= 1 + $maxCompanions; $n++): ?><option value="<?= $n ?>"><?= $n ?> orang</option><?php endfor; ?>
          </select>
        </label>
        <label class="field"><span>Catatan (opsional)</span><textarea name="message" maxlength="300" rows="2"></textarea></label>
        <p class="form__error" role="alert" hidden></p>
        <button type="submit" class="btn btn-primary btn-block">Kirim Konfirmasi</button>
      </form>
      <div class="card notice-card" id="rsvp-done" hidden role="status">
        <p class="notice-card__title">Terima kasih!</p>
        <p id="rsvp-done-text"></p>
        <button type="button" class="btn btn-ghost" id="rsvp-edit">Ubah jawaban</button>
      </div>
      <?php else: ?>
      <p class="card notice-card reveal">Konfirmasi kehadiran dapat dilakukan melalui tautan undangan personal yang kami kirimkan.</p>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($s['wishes']): ?>
  <!-- ============ UCAPAN ============ -->
  <section class="section section--wishes" id="ucapan">
    <div class="container narrow">
      <h2 class="section-title reveal">Ucapan &amp; Doa</h2>
      <svg class="ornament reveal" aria-hidden="true"><use href="#ornament"/></svg>
      <form class="card form reveal" id="wish-form" novalidate>
        <label class="field"><span>Nama</span>
          <input name="name" maxlength="60" required autocomplete="name" value="<?= e($guest['guest_name'] ?? '') ?>"></label>
        <label class="field"><span>Ucapan &amp; doa</span>
          <textarea name="message" maxlength="500" rows="3" required></textarea>
          <small class="counter"><span id="wish-count">0</span>/500</small></label>
        <label class="hp" aria-hidden="true">Website <input name="website" tabindex="-1" autocomplete="off"></label>
        <p class="form__error" role="alert" hidden></p>
        <p class="form__success" role="status" hidden></p>
        <button type="submit" class="btn btn-primary btn-block">Kirim Ucapan</button>
      </form>
      <ul class="wishes" id="wish-list" aria-live="polite"></ul>
      <button type="button" class="btn btn-ghost btn-block" id="wish-more" hidden>Tampilkan lebih banyak</button>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($s['gift'] && $c['gift']['accounts']): ?>
  <!-- ============ AMPLOP DIGITAL ============ -->
  <section class="section section--gift" id="hadiah">
    <div class="container narrow">
      <h2 class="section-title reveal">Tanda Kasih</h2>
      <svg class="sprig reveal" aria-hidden="true"><use href="#floral-sprig"/></svg>
      <p class="lead pre reveal"><?= text_block($c['gift']['intro']) ?></p>
      <div class="gift-grid">
        <?php foreach ($c['gift']['accounts'] as $acc): ?>
        <div class="card gift-card reveal">
          <svg class="icon icon--lg" aria-hidden="true"><use href="#i-gift"/></svg>
          <p class="gift-card__bank"><?= e($acc['bank']) ?></p>
          <p class="gift-card__number"><?= e($acc['number']) ?></p>
          <?php if ($acc['holder']): ?><p class="gift-card__holder">a.n. <?= e($acc['holder']) ?></p><?php endif; ?>
          <button type="button" class="btn btn-ghost" data-copy="<?= e($acc['number']) ?>">
            <svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg> Salin</button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($s['closing']): ?>
  <!-- ============ PENUTUP ============ -->
  <section class="section section--closing night" id="penutup">
    <div class="stars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
    <svg class="corner corner--bl" aria-hidden="true"><use href="#floral-corner"/></svg>
    <svg class="corner corner--br" aria-hidden="true"><use href="#floral-corner"/></svg>
    <div class="container narrow reveal">
      <div class="moon moon--sm" aria-hidden="true"></div>
      <p class="lead pre"><?= text_block($data['closing_text']) ?></p>
      <?php if ($c['closing_prayer_arabic']): ?><p class="arabic" lang="ar" dir="rtl"><?= e($c['closing_prayer_arabic']) ?></p><?php endif; ?>
      <?php if ($c['closing_prayer_translation']): ?><p class="prayer pre"><?= text_block($c['closing_prayer_translation']) ?></p><?php endif; ?>
      <p class="salam"><?= e($c['salam_close']) ?></p>
      <p class="muted-light"><?= e($c['closing_signature']) ?></p>
      <p class="couple-title couple-title--closing"><span><?= e($groom['name']) ?></span><em>&amp;</em><span><?= e($bride['name']) ?></span></p>
    </div>
  </section>
  <?php endif; ?>

  <footer class="site-footer"><p><?= e($couple) ?><?= $firstDate ? ' · ' . e(date('Y', strtotime($firstDate))) : '' ?></p></footer>
</main>

<?php if ($s['music'] && $c['music_path'] && is_file(APP_ROOT . '/' . $c['music_path'])): ?>
<button type="button" class="music-toggle" id="music-toggle" aria-pressed="false" aria-label="Putar musik" hidden>
  <svg class="icon icon-play"><use href="#i-music"/></svg><svg class="icon icon-pause"><use href="#i-pause"/></svg>
</button>
<audio id="music" src="<?= e($c['music_path']) ?>" preload="metadata" loop<?= !empty($c['music_on_open']) ? ' data-play-on-open' : '' ?>></audio>
<?php endif; ?>

<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
</body>
</html>
