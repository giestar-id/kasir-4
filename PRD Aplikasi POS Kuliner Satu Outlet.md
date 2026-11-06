# PRD: Aplikasi POS Kuliner Satu Outlet

**Stack:** Laravel, Livewire, MySQL, Tailwind, Alpine.js (hanya jika diperlukan) **Bahasa antarmuka:** Indonesia | **Mata uang:** Rupiah (IDR) | **Zona waktu:** Asia/Jakarta

---

## 1. Ringkasan

Aplikasi POS berbasis web untuk satu outlet kuliner. Fungsi utama: mencatat penjualan (offline, GoFood, ShopeeFood), mengelola menu, menghitung HPP dari resep, memantau stok bahan baku, dan mengotomatiskan pencatatan pesanan online dari screenshot dengan **AI hybrid**: API AI sebagai jalur utama, mesin lokal Laravel sebagai cadangan yang tetap akurat.

### Tujuan

1. Kasir menyelesaikan transaksi offline dalam kurang dari 15 detik.
2. Pesanan online dari screenshot tercatat dengan input manual minimal, tanpa duplikat.
3. Stok dan HPP selalu konsisten dengan transaksi.
4. Pemilik bisa melihat laporan penjualan dan estimasi laba kapan saja.
5. Aplikasi tetap berfungsi penuh walaupun API AI mati, kuota habis, atau key tidak diisi.

### Di luar cakupan (versi 1)

Multi-outlet, mode offline penuh tanpa internet, integrasi API resmi GoFood/ShopeeFood, cetak struk thermal (disiapkan tampilan struk saja), pembayaran digital terintegrasi, program loyalitas, aplikasi native.

---

## 2. Instruksi Kerja untuk AI Pengembang (WAJIB DIBACA)

1. **Baca direktori desain lebih dulu:** `[ISI_PATH_DIREKTORI_DESAIN]`. Semua warna, tipografi, spasi, radius, bayangan, ikon, dan pola tata letak mengikuti isi direktori itu. Jika PRD ini bertentangan dengan desain, **desain yang menang** untuk urusan visual, PRD yang menang untuk urusan perilaku dan data.
2. **Tidak ada dummy data.** Dilarang membuat seeder berisi menu, bahan, resep, harga, atau transaksi contoh. Seeder hanya boleh membuat: akun pemilik awal, pengaturan default, dan kategori kosong. Semua layar harus punya **empty state** yang membimbing pengguna mengisi data pertama.
3. **Kerjakan per fase** (bagian 14). Setiap fase harus lulus tes sebelum lanjut.
4. **Jangan menebak kebutuhan.** Jika ada hal yang tidak tercakup, catat di `docs/PERTANYAAN.md` dan pilih opsi paling sederhana dan aman.
5. **Uang selalu bilangan bulat rupiah** (BIGINT). Jumlah bahan memakai DECIMAL(14,3). Dilarang memakai float untuk uang atau stok.
6. Semua teks antarmuka berbahasa Indonesia. Kode, nama tabel, dan nama kelas berbahasa Inggris.
7. Setiap fitur disertai tes otomatis (Pest/PHPUnit). Fitur dianggap selesai hanya jika kriteria penerimaan di bagian 5 lolos.
8. Verifikasi versi paket terbaru yang stabil saat instalasi. Jangan mengunci versi lama tanpa alasan.

---

## 3. Teknologi dan Arsitektur

| Lapisan | Pilihan | Catatan |
| --- | --- | --- |
| Framework | Laravel (stabil terbaru), PHP 8.3+ | Struktur standar, service class untuk logika bisnis |
| UI reaktif | Livewire (stabil terbaru); Alpine.js hanya untuk interaksi kecil sisi klien yang tidak praktis lewat Livewire (dropdown, modal, pintasan keyboard) | Tanpa React/Next.js |
| Database | MySQL 8, InnoDB, utf8mb4 | Indeks wajib, lihat bagian 7 |
| CSS | Tailwind CSS | Token desain via CSS variables |
| Komponen UI | Komponen Blade b**uatan sendiri (Blade component + Tailwind)** | Tanpa pustaka komponen pihak ketiga. Setiap komponen dibuat sebagai Blade component dengan Tailwind, dengan prop variant dan size yang konsisten (Button, Input, Select, Dialog, Sheet, Card, Table, Badge, Tabs, Toast, Skeleton, Command/Combobox, Switch, Alert, DropdownMenu). Gunakan `tailwind-merge` untuk penggabungan class. |
| Antrian | Laravel Queue (driver database) | Untuk proses AI/OCR agar UI tidak macet |
| Penyimpanan file | Disk lokal privat (`storage/app/private`) | Screenshot tidak boleh publik |
| OCR lokal | Tesseract (bahasa `ind` + `eng`) lewat wrapper PHP | Opsi peningkatan: layanan PaddleOCR kecil bila uji akurasi gagal |
| Pengujian | Pest, Livewire testing, Laravel Dusk (alur kritis) |  |

