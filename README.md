# POS Kuliner Satu Outlet

Aplikasi Point of Sale (POS) kuliner web-based berbasis Laravel 11/12, Livewire 3, Tailwind CSS, Alpine.js, dan Lucide Icons. Dirancang khusus untuk satu outlet kuliner dengan arsitektur single-page application (SPA), desain glassmorphism di atas soft mesh gradient, integrasi AI Hybrid (Cloud AI API + Mesin Lokal OCR) untuk pesanan online (GoFood & ShopeeFood), manajemen resep & HPP otomatis, kontrol stok multi-satuan, serta pencatatan audit log lengkap.

---

## 1. Ringkasan Fitur Utama

- **Autentikasi & Otorisasi Ketat:** Login email/username dengan rate limiting (maksimal 5 percobaan/menit), session timeout, dan pembatasan peran berbasis middleware server-side.
- **Dashboard Eksekutif (Pemilik):** Pemantauan omzet real-time, estimasi laba kotor, status AI & antrian pesanan online, grafik performa SVG, 5 menu terlaris, pembagian kanal, dan peringatan stok kritis.
- **Kasir POS Offline Cepat (< 15 detik):**
  - Grid menu adaptif dengan tab kategori dan pencarian instan (shortcut `/`).
  - Keranjang dinamis dengan stepper jumlah, catatan per item, dan catatan pesanan.
  - Modal pembayaran dengan metode Tunai, QRIS, Transfer, diskon nominal/persen berizin, dan tombol nominal cepat.
  - Struk transaksi siap cetak browser (format struk thermal 58mm/80mm).
  - Penomoran transaksi unik berurutan per hari (`TRX-YYYYMMDD-0001`) dan proteksi idempotency klik ganda.
- **Pesanan Online Hybrid AI & Manual:**
  - Ekstraksi screenshot pesanan GoFood & ShopeeFood dengan AI API (Gemini, Claude, GPT, OpenRouter, Custom) atau fallback Mesin Lokal OCR jika kuota/koneksi habis.
  - Proteksi anti-duplikat 3 lapis: Kunci unik database `(channel, external_order_id)`, Hash file SHA-256 gambar, dan validasi selisih waktu/total.
  - Modal konfirmasi berdampingan (gambar vs hasil bacaan), pencocokan Levenshtein nama menu & alias platform, serta opsi simpan alias otomatis.
  - Jalur manual cadangan dengan kalkulasi penyesuaian platform otomatis.
- **Manajemen Menu, Resep & HPP:**
  - Satu harga untuk semua kanal tanpa markup tersembunyi.
  - Resep per porsi dalam satuan dasar dan biaya kemasan/packaging.
  - Perhitungan HPP otomatis serta snapshot harga dan HPP pada setiap transaksi.
  - Penandaan menu tanpa resep (*Resep belum lengkap*).
- **Inventaris & Bahan Baku:**
  - Multi-satuan: satuan dasar (gram, ml, pcs) dan satuan beli (kg, liter, dus) dengan faktor konversi otomatis.
  - Perhitungan harga rata-rata tertimbang (*weighted average cost*) saat pembelian.
  - Seluruh mutasi stok tercatat dalam buku besar `stock_movements` (tidak ada perubahan stok tanpa jejak).
  - Stok Opname fisik dan pencatatan bahan terbuang (*waste*) dengan alasan wajib.
- **Laporan & Ekspor:**
  - Agregasi SQL murni untuk performa tinggi (per periode harian, kanal penjualan, kategori, menu, dan nilai aset stok).
  - Ekspor data laporan langsung ke berkas CSV (UTF-8).
- **Pengaturan Outlet:**
  - Profil toko & catatan kaki struk.
  - Keamanan akun pemilik & manajemen kasir tambahan dengan hak izin diskon.
  - Integrasi AI dengan enkripsi API key AES-256, uji koneksi ringan, dan dry-run ekstraksi gambar.
  - Ekspor cadangan database ke format SQL.

---

## 2. Role dan Hak Akses

