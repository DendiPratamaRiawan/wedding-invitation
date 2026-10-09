<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';

$admin = require_admin_page();
admin_header('Daftar Tamu', 'guests', $admin);
?>
<section class="panel">
  <div class="toolbar">
    <input type="search" id="g-search" placeholder="Cari nama, kategori, atau kode…" aria-label="Cari tamu">
    <select id="g-category" aria-label="Filter kategori"><option value="">Semua kategori</option></select>
    <select id="g-rsvp" aria-label="Filter RSVP">
      <option value="">Semua RSVP</option>
      <option value="hadir">Hadir</option>
      <option value="tidak_hadir">Tidak hadir</option>
      <option value="ragu">Belum pasti</option>
      <option value="none">Belum menjawab</option>
    </select>
    <select id="g-active" aria-label="Filter status tautan">
      <option value="">Semua tautan</option>
      <option value="1">Aktif</option>
      <option value="0">Nonaktif</option>
    </select>
  </div>
  <div class="toolbar toolbar--actions">
    <button type="button" class="btn btn-primary" id="btn-add">+ Tambah tamu</button>
    <button type="button" class="btn btn-ghost" id="btn-import">Impor Excel / CSV / Massal</button>
    <a class="btn btn-ghost" href="../api/guests.php?action=export" id="btn-export">Ekspor CSV</a>
    <span class="spacer"></span>
    <span class="muted" id="g-count"></span>
  </div>
  <div class="bulkbar" id="bulkbar" hidden>
    <span id="bulk-count"></span>
    <button type="button" class="btn btn-sm btn-ghost" data-bulk="deactivate">Nonaktifkan</button>
    <button type="button" class="btn btn-sm btn-ghost" data-bulk="activate">Aktifkan</button>
    <button type="button" class="btn btn-sm btn-danger" data-bulk="delete">Hapus</button>
  </div>
  <div class="table-wrap">
    <table class="table guests-table">
      <thead>
        <tr>
          <th class="col-check"><input type="checkbox" id="check-all" aria-label="Pilih semua"></th>
          <th>Nama tamu</th>
          <th>Kode &amp; tautan</th>
          <th>RSVP</th>
          <th>Dibuka</th>
          <th>Dibuat</th>
          <th class="col-actions">Aksi</th>
        </tr>
      </thead>
      <tbody id="g-body"><tr><td colspan="7" class="muted">Memuat…</td></tr></tbody>
    </table>
  </div>
  <div class="pager" id="pager"></div>
</section>

<!-- Dialog: tambah / edit tamu -->
<dialog class="modal" id="guest-dialog">
  <form id="guest-form" class="form-stack" method="dialog" novalidate>
    <h2 id="guest-dialog-title">Tambah tamu</h2>
    <input type="hidden" name="id">
    <label>Nama tamu * <input name="guest_name" required maxlength="160" autocomplete="off"></label>
    <div class="row-2">
      <label>Kategori / kelompok <input name="category" maxlength="80" list="category-list" autocomplete="off" placeholder="mis. Keluarga, Kantor"></label>
      <label>No. WhatsApp <input name="phone" inputmode="tel" maxlength="20" placeholder="08xxxxxxxxxx"></label>
    </div>
    <div class="row-2">
      <label>Maks. pendamping <input name="max_companions" type="number" min="0" max="10" placeholder="Bawaan"></label>
      <label class="check check--inline"><input type="checkbox" name="is_active" checked> Tautan aktif</label>
    </div>
    <label>Catatan internal (tidak tampil ke tamu) <input name="notes" maxlength="500"></label>
    <p class="hint" id="guest-code-hint" hidden></p>
    <p class="notice error" id="guest-error" hidden></p>
    <div class="modal__actions">
      <button type="button" class="btn btn-ghost" data-close>Batal</button>
      <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
  </form>
</dialog>
<datalist id="category-list"></datalist>