### 3.1 Pengalaman seperti Next.js (tanpa reload halaman penuh)

Aplikasi harus terasa seperti SPA. Aturan wajib:

- Semua tautan navigasi memakai `wire:navigate` (SPA mode). Sidebar dan header berada di layout yang di-`@persist` agar tidak dirender ulang.
- **Setiap kartu/panel adalah komponen Livewire sendiri** (atau island) dengan `wire:key` stabil. Perubahan data hanya me-render ulang kartu yang terdampak, bukan seluruh halaman. Contoh: setelah transaksi disimpan, hanya kartu keranjang, ringkasan penjualan hari ini, dan daftar stok kritis yang diperbarui.
- Komunikasi antar kartu memakai **event Livewire** (`dispatch`), bukan refresh halaman.
- Kartu berat memakai **lazy loading** (`#[Lazy]`) dengan **skeleton** sesuai bentuk kartu, bukan spinner layar penuh.
- Pembaruan berkala memakai `wire:poll.visible` hanya pada kartu yang perlu (status proses AI, stok kritis) dengan interval wajar (5-15 detik) dan berhenti saat tab tidak terlihat.
- Filter, pencarian, dan pagination tabel dilakukan di dalam komponen tabel dengan `wire:model.live.debounce.300ms`, URL ikut diperbarui (`#[Url]`) agar bisa di-bookmark.
- Indikator `wire:loading` lokal pada tombol dan kartu. Tombol disabled saat proses untuk mencegah klik ganda.
- Kriteria uji: pada semua halaman, tab Network tidak boleh menampilkan navigasi dokumen penuh saat berpindah menu atau menyimpan data.

---

## 4. Pengguna dan Hak Akses

Satu outlet, dua peran:

| Peran | Akses |
| --- | --- |
| **Pemilik** | Semua fitur: menu, bahan, resep, HPP, laporan laba, pengaturan, kelola kasir |
| **Kasir** | Kasir/POS, input pesanan online, riwayat transaksi hari berjalan, lihat stok. **Tidak boleh** melihat HPP, laba, resep (harga beli), atau pengaturan |

Pembatasan dijalankan di server (Policy/Gate dan middleware), bukan hanya menyembunyikan tombol. Kolom HPP dan harga beli tidak boleh ikut terkirim ke browser kasir.

---

## 5. Fitur dan Kriteria Penerimaan

### 5.1 Autentikasi

- Login dengan email/username + password. Ada rate limit (maks 5 percobaan per menit per IP+akun) dan pesan error generik.
- Sesi otomatis berakhir setelah tidak aktif (default 8 jam, dapat diatur).
- Logout menghapus sesi. Ada opsi keluar dari semua perangkat.
- **Lolos jika:** akun salah 6 kali berturut-turut diblokir sementara; kasir yang membuka URL halaman pemilik mendapat 403.

### 5.2 Dashboard (Pemilik)

Kartu independen, masing-masing lazy: penjualan hari ini (jumlah transaksi, omzet), pembagian per kanal, estimasi laba kotor (omzet dikurangi HPP), 5 menu terlaris, bahan stok kritis, status AI (terhubung atau tidak), pesanan online menunggu konfirmasi. Filter periode (hari ini, 7 hari, bulan ini, rentang kustom).

### 5.3 Menu dan Kategori

- CRUD kategori (nama, urutan) dan menu (nama, kategori, harga, status aktif, foto opsional, catatan).
- **Satu harga untuk semua kanal.** Tidak ada kolom harga per platform.
- Menu dinonaktifkan (bukan dihapus) jika sudah punya riwayat transaksi. Hapus permanen hanya untuk menu tanpa transaksi.
- Nama menu unik (tidak peka huruf besar/kecil).
- Menu punya **alias** (nama lain, termasuk nama di GoFood/ShopeeFood) yang dipakai pencocokan AI. Alias dikelola di halaman menu.
- Varian/add-on: **versi 1 tidak ada**; setiap varian dibuat sebagai menu terpisah (mis. Ayam Geprek Level 3). Catat di dokumentasi.
- **Lolos jika:** harga diubah, transaksi lama tetap memakai harga lama.

