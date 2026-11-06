<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed initial system configuration, default owner account, and empty categories.
     * STRICT RULE: No dummy menus, recipes, ingredients, or transactions!
     */
    public function run(): void
    {
        // 1. Akun Pemilik Awal
        User::firstOrCreate(
            ['email' => 'owner@outlet.id'],
            [
                'name' => 'Pemilik Outlet',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'is_active' => true,
                'can_give_discount' => true,
            ]
        );

        // 2. Akun Kasir Awal (untuk pengujian kasir)
        User::firstOrCreate(
            ['email' => 'kasir@outlet.id'],
            [
                'name' => 'Kasir Utama',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
                'is_active' => true,
                'can_give_discount' => false,
            ]
        );

        // 3. Pengaturan Toko & AI Default
        $defaultSettings = [
            'store_name' => 'POS Kuliner Satu Outlet',
            'store_address' => 'Jl. Boulevard Raya No. 8, Jakarta',
            'store_phone' => '0812-3456-7890',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda. Selamat menikmati!',
            'gofood_commission_pct' => '20',
            'shopeefood_commission_pct' => '20',
            'ai_enabled' => '1',
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-1.5-flash',
            'ai_budget_limit' => '500000',
            'confidence_threshold' => '85',
            'screenshot_retention_days' => '30',
            'auto_save_ai' => '0',
        ];

        foreach ($defaultSettings as $key => $val) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $val, 'is_encrypted' => false]
            );
        }

        // 4. Kategori Awal (Struktur tanpa menu)
        $categories = [
            ['name' => 'Makanan Utama', 'sort_order' => 1],
            ['name' => 'Minuman', 'sort_order' => 2],
            ['name' => 'Camilan', 'sort_order' => 3],
            ['name' => 'Paket Hemat', 'sort_order' => 4],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                ['sort_order' => $cat['sort_order']]
            );
        }
    }
}