<!-- Dialog: impor -->
<dialog class="modal modal--lg" id="import-dialog">
  <div class="import">
    <div class="modal__head">
      <h2>Impor tamu</h2>
      <button type="button" class="btn btn-ghost btn-sm" data-close aria-label="Tutup">Tutup</button>
    </div>
    <ol class="steps" id="import-steps">
      <li class="active">1. Sumber data</li><li>2. Pemetaan kolom</li><li>3. Pratinjau &amp; validasi</li><li>4. Hasil</li>
    </ol>

    <!-- Langkah 1 -->
    <div class="step" data-step="1">
      <div class="tabs" role="tablist">
        <button type="button" role="tab" class="tab active" data-tab="file" aria-selected="true">Berkas Excel / CSV</button>
        <button type="button" role="tab" class="tab" data-tab="paste" aria-selected="false">Tempel daftar nama</button>
      </div>
      <div class="tab-panel" data-panel="file">
        <label class="dropzone" id="dropzone">
          <input type="file" id="import-file" accept=".xlsx,.xls,.csv,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
          <strong>Pilih berkas .xlsx, .xls, atau .csv</strong>
          <span class="muted">atau seret berkas ke sini</span>
        </label>
        <p class="hint">Unduh template:
          <a href="../assets/templates/template-tamu.csv" download>template-tamu.csv</a> ·
          <a href="#" id="template-xlsx">template-tamu.xlsx</a>.
          Kolom <strong>Nama Tamu</strong> wajib; Kategori dan No. WhatsApp opsional.</p>
        <label class="field-inline" id="sheet-picker" hidden>Lembar kerja: <select id="sheet-select"></select></label>
      </div>
      <div class="tab-panel" data-panel="paste" hidden>
        <label>Satu nama per baris
          <textarea id="paste-names" rows="10" placeholder="Bapak Ahmad Fauzi&#10;Ibu Siti Rahma&#10;Keluarga Bapak Hendra"></textarea></label>
        <label>Kategori untuk semua nama (opsional) <input id="paste-category" maxlength="80" list="category-list"></label>
        <button type="button" class="btn btn-primary" id="paste-next">Lanjut ke pratinjau</button>
      </div>
      <p class="notice error" id="import-error" hidden></p>
    </div>

    <!-- Langkah 2 -->
    <div class="step" data-step="2" hidden>
      <p class="muted" id="map-info"></p>
      <label class="check"><input type="checkbox" id="has-header" checked> Baris pertama adalah judul kolom</label>
      <div class="row-3">
        <label>Kolom nama tamu * <select id="map-name"></select></label>
        <label>Kolom kategori <select id="map-category"></select></label>
        <label>Kolom No. WhatsApp <select id="map-phone"></select></label>
      </div>
      <div class="table-wrap table-wrap--short"><table class="table table--compact" id="raw-preview"></table></div>
      <div class="modal__actions">
        <button type="button" class="btn btn-ghost" data-goto="1">Kembali</button>
        <button type="button" class="btn btn-primary" id="map-next">Validasi data</button>
      </div>
    </div>

    <!-- Langkah 3 -->
    <div class="step" data-step="3" hidden>
      <div class="summary" id="dry-summary"></div>
      <fieldset class="dup-mode" id="dup-mode">
        <legend>Jika nama sudah ada / duplikat:</legend>
        <label><input type="radio" name="dup" value="skip" checked> Lewati</label>
        <label><input type="radio" name="dup" value="update"> Perbarui kategori/nomor data lama</label>
        <label><input type="radio" name="dup" value="import"> Tetap impor sebagai tamu baru</label>
      </fieldset>
      <div class="table-wrap table-wrap--short"><table class="table table--compact" id="dry-table"></table></div>
      <div class="modal__actions">
        <button type="button" class="btn btn-ghost" id="dry-back">Kembali</button>
        <button type="button" class="btn btn-primary" id="import-commit">Konfirmasi &amp; simpan</button>
      </div>
    </div>

    <!-- Langkah 4 -->
    <div class="step" data-step="4" hidden>
      <div class="summary" id="final-summary"></div>
      <div id="final-failed"></div>
      <div class="modal__actions">
        <button type="button" class="btn btn-ghost" id="import-again">Impor lagi</button>
        <button type="button" class="btn btn-primary" data-close>Selesai</button>
      </div>
    </div>
  </div>
</dialog>
<?php admin_footer(['../js/admin-guests.js']); ?>