### 5.4 Bahan Baku dan Stok

- CRUD bahan: nama, **satuan dasar** (gram, ml, pcs), satuan beli (kg, liter, dus, dll) beserta faktor konversi ke satuan dasar, stok minimum, status aktif.
- Stok disimpan di satuan dasar. Setiap perubahan stok **wajib** tercatat sebagai baris `stock_movements` (tidak ada perubahan stok tanpa jejak).
- Jenis pergerakan: `purchase` (pembelian), `sale_usage` (pemakaian penjualan), `void_return` (pembatalan), `adjustment` (koreksi/stok opname), `waste` (terbuang).
- Halaman **Pembelian Bahan**: pilih bahan, jumlah di satuan beli, total harga dibayar. Sistem menghitung harga per satuan dasar dan memperbarui **harga rata-rata tertimbang**.
- Halaman **Stok Opname**: input stok fisik, sistem membuat selisih sebagai adjustment dengan alasan wajib.
- Peringatan stok menipis saat stok ≤ minimum. Stok negatif **tidak memblokir penjualan** (dapur nyata sering tidak sinkron), tetapi ditandai merah dan muncul di dashboard.
- **Lolos jika:** saldo stok = jumlah seluruh pergerakan, selalu; tes konsistensi otomatis membuktikannya.

### 5.5 Resep dan HPP

- Setiap menu punya resep: daftar bahan + jumlah per 1 porsi (satuan dasar). Opsional: biaya tambahan per porsi (kemasan, dll) sebagai angka tetap.
- **HPP menu** = jumlah (jumlah bahan x harga rata-rata bahan) + biaya tambahan. Tampilkan juga margin (Rp dan %).
- Menu tanpa resep ditandai **Resep belum lengkap** dan HPP-nya tidak dianggap nol palsu: laporan menampilkan penanda bahwa HPP tidak lengkap.
- Saat transaksi, **HPP dan harga dibekukan (snapshot)** di `sale_items`. Mengubah resep atau harga beli tidak mengubah laporan lama.
- **Lolos jika:** tes memastikan ayam 1 kg dibeli Rp36.000, resep 150 g, menghasilkan HPP bahan Rp5.400.

### 5.6 Kasir / POS Offline

- Layar dua panel: kiri grid menu (kategori sebagai tab, pencarian cepat, fokus otomatis ke kolom cari), kanan keranjang.
- Keranjang: ubah jumlah, hapus item, catatan per item, catatan transaksi. Total dihitung server-side (client hanya menampilkan).
- Metode pembayaran: tunai (input uang diterima, hitung kembalian, tombol nominal cepat), QRIS/transfer (hanya dicatat). Diskon nominal atau persen opsional dengan alasan, hanya Pemilik atau kasir yang diizinkan Pemilik.
- Tombol **Simpan** membuat transaksi, mengurangi stok sesuai resep dalam **satu database transaction**, lalu menampilkan struk (tampilan cetak browser, lebar 58/80 mm via CSS print).
- Nomor transaksi berurutan per hari, mis. `TRX-20261104-0001`, dijamin unik dan tahan balapan.
- Klik ganda tidak boleh membuat dua transaksi (idempotency key per keranjang).
- Pintasan keyboard: `/` cari menu, `Enter` tambah, `F2` bayar, `Esc` tutup dialog.
- **Lolos jika:** dua kasir menyimpan bersamaan, nomor tidak bentrok dan stok tetap benar.

### 5.7 Pesanan Online (Input Manual)

- Form cepat: kanal (GoFood/ShopeeFood), nomor pesanan eksternal (wajib, unik per kanal), waktu pesanan, item (combobox menu + jumlah), total dari platform, catatan.
- Ini juga **jalur cadangan terakhir** bila AI gagal total. Harga item mengikuti harga menu. Selisih antara jumlah harga menu dan total dari platform ditampilkan sebagai **penyesuaian platform** (ongkir, promo, komisi), tidak mengubah harga menu.
- Pengaturan opsional: persen komisi per platform untuk **estimasi pendapatan bersih** (default kosong, diberi label Estimasi).

### 5.8 AI Screenshot Pesanan (Hybrid)

Lihat bagian 6 untuk rancangan teknis. Perilaku dari sisi pengguna:

