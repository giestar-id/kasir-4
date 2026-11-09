# POS Kuliner - Model Context Protocol (MCP) Server

Server ini mengintegrasikan aplikasi POS Laravel dengan berbagai AI Agent (seperti **Cursor IDE**, **Claude Desktop**, **Antigravity**, **ChatGPT Developer Mode**, **Windsurf**, atau Agent LLM kustom) menggunakan protokol resmi Model Context Protocol (MCP) via `laravel/mcp`.

AI dapat melakukan **operasi apapun (CRUD penuh)** pada sistem POS Anda, mulai dari data master menu, kategori, bahan baku inventaris, resep HPP otomatis, transaksi kasir dengan pengurangan stok otomatis, penyesuaian stok opname/pembelian, laporan keuangan real-time, hingga eksekusi query database langsung.

---

## 🔑 Kredensial & Base URL (Authentication)

Setiap koneksi HTTP/SSE dari AI Client ke MCP Server diamankan menggunakan **API Key**.

| Parameter | Nilai Default / Format | Keterangan |
|---|---|---|
| **Base URL** | `http://localhost:8000/mcp/pos` | Endpoint POST utama untuk komunikasi JSON-RPC 2.0 |
| **Info URL** | `http://localhost:8000/mcp/info` | Endpoint GET publik untuk mengecek status & kapabilitas server |
| **Header Auth** | `Authorization: Bearer <MCP_API_KEY>` | Header otentikasi standar Bearer Token |
| **Header Alternatif** | `X-MCP-KEY: <MCP_API_KEY>` | Header otentikasi alternatif |
| **Query Auth** | `?api_key=<MCP_API_KEY>` | Parameter URL alternatif jika client tidak mendukung custom headers |

> 💡 **Cek atau Regenerate Kredensial via Terminal:**
> ```powershell
> # Melihat Base URL dan API Key aktif
> php artisan mcp:credentials
> 
> # Membuat API Key baru yang aman dan otomatis menyimpan ke .env
> php artisan mcp:credentials --generate
> ```

---

## 🚀 Cara Menghubungkan AI Client

### 1. Cursor IDE (Mode HTTP Remote dengan Kredensial)
Buat atau edit file `.cursor/mcp.json` di root folder project:

```json
{
  "mcpServers": {
    "pos-laravel": {
      "url": "http://localhost:8000/mcp/pos",
      "headers": {
        "Authorization": "Bearer pos_sec_hQueg7jqqeEnBSJMl57dGIAVN28BdUE0",
        "X-MCP-KEY": "pos_sec_hQueg7jqqeEnBSJMl57dGIAVN28BdUE0"
      }
    }
  }
}
```
*Ganti token di atas dengan API Key Anda yang tertera di `php artisan mcp:credentials`.*

---

### 2. Claude Desktop

#### Opsi A: Remote HTTP (Jika Claude mendukung URL Transport)
Edit `%APPDATA%\Claude\claude_desktop_config.json`:
```json
{
  "mcpServers": {
    "pos-laravel": {
      "url": "http://localhost:8000/mcp/pos",
      "headers": {
        "Authorization": "Bearer pos_sec_hQueg7jqqeEnBSJMl57dGIAVN28BdUE0"
      }
    }
  }
}
```

#### Opsi B: Local Stdio CLI (Tanpa perlu HTTP server menyala)
```json
{
  "mcpServers": {
    "pos-laravel": {
      "command": "php",
      "args": ["artisan", "mcp:start", "pos"],
      "cwd": "D:\\COBA\\project-id-1(llelet)"
    }
  }
}
```

---

### 3. Antigravity / Gemini CLI
Tambahkan ke konfigurasi MCP Antigravity:

```json
{
  "mcpServers": {
    "pos": {
      "command": "php",
      "args": ["artisan", "mcp:start", "pos"],
      "cwd": "D:\\COBA\\project-id-1(llelet)"
    }
  }
}
```

---

