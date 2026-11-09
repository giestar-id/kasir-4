# POS MCP (Model Context Protocol) Server

Server MCP ini mengintegrasikan aplikasi POS Laravel dengan berbagai AI Agent (seperti **Claude Desktop**, **Cursor**, **Antigravity**, **ChatGPT Developer Mode**, atau agent LLM lainnya) menggunakan protokol resmi Model Context Protocol (MCP) via `laravel/mcp`.

AI dapat melakukan **operasi apapun (CRUD penuh)** pada sistem POS Anda, mulai dari data master, resep, transaksi kasir dengan pengurangan stok otomatis, penyesuaian stok opname/pembelian, laporan keuangan, hingga eksekusi query database langsung.

---

## 🛠️ Daftar Tools MCP yang Tersedia

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

## 🚀 Cara Menghubungkan ke AI Client

### 1. Claude Desktop
Edit konfigurasi Claude Desktop di:
- **Windows:** `%APPDATA%\Claude\claude_desktop_config.json`
- **macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`

Tambahkan konfigurasi berikut ke dalam objek `"mcpServers"`:

```json
{
  "mcpServers": {
    "pos-laravel": {
      "command": "php",
      "args": [
        "artisan",
        "mcp:start",
        "pos"
      ],
      "cwd": "D:\\COBA\\project-id-1(llelet)"
    }
  }
}
```

> **Tips:** Pastikan path binary `php` sudah terdaftar di Environment Variable `PATH` sistem Anda, atau ganti `"php"` dengan full path seperti `"C:\\laragon\\bin\\php\\php-8.2.x\\php.exe"`.

---

### 2. Cursor IDE
Buat atau edit file `.cursor/mcp.json` di root direktori project:

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

Setelah itu buka **Cursor Settings > Features > MCP**, pastikan status `pos-laravel` berwarna hijau (Connected).

---

### 3. Antigravity / Gemini CLI
Tambahkan server ke file konfigurasi MCP Antigravity (`agy`):

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

### 4. Web MCP Endpoint (HTTP / SSE)
Server ini juga otomatis mengekspos endpoint web:
- **URL:** `http://localhost:8000/mcp/pos`

Dapat digunakan jika AI Client mendukung koneksi MCP over HTTP/SSE.

---

## 🧪 Uji Coba Manual via Terminal

Anda dapat mengetes apakah MCP server merespons JSON-RPC dengan benar melalui command line:

```powershell
cd "D:\COBA\project-id-1(llelet)"
php artisan mcp:start pos
```

Ketikkan pesan `initialize` berikut lalu tekan Enter:
```json
{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test-client","version":"1.0.0"}}}
```

Server akan langsung membalas dengan status sukses dan daftar capabilities.
Ketikkan pesan `tools/list` berikut untuk melihat seluruh definisi tools:
```json
{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}
```

---

## 💡 Contoh Prompt yang Bisa Anda Berikan ke AI Setelah Terhubung

1. *"Tolong tambahkan menu baru 'Kopi Susu Gula Aren' seharga Rp 22.000 di kategori Kopi, lalu buatkan resepnya dengan bahan espresso 30ml, susu segar 150ml, dan gula aren 20ml."*
2. *"Cek laporan keuangan untuk minggu ini, berapa total omzet dan menu apa yang paling laku?"*
3. *"Catat pembelian bahan baku susu segar sebanyak 10 liter dengan total biaya Rp 180.000."*
4. *"Buatkan transaksi kasir baru untuk Meja 4: 2 Kopi Susu dan 1 Croissant, bayar pakai QRIS."*
5. *"Batalkan transaksi TRX-20261109-0001 karena pelanggan salah pesan, dan pastikan stok bahannya sudah kembali."*
6. *"Tampilkan semua bahan baku yang stoknya di bawah minimum stock."*