1. Pengguna mengunggah 1 atau banyak screenshot (seret-lepas, pilih file, atau tempel dengan Ctrl+V). Format JPG/PNG/WEBP, maks 8 MB per file.
2. Tiap file muncul sebagai kartu dengan status: Menunggu, Membaca, Perlu dicek, Siap disimpan, Duplikat, Gagal.
3. Layar **Konfirmasi** menampilkan gambar di sisi kiri dan hasil bacaan di sisi kanan yang bisa diedit: platform, nomor pesanan, waktu, daftar item (dengan status cocok/tidak cocok), jumlah, total.
4. Item tak dikenali ditandai merah, pengguna memilih menu yang benar dari combobox dan bisa mencentang **Simpan sebagai alias** agar pembacaan berikutnya otomatis cocok.
5. Tombol **Simpan pesanan** membuat transaksi, mengurangi stok, dan menandai gambar terpakai.
6. Label sumber ditampilkan: **Dibaca oleh: AI (nama penyedia)** atau **Dibaca oleh: Mesin lokal**.

### 5.9 Pencegahan Transaksi Ganda

Berlapis, semuanya wajib:

1. **Kunci unik database** `(channel, external_order_id)` pada tabel transaksi.
2. **Hash file** (SHA-256 isi gambar) unik pada tabel `screenshot_imports`; unggahan gambar yang sama ditolak dengan pesan jelas dan tautan ke transaksi yang sudah ada.
3. **Kunci cadangan** bila nomor pesanan tidak terbaca: kombinasi `channel + tanggal-waktu (toleransi 2 menit) + total + jumlah item`. Bila cocok, tampilkan peringatan Kemungkinan duplikat dan minta keputusan pengguna (tidak ditolak diam-diam).
4. Penyimpanan memakai database transaction dan menangani error pelanggaran unik dengan pesan ramah.

- **Lolos jika:** unggah gambar sama dua kali, dan unggah dua gambar berbeda untuk pesanan yang sama, keduanya hanya menghasilkan satu transaksi.

### 5.10 Riwayat dan Laporan

- **Riwayat transaksi:** tabel dengan filter (tanggal, kanal, status, pencarian nomor), urutan, pagination server-side, detail transaksi dalam panel samping (sheet).
- **Pembatalan (void):** hanya dengan alasan wajib. Status menjadi `void`, stok dikembalikan lewat `void_return`, transaksi tidak dihapus. Dicatat di audit log.
- **Laporan** (Pemilik): penjualan per hari/minggu/bulan, per kanal, per kategori, per menu, estimasi laba kotor, pemakaian bahan, nilai stok. Grafik sederhana dan ekspor CSV.
- Transaksi void tidak dihitung di omzet tetapi tetap bisa dilihat.
- **Lolos jika:** total laporan = jumlah transaksi non-void pada periode, dibuktikan tes.

### 5.11 Pengaturan

Halaman dengan tab (hanya Pemilik):

**a. Profil Toko**

- Ubah **nama toko** (dipakai di header, struk, judul halaman). Opsional: alamat, nomor telepon, catatan kaki struk.

**b. Keamanan Akun**

- Ubah **password**: wajib password lama, password baru (min 10 karakter), konfirmasi. Ada indikator kekuatan. Setelah berhasil, sesi lain dikeluarkan.
- Kelola akun kasir (buat, nonaktifkan, reset password).

**c. Integrasi AI**

- Pilih **penyedia** dari daftar: Anthropic (Claude), Google (Gemini), OpenAI, OpenRouter, dan Endpoint kompatibel OpenAI (kustom: base URL + key). Arsitektur harus memudahkan penambahan penyedia.
- Isi **API key** (kolom password, tombol tampilkan hanya untuk key yang baru diketik) dan **model**. Daftar model diambil dari endpoint daftar model penyedia bila tersedia; jika tidak, input manual. **Jangan hardcode nama model** karena cepat usang; sediakan nilai awal yang bisa diubah.
- Tombol **Tes Koneksi**: mengirim permintaan ringan dan menampilkan status:
  - Belum diatur (abu-abu)
  - Terhubung (hijau) + waktu pengecekan terakhir + nama model
  - Gagal (merah) + alasan ramah (key salah, kuota habis, model tidak ada, timeout, jaringan)