### 4. Uji Coba Cepat via cURL / PowerShell
```powershell
# 1. Cek Info Server
curl http://localhost:8000/mcp/info

# 2. Test Tools List via HTTP dengan Kredensial
curl -X POST "http://localhost:8000/mcp/pos" `
  -H "Authorization: Bearer pos_sec_hQueg7jqqeEnBSJMl57dGIAVN28BdUE0" `
  -H "Content-Type: application/json" `
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}'
```

---

## 🛠️ Daftar Lengkap Fitur & Kapabilitas

### 1. 9 Tools MCP (Full CRUD)

| Tool Name | Kategori | Fungsi & Kemampuan CRUD |
|---|---|---|
| `manage-menu-tool` | **Menu Makanan & Minuman** | `list` (cari menu), `get` (detail menu + resep), `create` (tambah menu baru), `update` (edit nama, harga jual, barcode, status aktif), `delete` (hapus menu). |
| `manage-category-tool` | **Kategori Menu** | `list` (semua kategori + total menu), `create` (tambah kategori baru), `update` (edit nama/deskripsi/ikon), `delete` (hapus kategori). |
| `manage-ingredient-tool` | **Bahan Baku (Inventory)** | `list` (lihat semua stok bahan & satuan), `get` (detail bahan), `create` (tambah bahan baru + rasio konversi unit beli ke unit pakai), `update` (edit nama, minimum stock, rasio). |
| `manage-recipe-tool` | **Resep & HPP** | `get` (lihat komposisi resep & kalkulasi HPP otomatis), `set` (atur/ubah bahan-bahan resep & biaya packaging, otomatis update modal HPP menu). |
| `create-sale-tool` | **Transaksi Kasir (POS)** | Buat transaksi baru dengan multi-item, metode bayar (`cash`, `qris`, `transfer`), diskon, catatan, dan nama pelanggan. **Otomatis memotong stok bahan baku** via atomic transaction di `SaleService`. |
| `manage-sale-tool` | **Riwayat Transaksi** | `list` (filter transaksi berdasarkan tanggal, kasir, status), `detail` (rincian item terjual & struk), `void` (batalkan transaksi & **otomatis mengembalikan stok bahan baku** yang sudah dipotong). |
| `manage-stock-tool` | **Operasi Stok Bahan** | `opname` (rekonsiliasi fisik & pencatatan waste/selisih), `purchase` (catat pembelian bahan masuk & otomatis hitung harga pokok rata-rata tertimbang / moving average), `movements` (kartu stok & histori perubahan). |
| `get-financial-report-tool`| **Laporan & Analytics** | Analisis keuangan lengkap: omzet kotor, omzet bersih, total HPP, laba kotor, persentase margin laba, jumlah transaksi, rincian per metode bayar, dan daftar menu terlaris (top sellers). |
| `universal-database-tool` | **Universal Database CRUD** | Tool sakti untuk AI: `tables` (lihat semua tabel), `describe` (lihat kolom & tipe data), `select` (jalankan custom query SQL SELECT), `insert` (tambah record ke tabel apapun), `update` (ubah data di tabel apapun dengan WHERE condition), `delete` (hapus data di tabel apapun dengan WHERE condition). |

---

### 2. 3 Resources MCP (Data Real-Time untuk AI)

AI dapat membaca sumber daya data langsung menggunakan protokol MCP (`resources/read`):

| URI Resource | Nama | Deskripsi |
|---|---|---|
| `pos://system/status` | **Status Sistem & Outlet** | Ringkasan kondisi outlet, jumlah menu aktif, peringatan stok bahan baku yang habis atau menipis (`low_stock_alerts`), dan transaksi hari ini. |
| `pos://reports/today` | **Ringkasan Penjualan Hari Ini** | Laporan real-time omzet hari ini, total HPP, laba kotor, margin %, dan breakdown metode pembayaran. |
| `pos://catalog/menus` | **Katalog Menu Aktif** | Seluruh menu aktif lengkap dengan kategori, harga jual, HPP, margin %, dan kelengkapan resep. |

---

### 3. 2 Prompts MCP (Asisten Terpandu untuk AI)

AI dapat memuat template alur kerja profesional (`prompts/get`):

| Prompt Name | Judul | Deskripsi Alur Kerja |
|---|---|---|
| `pos-daily-analysis` | **Analisis Kinerja & Inventaris Harian** | Menginstruksikan AI menjadi konsultan keuangan & operasional kuliner untuk mengevaluasi omzet, margin kotor, menu terlaris, dan rekomendasi belanja bahan baku esok hari. |
| `pos-menu-wizard` | **Panduan Tambah Menu & Hitung HPP** | Memandu AI dan user langkah demi langkah merancang menu baru, menyusun takaran bahan baku, menghitung HPP per porsi, dan menentukan harga jual yang menguntungkan. |

---

## 💡 Contoh Interaksi AI Setelah Terhubung

1. *"Berapa omzet kita hari ini dan bahan apa saja yang stoknya hampir habis?"*
   *(AI membaca resource `pos://system/status` dan `pos://reports/today`)*
2. *"Tolong tambahkan menu baru 'Matcha Boba Latte' seharga Rp 24.000 dan buatkan resepnya menggunakan bubuk matcha 15g, susu 150ml, boba 40g, dan cup Rp 1.000."*
   *(AI menjalankan `manage-menu-tool` dan `manage-recipe-tool`)*
3. *"Catat pembelian 5 kg bubuk matcha seharga Rp 450.000 dari Toko Bahan Kue."*
   *(AI menjalankan `manage-stock-tool` action: purchase)*
4. *"Buatkan pesanan kasir Meja 2: 2 Matcha Boba Latte dan 1 Croissant, bayar pakai QRIS Rp 68.000."*
   *(AI menjalankan `create-sale-tool` dan otomatis memotong stok bahan)*
5. *"Pelanggan Meja 2 batal, tolong batalkan transaksinya."*
   *(AI menjalankan `manage-sale-tool` action: void dan otomatis mengembalikan stok)*