| Fitur / Layar | Pemilik (Owner) | Kasir (Cashier) |
|---|:---:|:---:|
| Dashboard Omzet & Laba | Ya | Tidak (403) |
| Kasir POS Offline | Ya | Ya |
| Pesanan Online (AI & Manual) | Ya | Ya |
| Riwayat Transaksi | Semua Periode | Hari Berjalan Saja |
| Batalkan / Void Transaksi | Ya | Ya (dengan alasan wajib) |
| Lihat Saldo Stok Bahan | Ya | Ya (stok saja) |
| Lihat HPP & Harga Beli Bahan | Ya | **Tidak (Dihapus server-side)** |
| Kelola Menu & Resep | Ya | Tidak (403) |
| Kelola Kategori | Ya | Tidak (403) |
| Catat Pembelian Bahan | Ya | Tidak (403) |
| Stok Opname & Penyesuaian | Ya | Tidak (403) |
| Laporan & Ekspor CSV | Ya | Tidak (403) |
| Pengaturan Toko & AI | Ya | Tidak (403) |

---

## 3. Asumsi dan Resolusi Bagian Ambigu

Sebelum dan selama implementasi, keputusan teknis berikut diterapkan sesuai standar keamanan dan kepatuhan PRD:
1. **Penyedia AI Default & Fallback:** Sistem default menggunakan Google Gemini dengan model `gemini-1.5-flash`. Jika API key belum diisi atau kuota habis, sistem secara transparan dan otomatis mengalihkan proses ke Mesin Lokal OCR tanpa menghentikan alur kasir.
2. **Kerahasiaan Data Pelanggan:** Nama dan alamat pelanggan disaring secara ketat pada prompt AI dan parser lokal agar tidak tersimpan ke database sesuai aturan privasi F&B.
3. **Database Driver:** Konfigurasi default menggunakan SQLite yang efisien dan siap pakai langsung tanpa konfigurasi server tambahan, serta mendukung migrasi langsung ke MySQL 8 dengan mengubah konfigurasi `.env`.
4. **Stok Negatif:** Sesuai PRD 5.4, saldo stok bahan baku yang mencapai angka negatif di dapur tidak memblokir penjualan di kasir, melainkan ditandai merah dan dimunculkan di widget dashboard agar operasional kasir tetap lancar.
5. **Varian Menu:** Mengikuti ketentuan PRD versi 1, setiap varian menu dibuat sebagai menu terpisah (misal: *Ayam Geprek Level 1*, *Ayam Geprek Level 2*).

---

## 4. Struktur Folder Modular