- Tombol **Tes Baca Gambar**: unggah satu screenshot contoh untuk melihat hasil ekstraksi tanpa menyimpan transaksi.
- Opsi: aktifkan/nonaktifkan AI (jika nonaktif, langsung mesin lokal), batas anggaran bulanan (hitungan perkiraan dari jumlah panggilan), ambang keyakinan, jumlah hari penyimpanan screenshot (default 30), simpan otomatis (default **mati**).
- Key tersimpan **terenkripsi** dan tampil hanya dalam bentuk tersamar (`sk-••••••a1b2`). Key tidak pernah dikirim kembali ke browser, tidak masuk log, tidak jadi public property Livewire.
- Menyimpan key kosong = menghapus key. Ada tombol Hapus key dengan konfirmasi.

**d. Cadangan Data** (tombol ekspor database dan status backup terakhir)

---

## 6. Rancangan AI Hybrid

### 6.1 Alur

```
Unggah gambar -> validasi file + hash -> antrian
  -> [Jalur A] Penyedia API aktif (jika ada key & lolos syarat)
        sukses + lolos validasi + yakin  -> hasil AI
        gagal / tidak yakin              -> [Jalur B]
  -> [Jalur B] Mesin lokal (OCR + parser per platform)
  -> Pencocokan menu -> Validasi -> Skor keyakinan
  -> Layar konfirmasi (wajib) -> Simpan
```

### 6.2 Kapan beralih ke mesin lokal

Otomatis jika salah satu terjadi: AI nonaktif atau key kosong; status koneksi gagal; timeout (default 25 detik); HTTP 429/5xx setelah 2 kali coba ulang dengan jeda bertahap; respons bukan JSON valid atau tidak sesuai skema; batas anggaran bulanan tercapai; **hasil AI gagal validasi** (bagian 6.5). Pada semua kasus, pengguna melihat label sumber dan alasan beralih (mis. Kuota API habis, dibaca mesin lokal).

### 6.3 Antarmuka kode

- Interface `OrderExtractor` dengan metode `extract(ImageFile): ExtractionResult`.
- Implementasi: `AiApiExtractor` (memakai driver per penyedia: `AnthropicDriver`, `GeminiDriver`, `OpenAiDriver`, `OpenRouterDriver`, `CustomOpenAiDriver`) dan `LocalOcrExtractor`.
- `ExtractionPipeline` mengatur urutan, percobaan ulang, dan catatan alasan fallback.
- `ExtractionResult` berisi: platform, nomor pesanan, waktu pesanan, daftar item (nama mentah, jumlah, harga bila terlihat), total, keyakinan per field, sumber, peringatan. Nama pelanggan dan alamat **tidak diekstrak dan tidak disimpan**; prompt harus melarangnya.
- Prompt AI disimpan sebagai berkas yang mudah diperbarui, minta keluaran JSON ketat sesuai skema, dan diuji.

### 6.4 Mesin lokal (harus akurat tanpa AI)

Akurasi diraih dengan beberapa lapis, bukan satu OCR saja:

1. **Pra-pemrosesan gambar:** ubah ke grayscale, perbesar 2x bila lebar < 1200 px, tingkatkan kontras, pangkas bagian status bar/navigasi, penajaman ringan (Imagick/Intervention).
2. **Deteksi platform** dari kata kunci dan warna dominan; bila ragu, minta pengguna memilih.
3. **Dua kali OCR** dengan mode segmentasi berbeda (mis. blok dan baris) lalu digabung; angka rupiah divalidasi dengan regex ketat (`Rp` dan pemisah titik ribuan) dan koreksi karakter ambigu (O/0, l/1, S/5) hanya pada konteks numerik.
4. **Parser per platform** berbasis jangkar teks (mis. kata sebelum nomor pesanan, kata Total) dengan definisi template di berkas konfigurasi bertingkat versi, sehingga bisa diperbarui tanpa mengubah kode inti.
5. **Pencocokan terbatas pada daftar menu milik pengguna** (bukan teks bebas): normalisasi (huruf kecil, buang tanda baca dan kata umum), cocokkan ke nama menu + alias dengan kemiripan karakter (Levenshtein/trigram). Skor ≥ 0,90 dianggap cocok, 0,70-0,89 saran (perlu klik), < 0,70 tidak cocok.
6. **Belajar dari koreksi:** setiap koreksi yang disimpan sebagai alias membuat pencocokan berikutnya lebih akurat.
7. **Mesin lokal tidak pernah menyimpan otomatis.** Konfirmasi manusia selalu wajib.

### 6.5 Validasi silang (berlaku untuk AI dan lokal)

