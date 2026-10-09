<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';

$admin = require_admin_page();
admin_header('Pengaturan Undangan', 'settings', $admin);
?>
<section class="panel publish-panel" id="publikasi">
  <div class="panel__head">
    <h2>Undangan</h2>
    <div class="actions">
      <a class="btn btn-primary" href="../" target="_blank" rel="noopener" id="btn-preview">Lihat undangan ↗</a>
    </div>
  </div>
  <p class="muted">Setiap perubahan langsung tampil di undangan setelah klik <strong>Simpan</strong>.</p>
  <div id="problems-box" hidden>
    <p class="hint">Data yang masih kosong (opsional, sebaiknya dilengkapi):</p>
    <ul class="problems" id="problems"></ul>
  </div>
</section>

<form id="settings-form" novalidate>
  <nav class="subnav" aria-label="Bagian pengaturan">
    <a href="#mempelai">Mempelai</a><a href="#acara">Acara</a><a href="#teks">Teks</a><a href="#bagian">Bagian</a>
    <a href="#media">Foto &amp; galeri</a><a href="#kisah">Kisah</a><a href="#amplop">Amplop</a><a href="#tema">Tema</a>
    <a href="#whatsapp">WhatsApp</a><a href="#akun">Akun</a>
  </nav>

  <!-- Mempelai -->
  <section class="panel" id="mempelai">
    <h2>Mempelai &amp; keluarga</h2>
    <div class="grid-2">
      <fieldset class="group">
        <legend>Mempelai pria</legend>
        <label>Nama tampilan * <input data-field="groom.name" maxlength="80" required></label>
        <label>Nama lengkap <input data-field="groom.full_name" maxlength="160"></label>
        <label>Keterangan <input data-field="content.groom_relation" maxlength="40" placeholder="Putra dari"></label>
        <label>Nama keluarga <input data-field="groom.family" maxlength="200"></label>
        <label>Deskripsi singkat (opsional) <textarea data-field="content.groom_bio" rows="2" maxlength="600"></textarea></label>
        <div class="upload" data-upload="groom" data-target="content.groom_photo"></div>
      </fieldset>
      <fieldset class="group">
        <legend>Mempelai wanita</legend>
        <label>Nama tampilan * <input data-field="bride.name" maxlength="80" required></label>
        <label>Nama lengkap <input data-field="bride.full_name" maxlength="160"></label>
        <label>Keterangan <input data-field="content.bride_relation" maxlength="40" placeholder="Putri dari"></label>
        <label>Nama keluarga <input data-field="bride.family" maxlength="200"></label>
        <label>Deskripsi singkat (opsional) <textarea data-field="content.bride_bio" rows="2" maxlength="600"></textarea></label>
        <div class="upload" data-upload="bride" data-target="content.bride_photo"></div>
      </fieldset>
    </div>
  </section>

  <!-- Acara -->
  <section class="panel" id="acara">
    <div class="panel__head">
      <h2>Acara</h2>
      <button type="button" class="btn btn-ghost btn-sm" id="add-event">+ Tambah acara</button>
    </div>
    <p class="hint">Tanggal &amp; jam dipakai untuk tampilan, hitung mundur, dan tombol "Simpan ke Kalender".</p>
    <div id="events"></div>
  </section>

  <!-- Teks -->
  <section class="panel" id="teks">
    <h2>Teks undangan</h2>
    <p class="notice info">Teks keagamaan (basmalah, ayat, doa) wajib diperiksa ulang ejaan &amp; harakatnya sebelum publikasi.</p>
    <div class="grid-2">
      <label>Basmalah (sampul) <input data-field="content.basmalah" maxlength="200" dir="rtl" class="arabic-input"></label>
      <label>Judul sampul <input data-field="content.cover_title" maxlength="80"></label>
      <label>Sapaan tamu sebelum nama <input data-field="content.guest_prefix" maxlength="40" placeholder="Bapak/Ibu"></label>
      <label>Salam pembuka <input data-field="content.salam_open" maxlength="120"></label>
    </div>
    <label>Kalimat pengantar <textarea data-field="opening_text" rows="3" maxlength="1500"></textarea></label>
    <label>Ayat / kutipan (Arab) <textarea data-field="content.quote_arabic" rows="2" maxlength="1000" dir="rtl" class="arabic-input"></textarea></label>
    <label>Terjemahan kutipan <textarea data-field="content.quote_translation" rows="3" maxlength="1500"></textarea></label>
    <label>Sumber kutipan <input data-field="content.quote_source" maxlength="80"></label>
    <hr>
    <label>Teks penutup <textarea data-field="closing_text" rows="3" maxlength="1500"></textarea></label>
    <label>Doa penutup (Arab) <textarea data-field="content.closing_prayer_arabic" rows="2" maxlength="1000" dir="rtl" class="arabic-input"></textarea></label>
    <label>Terjemahan doa <textarea data-field="content.closing_prayer_translation" rows="2" maxlength="1500"></textarea></label>
    <div class="grid-2">
      <label>Salam penutup <input data-field="content.salam_close" maxlength="120"></label>
      <label>Tanda tangan penutup <input data-field="content.closing_signature" maxlength="120"></label>
    </div>
  </section>

  <!-- Bagian -->
  <section class="panel" id="bagian">
    <h2>Bagian yang ditampilkan</h2>
    <div class="toggles">
      <label class="switch"><input type="checkbox" data-field="content.sections.opening"><span>Pembuka Islami</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.couple"><span>Profil mempelai</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.events"><span>Detail acara</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.countdown"><span>Hitung mundur</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.gallery"><span>Galeri</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.story"><span>Kisah pasangan</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.rsvp"><span>RSVP</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.wishes"><span>Ucapan &amp; doa</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.gift"><span>Amplop digital</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.closing"><span>Penutup</span></label>
      <label class="switch"><input type="checkbox" data-field="content.sections.music" id="music-section"><span>Musik latar</span></label>
    </div>
    <div class="grid-2">
      <label>Batas pendamping per tamu (bawaan) <input type="number" min="0" max="10" data-field="content.rsvp_max_companions"></label>
      <label class="check check--inline"><input type="checkbox" data-field="content.wishes_auto_approve"> Tampilkan ucapan tanpa moderasi</label>
    </div>
  </section>

  <!-- Media -->
  <section class="panel" id="media">
    <h2>Foto &amp; galeri</h2>
    <div class="grid-2">
      <div>
        <h3>Foto pasangan (di dalam bulan pada halaman utama)</h3>
        <div class="upload" data-upload="couple" data-target="content.couple_photo"></div>
      </div>
      <div>
        <h3>Musik latar</h3>
        <p class="hint">Format MP3, M4A, atau OGG, maks. 15 MB. Agar cepat dimuat di ponsel, gunakan MP3 128 kbps (±3–5 MB).</p>
        <div class="upload upload--audio" data-upload="music" data-target="content.music_path"></div>
        <label class="check"><input type="checkbox" data-field="content.music_on_open">
          Putar otomatis saat tamu menekan "Buka Undangan"</label>
        <p class="hint">Tamu selalu dapat menjeda/memutar lewat tombol musik di pojok kanan bawah. Browser tidak mengizinkan musik berbunyi sebelum tamu berinteraksi, jadi musik dimulai tepat saat sampul dibuka.</p>
      </div>
    </div>
    <h3>Galeri</h3>
    <p class="hint">Foto otomatis dikompresi ke WebP. Perubahan galeri langsung berlaku (tidak menunggu publikasi).</p>
    <label class="btn btn-ghost file-btn">+ Unggah foto galeri <input type="file" id="gallery-input" accept="image/jpeg,image/png,image/webp" multiple hidden></label>
    <div class="gallery-admin" id="gallery"></div>
  </section>

  <!-- Kisah -->
  <section class="panel" id="kisah">
    <div class="panel__head">
      <h2>Kisah pasangan</h2>
      <button type="button" class="btn btn-ghost btn-sm" id="add-story">+ Tambah cerita</button>
    </div>
    <div id="story"></div>
  </section>

  <!-- Amplop -->
  <section class="panel" id="amplop">
    <div class="panel__head">
      <h2>Amplop digital</h2>
      <button type="button" class="btn btn-ghost btn-sm" id="add-account">+ Tambah rekening</button>
    </div>
    <p class="hint">Hanya tampil bila bagian "Amplop digital" diaktifkan. Periksa nomor rekening dengan teliti.</p>
    <label>Kalimat pengantar <textarea data-field="content.gift.intro" rows="2" maxlength="600"></textarea></label>
    <div id="accounts"></div>
  </section>

  <!-- Tema -->
  <section class="panel" id="tema">
    <h2>Tema warna</h2>
    <div class="presets" id="presets"></div>
  </section>

  <!-- WhatsApp -->
  <section class="panel" id="whatsapp">
    <h2>Template pesan WhatsApp</h2>
    <p class="hint">Gunakan <code>[Nama Tamu]</code>, <code>[Tautan Undangan]</code>, dan <code>[Nama Mempelai]</code> — akan diisi otomatis untuk setiap tamu. Teks di antara <code>*bintang*</code> tampil tebal di WhatsApp.</p>
    <div class="grid-2">
      <label>Template <textarea data-field="content.wa_template" rows="14" maxlength="2000" id="wa-template"></textarea></label>
      <div>
        <span class="label">Contoh hasil</span>
        <div class="wa-preview" id="wa-preview"></div>
      </div>
    </div>
  </section>

  <div class="savebar" id="savebar">
    <span id="save-state" class="muted">Semua perubahan tersimpan</span>
    <button type="submit" class="btn btn-primary" id="btn-save">Simpan</button>
  </div>
</form>

<section class="panel" id="akun">
  <h2>Akun admin</h2>
  <form id="password-form" class="form-stack narrow-form" novalidate>
    <label>Password saat ini <input type="password" name="current" autocomplete="current-password" required></label>
    <label>Password baru (min. 10 karakter) <input type="password" name="new" autocomplete="new-password" minlength="10" required></label>
    <button type="submit" class="btn btn-ghost">Ganti password</button>
  </form>
</section>

<?php admin_footer(['../js/admin-settings.js']); ?>
