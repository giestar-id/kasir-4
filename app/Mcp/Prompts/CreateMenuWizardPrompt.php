<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('pos-menu-wizard')]
#[Title('Panduan Tambah Menu & Hitung HPP')]
#[Description('Panduan interaktif untuk membantu kasir atau owner membuat menu kuliner baru, merancang resep bahan baku, menghitung biaya pokok porsi, dan menentukan harga jual yang menguntungkan.')]
class CreateMenuWizardPrompt extends Prompt
{
    public function arguments(): array
    {
        return [
            new Argument('menu_name', 'Nama calon menu baru yang ingin dibuat', true),
            new Argument('target_margin_pct', 'Target persentase margin keuntungan kotor (contoh: 60)', false),
        ];
    }

    public function handle(Request $request): Response
    {
        $menuName = $request->get('menu_name', 'Menu Baru');
        $targetMargin = (int) $request->get('target_margin_pct', 65);

        $promptText = <<<MARKDOWN
Anda adalah Asisten Chef & Costing Specialist POS Kuliner.
Tugas Anda adalah memandu proses pembuatan menu baru: **{$menuName}** dengan target gross margin **{$targetMargin}%**.

Instruksi kerja Anda:
1. Periksa kategori yang ada menggunakan `manage-category-tool` (action: 'list'). Jika kategori yang cocok belum ada, tanyakan kepada pengguna atau buat kategori baru.
2. Periksa inventaris bahan baku menggunakan `manage-ingredient-tool` (action: 'list'). Identifikasi bahan baku apa saja yang diperlukan untuk menyusun resep porsi {$menuName}. Jika ada bahan yang belum terdaftar di sistem, buat bahan baru tersebut dengan satuan beli dan faktor konversi yang tepat.
3. Rancang komposisi resep per porsi (misal gram kopi, ml susu, sendok gula, packaging cup/box).
4. Hitung estimasi HPP (Harga Pokok Penjualan):
   - HPP = Total biaya bahan baku porsi + biaya kemasan/packaging.
   - Rekomendasi Harga Jual = HPP / (1 - ({$targetMargin} / 100)), lalu bulatkan ke kelipatan seribu terdekat (misal Rp 22.000).
5. Tanyakan konfirmasi kepada pengguna. Setelah pengguna setuju:
   - Buat menu menggunakan `manage-menu-tool` (action: 'create').
   - Hubungkan resepnya menggunakan `manage-recipe-tool` (action: 'set').
6. Berikan konfirmasi akhir beserta ringkasan margin keuntungan yang didapat.
MARKDOWN;

        return Response::text($promptText);
    }
}