- Nomor pesanan sesuai pola platform.
- Jumlah per item bilangan bulat positif dan wajar (batas peringatan, mis. > 50).
- Jumlah (harga menu x qty) dibandingkan dengan total platform: selisih dijelaskan sebagai penyesuaian; jika selisih tidak masuk akal (mis. > 60% dari total), beri peringatan dan turunkan keyakinan.
- Waktu tidak boleh di masa depan.
- Jika hasil AI dan lokal **sama-sama dijalankan** (mode verifikasi opsional di Pengaturan) dan berbeda pada field penting, tandai Perlu dicek.

### 6.6 Simpan otomatis (opsional, default mati)

Hanya untuk hasil jalur API, dengan syarat sekaligus: semua item cocok persis, validasi lolos, bukan duplikat, keyakinan ≥ ambang. Hasil mesin lokal tidak pernah dieligibel.

### 6.7 Target akurasi dan cara uji

- Siapkan **dataset uji 30 screenshot asli** milik pemilik (campuran GoFood/ShopeeFood, 1-6 item, berbagai kondisi), beserta kunci jawaban manual di `tests/Fixtures/orders`.
- Target per field setelah dicocokkan ke menu: **Jalur API ≥ 95%, mesin lokal ≥ 85%** untuk nomor pesanan, total, dan seluruh item+jumlah per pesanan. Jika mesin lokal < 85%, naikkan ke PaddleOCR sebagai layanan terpisah sebelum fase dinyatakan selesai.
- Tes regresi otomatis menjalankan dataset ini dan melaporkan akurasi per platform. Berkas screenshot uji tidak dimasukkan ke repositori publik.

---

## 7. Model Data dan Indeks

Semua tabel memakai InnoDB, `created_at/updated_at`, kunci asing dengan aturan yang jelas. Uang BIGINT (rupiah), kuantitas DECIMAL(14,3).

| Tabel | Kolom utama | Indeks / batasan |
| --- | --- | --- |
| `users` | name, email, password, role, is\_active, last\_login\_at | UNIQUE(email) |
| `settings` | key, value (terenkripsi bila sensitif) | UNIQUE(key) |
| `categories` | name, sort\_order | UNIQUE(name) |
| `menus` | category\_id, name, price, is\_active, image\_path, note | UNIQUE(name), INDEX(category\_id, is\_active) |
| `menu_aliases` | menu\_id, alias, normalized\_alias | UNIQUE(normalized\_alias), INDEX(menu\_id) |
| `ingredients` | name, base\_unit, purchase\_unit, unit\_factor, avg\_cost\_per\_base, current\_stock, min\_stock, is\_active | UNIQUE(name), INDEX(is\_active, current\_stock) |
| `recipes` | menu\_id, ingredient\_id, qty\_per\_portion | UNIQUE(menu\_id, ingredient\_id) |
| `menu_extra_costs` | menu\_id, label, amount | INDEX(menu\_id) |
| `purchases` | ingredient\_id, qty\_purchase\_unit, qty\_base, total\_cost, purchased\_at, note | INDEX(ingredient\_id, purchased\_at) |
| `stock_movements` | ingredient\_id, type, qty\_change, unit\_cost, ref\_type, ref\_id, reason, user\_id | INDEX(ingredient\_id, created\_at), INDEX(ref\_type, ref\_id) |
| `sales` | number, channel, external\_order\_id, status, subtotal, discount, platform\_adjustment, total, payment\_method, paid\_amount, ordered\_at, source (manual/ai\_api/ai\_local), idempotency\_key, voided\_at, void\_reason, user\_id | UNIQUE(number), **UNIQUE(channel, external\_order\_id)**, UNIQUE(idempotency\_key), INDEX(ordered\_at), INDEX(channel, ordered\_at), INDEX(status, ordered\_at) |
| `sale_items` | sale\_id, menu\_id, menu\_name\_snapshot, qty, unit\_price, unit\_hpp, note | INDEX(sale\_id), INDEX(menu\_id) |
| `screenshot_imports` | file\_path, file\_hash, status, source, platform, raw\_result (JSON), confidence, fallback\_reason, sale\_id, uploaded\_by | **UNIQUE(file\_hash)**, INDEX(status, created\_at) |
| `ai_call_logs` | provider, model, status, latency\_ms, error\_code, est\_cost | INDEX(created\_at) (tanpa isi gambar/key) |
| `audit_logs` | user\_id, action, subject\_type, subject\_id, meta (JSON), ip | INDEX(subject\_type, subject\_id), INDEX(created\_at) |