```
project-id-1/
├── app/
│   ├── Http/
│   │   └── Middleware/
│   │       └── EnsureOwnerRole.php        # Proteksi hak akses Pemilik
│   ├── Livewire/
│   │   ├── Auth/Login.php                 # S1: Layar Masuk
│   │   ├── Dashboard.php                  # S2 & S3: Dashboard Outlet & Modal Rentang
│   │   ├── Pos/Cashier.php                # S4, S5, S6: Kasir POS, Pembayaran, Struk
│   │   ├── OnlineOrders/Index.php         # S7, S8, S9, S10: Pesanan Online & AI
│   │   ├── Transactions/History.php       # S11, S12, S13: Riwayat & Void
│   │   ├── Menu/Index.php                 # S14, S15, S16, S17: Menu, Resep HPP, Hapus
│   │   ├── Categories/Index.php           # S18, S19, S20: Kategori Menu
│   │   ├── Ingredients/Index.php          # S8: Bahan Baku & Kartu Stok
│   │   ├── Purchases/Index.php            # S9: Pembelian Bahan Baku
│   │   ├── StockOpname/Index.php          # S10: Stok Opname Fisik & Waste
│   │   ├── Reports/Index.php              # S11: Laporan Penjualan & Ekspor CSV
│   │   └── Settings/Index.php             # S12: Pengaturan Outlet, AI, Backup
│   ├── Models/
│   │   ├── User.php, Setting.php, Category.php, Menu.php, MenuAlias.php
│   │   ├── MenuExtraCost.php, Ingredient.php, Recipe.php, Purchase.php
│   │   ├── StockMovement.php, Sale.php, SaleItem.php, ScreenshotImport.php
│   │   ├── AiCallLog.php, AuditLog.php
│   │   └── ...
│   └── Services/
│       ├── StockService.php               # Mutasi stok, atomic deduction, weighted average
│       ├── CostingService.php             # Kalkulasi HPP resep & margin keuntungan
│       ├── SaleService.php                # Pembuatan transaksi, penomoran unik, void
│       ├── AuditService.php               # Pencatatan audit trail
│       └── Ai/
│           ├── ExtractionPipeline.php     # Orkestrasi alur hybrid AI API & lokal fallback
│           ├── AiApiExtractor.php         # Adapter Gemini, Claude, OpenAI, OpenRouter
│           ├── LocalOcrExtractor.php      # Mesin parsing lokal cadangan
│           ├── MenuMatcher.php            # Algoritma Levenshtein pencocokan menu & alias
│           └── ExtractionResult.php       # Data transfer object hasil ekstraksi
├── database/
│   ├── migrations/                        # 8 migrasi skema database InnoDB/SQLite
│   └── seeders/
│       └── DatabaseSeeder.php             # Seeder akun awal & pengaturan default
├── resources/
│   ├── css/
│   │   └── app.css                        # Tailwind 4, CSS variables, Glassmorphism, Thermal Print
│   ├── js/
│   │   └── app.js                         # Inisialisasi ikon Lucide & event toast reaktif
│   └── views/
│       ├── components/
│       │   ├── nav-link.blade.php
│       │   └── ui/
│       │       ├── badge.blade.php        # Komponen badge status
│       │       ├── button.blade.php       # Komponen tombol serbaguna
│       │       ├── card.blade.php         # Komponen kartu kaca glassmorphic
│       │       ├── confirm-modal.blade.php# Dialog konfirmasi aksi berisiko
│       │       ├── empty-state.blade.php  # Tampilan state kosong ramah pengguna
│       │       ├── icon.blade.php         # Wrapper ikon Lucide SVG
│       │       ├── input.blade.php        # Input teks dengan icon & validasi
│       │       ├── modal.blade.php        # Modal dialog tengah terpusat
│       │       ├── select.blade.php       # Dropdown select kustom
│       │       └── toast.blade.php        # Notifikasi toast reaktif mengambang
│       ├── layouts/
│       │   ├── app.blade.php              # Layout master dengan mesh gradient
│       │   └── guest.blade.php            # Layout autentikasi
│       └── livewire/                      # Seluruh template Blade komponen reaktif
├── routes/
│   └── web.php                            # Definisi 26 rute aplikasi
└── tests/
    └── Feature/
        ├── AuthAndRoleTest.php            # Tes autentikasi & proteksi hak akses
        ├── CostingAndStockTest.php        # Tes hitung HPP 150g ayam & mutasi stok
        ├── PosAndAntiDuplicateTest.php    # Tes penomoran urut & anti-duplikat
        ├── HybridAiPipelineTest.php       # Tes pipeline fallback AI & pencocokan alias
        └── LivewirePagesTest.php          # Tes interaksi antarmuka komponen Livewire
```

---

## 5. Cara Menjalankan Aplikasi

### A. Prasyarat Sistem
- PHP 8.3 atau 8.4
- Node.js 18+ dan NPM
- Composer 2+

### B. Langkah Instalasi & Menjalankan

1. **Jalankan Migrasi Database & Seeder:**
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Kompilasi Aset Frontend:**
   ```bash
   npm run build
   ```

3. **Jalankan Server Lokal Laravel:**
   ```bash
   php artisan serve
   ```
   Aplikasi dapat diakses melalui peramban web pada alamat: `http://127.0.0.1:8000`

---

## 6. Akun Default untuk Pengujian

Aplikasi telah disiapkan dengan akun awal:

- **Akun Pemilik (Owner):**
  - Email / Username: `owner@outlet.id`
  - Password: `password123`
  - Akses: Semua layar (Dashboard, Menu & HPP, Kategori, Bahan Baku, Pembelian, Stok Opname, Laporan, Pengaturan).

- **Akun Kasir (Cashier):**
  - Email / Username: `kasir@outlet.id`
  - Password: `password123`
  - Akses: Kasir POS, Pesanan Online, Riwayat Transaksi Hari Berjalan, Pantau Stok Bahan (tanpa nilai HPP / laba).
