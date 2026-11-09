<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('pos-daily-analysis')]
#[Title('Analisis Kinerja & Inventaris Harian')]
#[Description('Instruksi terstruktur bagi AI untuk melakukan evaluasi menyeluruh atas omzet, margin kotor, menu terlaris, dan bahan baku yang kritis hari ini.')]
class DailyAnalysisPrompt extends Prompt
{
    public function arguments(): array
    {
        return [
            new Argument('date', 'Tanggal yang ingin dianalisis (format: YYYY-MM-DD, default hari ini)', false),
        ];
    }

    public function handle(Request $request): Response
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        $promptText = <<<MARKDOWN
Anda adalah Konsultan Keuangan & Operasional POS Kuliner Senior.
Tugas Anda adalah melakukan analisis mendalam terhadap performa outlet kuliner pada tanggal: {$date}.

Langkah-langkah yang harus Anda lakukan menggunakan tools MCP yang tersedia:
1. Jalankan tool `get-financial-report-tool` untuk periode tanggal {$date} sampai {$date}.
2. Periksa sumber daya `pos://system/status` untuk mendeteksi bahan baku yang di bawah stok minimum atau habis.
3. Analisis metrik kunci:
   - Total Omzet & Jumlah Transaksi.
   - Efisiensi HPP (Harga Pokok Penjualan) & Persentase Gross Margin (apakah berada di rentang sehat kuliner 55% - 70%).
   - Menu Terlaris (Top Performers).
4. Berikan output laporan dalam format eksekutif yang rapi:
   - **Ringkasan Eksekutif**
   - **Evaluasi Keuangan (Omzet, Laba Kotor, Margin)**
   - **Sorotan Menu & Preferensi Pelanggan**
   - **Peringatan Inventaris & Rekomendasi Belanja/Restock Bahan**
   - **Saran Tindakan Strategis untuk Esok Hari**
MARKDOWN;

        return Response::text($promptText);
    }
}