Catatan: `external_order_id` boleh NULL untuk transaksi offline (MySQL mengizinkan banyak NULL pada indeks unik). Seluruh perubahan stok hanya lewat `StockService` dengan `lockForUpdate()` di dalam transaction.

---

## 8. Desain dan Pengalaman Pengguna

- **Sumber kebenaran visual:** direktori desain pada bagian 2. Terjemahkan token desain menjadi CSS variables (`--background`, `--foreground`, `--primary`, `--muted`, `--border`, `--ring`, `--radius`, dst.) dan pakai di seluruh komponen. Jangan menaruh warna hardcode di Blade.
- **Responsif:** perangkat target PC kasir (utama), tablet, dan HP. Sidebar berubah jadi menu geser di layar kecil. Layar kasir di tablet memakai dua panel, di HP memakai keranjang sebagai bottom sheet. Area sentuh minimal 44 px.
- **Kemudahan:** satu tujuan per layar, label jelas bahasa sehari-hari, nilai default yang masuk akal, pencarian cepat di semua daftar, konfirmasi dialog hanya untuk tindakan berisiko (void, hapus, hapus key).
- **Umpan balik:** toast untuk sukses/gagal, validasi inline di bawah kolom, skeleton saat memuat, empty state dengan tombol aksi, pesan error manusiawi (tanpa jargon teknis).
- **Format:** Rupiah `Rp12.500`, tanggal `04 Nov 2026, 14:30`, angka rata kanan di tabel.
- **Aksesibilitas:** kontras WCAG AA, fokus terlihat, navigasi keyboard penuh, `aria-label` pada tombol ikon, dialog menjebak fokus, tidak mengandalkan warna saja untuk status (tambahkan ikon/teks).
- **Estetika:** minimalis, rapi, banyak ruang kosong, hierarki tipografi jelas, animasi halus dan singkat (≤ 200 ms) serta menghormati `prefers-reduced-motion`. Mode gelap hanya bila ada di desain.
- **Peta halaman:** Dashboard, Kasir, Pesanan Online (Unggah Screenshot, Antrian Konfirmasi, Input Manual), Riwayat Transaksi, Menu, Kategori, Bahan Baku, Pembelian, Stok Opname, Laporan, Pengaturan.

---

## 9. Keamanan

1. Password di-hash (bcrypt/argon2), minimal 10 karakter, tidak boleh sama dengan password lama.
2. Seluruh akses lewat HTTPS, cookie `Secure`, `HttpOnly`, `SameSite=Lax`, regenerasi sesi saat login.
3. Proteksi CSRF bawaan Laravel/Livewire. Header keamanan: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy.
4. Otorisasi server-side di setiap aksi Livewire (Policy). Properti Livewire sensitif dikunci (`#[Locked]`) agar ID tidak bisa diubah dari klien.
5. Mass assignment dibatasi (`$fillable`). Semua query lewat Eloquent/parameter binding (anti SQL injection). Output Blade di-escape (anti XSS).
6. **API key:** terenkripsi (`encrypted` cast, `APP_KEY` aman), tidak pernah di frontend/log/respons, pemanggilan AI hanya dari backend, ada batas anggaran dan rate limit internal.
7. **Unggahan:** validasi MIME dan ekstensi nyata, batas ukuran, nama file diganti acak, disimpan di disk privat, dihapus otomatis setelah masa simpan, tidak dieksekusi. Strip metadata EXIF.
8. **Privasi pelanggan:** nama dan alamat pelanggan tidak diekstrak maupun disimpan. Beri catatan di Pengaturan bahwa gambar dikirim ke penyedia AI yang dipilih.
9. Rate limit pada login, unggah, dan tes koneksi.
10. Audit log untuk: login, perubahan harga, void, penyesuaian stok, perubahan pengaturan AI/password.
11. `.env` tidak masuk repositori, `APP_DEBUG=false` di produksi, dependensi diperiksa (`composer audit`).
12. Tes keamanan otomatis: akses lintas peran, IDOR pada detail transaksi, unggah file berbahaya, brute force login.

---

## 10. Performa

- Semua kolom filter/urut/join memiliki indeks (bagian 7). Verifikasi dengan `EXPLAIN` pada kueri laporan.
- Hindari N+1 (eager loading, `Model::preventLazyLoading()` di non-produksi).
- Laporan memakai agregasi SQL, bukan loop PHP. Pagination server-side di semua tabel.
- Proses AI/OCR berjalan di antrian, UI mendapat status lewat poll ringan.
- Aset dibangun dengan Vite, cache config/route/view di produksi.
- Target: respons halaman biasa < 300 ms, simpan transaksi < 500 ms (di luar AI), kueri laporan 1 bulan < 1 detik.

---

## 11. Cadangan dan Ketahanan Data

- Backup database otomatis harian (mysqldump atau paket backup Laravel), simpan minimal 14 hari, salinan di lokasi terpisah dari server aplikasi.
- Semua operasi multi-tabel dalam database transaction.
- Tidak ada penghapusan keras untuk data transaksi, stok, dan audit.
- Dokumentasikan langkah **pemulihan** dan uji sekali sebelum serah terima.
- Pekerja antrian dijalankan lewat Supervisor dan otomatis hidup ulang. Job gagal masuk `failed_jobs` dan terlihat di UI sebagai status Gagal yang bisa dicoba ulang.

---

## 12. Pengujian dan Kualitas

- **Unit/feature (Pest):** perhitungan HPP, rata-rata tertimbang, konversi satuan, pengurangan dan pengembalian stok, penomoran transaksi, anti-duplikat, otorisasi peran, pipeline fallback (simulasikan timeout, 429, JSON rusak, key kosong), pencocokan menu, parser per platform.
- **Komponen Livewire:** tiap kartu diuji bahwa aksi hanya memperbarui kartu terkait.
- **Alur kritis (Dusk):** login, transaksi offline, unggah screenshot sampai simpan, void, ubah password, tes koneksi AI.
- **Dataset akurasi:** bagian 6.7.
- Analisis statis (Larastan) dan format kode (Pint) harus lulus.

---

## 13. Konvensi Kode

- Logika bisnis di service class (`SaleService`, `StockService`, `CostingService`, `ImportService`, `Ai/ExtractionPipeline`), komponen Livewire tipis.
- Form Request/Livewire Form Object untuk validasi, pesan validasi berbahasa Indonesia.
- Enum PHP untuk status, kanal, peran, jenis pergerakan.
- Komponen Blade UI di `resources/views/components/ui/*` memakai prop variant dan size yang konsisten.
- Dokumentasi di `docs/`: README instalasi, panduan penyetelan Supervisor/Cron, cara menambah penyedia AI, cara memperbarui template parser.

---

## 14. Fase Pengerjaan dan Definisi Selesai

| Fase | Isi | Selesai bila |
| --- | --- | --- |
| 0 | Instalasi, layout SPA (`wire:navigate`, `@persist`), komponen UI Blade, token desain dari direktori desain, login, peran, Pengaturan dasar (nama toko, password) | Navigasi tanpa reload penuh, tema sesuai desain |
| 1 | Kategori, menu, alias, bahan, pembelian, stok opname, resep, HPP | Tes HPP dan konsistensi stok lulus |
| 2 | Kasir offline, struk, input pesanan online manual, void, stok otomatis | Tes balapan dan idempotency lulus |
| 3 | Riwayat, laporan, dashboard, ekspor CSV | Total laporan terbukti sama dengan transaksi |
| 4 | Integrasi AI hybrid, pengaturan AI + tes koneksi, mesin lokal, konfirmasi, anti-duplikat | Target akurasi 6.7 tercapai, semua skenario fallback lulus |
| 5 | Keamanan, performa, backup, dokumentasi, serah terima | Semua daftar di bagian 9-12 lolos |

**Definisi selesai global:** semua kriteria penerimaan lolos, tidak ada dummy data, tes hijau, tidak ada reload halaman penuh pada interaksi, aplikasi berfungsi penuh dengan key AI kosong, dan kode serta akun berada di repositori/hosting milik pemilik.

---

## 15. Asumsi dan Pertanyaan Terbuka

- Perangkat kasir utama: PC. Cetak struk thermal belum termasuk (hanya tampilan cetak browser).
- Varian dan add-on dibuat sebagai menu terpisah pada versi 1.
- Kasir kedua dan seterusnya ditambahkan oleh Pemilik.
- Struktur komisi platform bersifat estimasi dan opsional.
- Penyedia AI mana yang dipakai awal ditentukan setelah uji 30 screenshot. Harga dan kebijakan data penyedia perlu dicek di situs resminya karena bisa berubah.
- Hosting (VPS) harus mendukung Supervisor, Cron, dan Tesseract/Imagick terpasang.
