<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendUiComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ui_button_renders_all_variants_and_sizes(): void
    {
        // 1. Primary button with icon
        $view = $this->blade('<x-ui.button variant="primary" size="md" icon="plus">Tambah Menu</x-ui.button>');
        $view->assertSee('Tambah Menu');
        $view->assertSee('bg-[#5E63E8]');
        $view->assertSee('type="button"', false);

        // 2. Submit button with danger variant
        $view = $this->blade('<x-ui.button variant="danger" size="sm" type="submit">Hapus Data</x-ui.button>');
        $view->assertSee('Hapus Data');
        $view->assertSee('bg-[#E5484D]');
        $view->assertSee('type="submit"', false);
        $view->assertSee('h-8');

        // 3. Secondary button large
        $view = $this->blade('<x-ui.button variant="secondary" size="lg">Batal</x-ui.button>');
        $view->assertSee('Batal');
        $view->assertSee('h-12');

        // 4. Disabled button
        $view = $this->blade('<x-ui.button disabled>Memproses...</x-ui.button>');
        $view->assertSee('disabled', false);
        $view->assertSee('disabled:cursor-not-allowed');
    }

    public function test_ui_modal_renders_structure_and_events(): void
    {
        $view = $this->blade(
            '<x-ui.modal name="test-modal" title="Judul Modal Uji" size="lg">
                <p>Isi Modal Konten</p>
                <x-slot:footer>
                    <button>Simpan</button>
                </x-slot:footer>
            </x-ui.modal>'
        );

        $view->assertSee('Judul Modal Uji');
        $view->assertSee('Isi Modal Konten');
        $view->assertSee('Simpan');
        $view->assertSee("open-modal.window=\"if (isTarget(\$event.detail, 'test-modal')) show = true\"", false);
        $view->assertSee("close-modal.window=\"if (!\$event.detail || isTarget(\$event.detail, 'test-modal')) show = false\"", false);
        $view->assertSee('max-w-[640px]');
    }

    public function test_ui_badge_renders_variants_and_dot(): void
    {
        // Success with dot
        $view = $this->blade('<x-ui.badge variant="success" dot>Aktif</x-ui.badge>');
        $view->assertSee('Aktif');
        $view->assertSee('bg-[#2EB872]');

        // Error badge
        $view = $this->blade('<x-ui.badge variant="error">Nonaktif</x-ui.badge>');
        $view->assertSee('Nonaktif');
        $view->assertSee('bg-[#E5484D]/10');

        // GoFood channel badge
        $view = $this->blade('<x-ui.badge variant="gofood">GoFood</x-ui.badge>');
        $view->assertSee('GoFood');
        $view->assertSee('bg-[#E3F5FC]');

        // ShopeeFood channel badge
        $view = $this->blade('<x-ui.badge variant="shopeefood">ShopeeFood</x-ui.badge>');
        $view->assertSee('ShopeeFood');
        $view->assertSee('bg-[#FDEEE4]');
    }

    public function test_ui_input_renders_label_required_and_errors(): void
    {
        // Standard input with required and hint
        $view = $this->blade(
            '<x-ui.input 
                name="user_email" 
                label="Alamat Email" 
                required 
                hint="Gunakan email aktif" 
                placeholder="nama@email.com" 
            />'
        );

        $view->assertSee('Alamat Email');
        $view->assertSee('*');
        $view->assertSee('Gunakan email aktif');
        $view->assertSee('placeholder="nama@email.com"', false);

        // Input with error message
        $viewWithError = $this->blade(
            '<x-ui.input 
                name="user_email" 
                label="Alamat Email" 
                error="Format email tidak valid" 
            />'
        );

        $viewWithError->assertSee('Format email tidak valid');
        $viewWithError->assertSee('border-[#E5484D]');
    }

    public function test_ui_select_renders_label_options_and_errors(): void
    {
        $view = $this->blade(
            '<x-ui.select name="role" label="Peran Akun" required>
                <option value="cashier">Kasir</option>
                <option value="owner">Pemilik</option>
            </x-ui.select>'
        );

        $view->assertSee('Peran Akun');
        $view->assertSee('*');
        $view->assertSee('Kasir');
        $view->assertSee('Pemilik');

        // Select with error
        $viewError = $this->blade(
            '<x-ui.select name="role" label="Peran Akun" error="Peran wajib dipilih">
                <option value="">Pilih</option>
            </x-ui.select>'
        );
        $viewError->assertSee('Peran wajib dipilih');
    }

    public function test_ui_empty_state_renders_title_description_and_action(): void
    {
        $view = $this->blade(
            '<x-ui.empty-state 
                icon="receipt" 
                title="Belum Ada Transaksi" 
                description="Semua transaksi akan muncul di sini.">
                <x-ui.button>Buat Transaksi</x-ui.button>
            </x-ui.empty-state>'
        );

        $view->assertSee('Belum Ada Transaksi');
        $view->assertSee('Semua transaksi akan muncul di sini.');
        $view->assertSee('Buat Transaksi');
    }

    public function test_sidebar_renders_navigation_links_based_on_user_role(): void
    {
        // 1. Owner can see all menu links
        $owner = User::factory()->create(['role' => 'owner', 'name' => 'Pak Bos']);
        $this->actingAs($owner);

        $responseOwner = $this->get(route('dashboard'));
        $responseOwner->assertOk();
        $responseOwner->assertSee('Dashboard');
        $responseOwner->assertSee('Kasir POS');
        $responseOwner->assertSee('Pesanan Online');
        $responseOwner->assertSee('Riwayat Transaksi');
        $responseOwner->assertSee('Bahan Baku');
        $responseOwner->assertSee('Menu & Resep');
        $responseOwner->assertSee('Pembelian Bahan');
        $responseOwner->assertSee('Stok Opname');
        $responseOwner->assertSee('Laporan Penjualan');
        $responseOwner->assertSee('Kelola Pekerja');
        $responseOwner->assertSee('Pengaturan');

        // 2. Cashier only sees cashier links
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Mbak Kasir']);
        $this->actingAs($cashier);

        $responseCashier = $this->get(route('pos'));
        $responseCashier->assertOk();
        $responseCashier->assertSee('Kasir POS');
        $responseCashier->assertSee('Pesanan Online');
        $responseCashier->assertSee('Riwayat Transaksi');
        $responseCashier->assertSee('Pantau Stok Bahan');

        // Cashier MUST NOT see owner-only links in sidebar
        $responseCashier->assertDontSee('Dashboard');
        $responseCashier->assertDontSee('Menu & Resep');
        $responseCashier->assertDontSee('Pembelian Bahan');
        $responseCashier->assertDontSee('Stok Opname');
        $responseCashier->assertDontSee('Laporan Penjualan');
        $responseCashier->assertDontSee('Kelola Pekerja');
    }

    public function test_sidebar_collapse_toggle_script_and_attributes_exist(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('toggleDesktopSidebar()', false);
        $response->assertSee("localStorage.getItem('pos_sidebar_collapsed')", false);
        $response->assertSee('desktopCollapsed', false);
        $response->assertSee('mobileNavOpen', false);
    }

    public function test_ui_card_renders_title_subtitle_actions_and_footer(): void
    {
        $view = $this->blade(
            '<x-ui.card title="Statistik Toko" subtitle="Ringkasan 30 hari terakhir" hoverable>
                <x-slot:actions>
                    <button id="act-btn">Refresh</button>
                </x-slot:actions>
                <p>Konten Kartu Utama</p>
                <x-slot:footer>
                    <span>Terakhir diperbarui 5 menit lalu</span>
                </x-slot:footer>
            </x-ui.card>'
        );

        $view->assertSee('Statistik Toko');
        $view->assertSee('Ringkasan 30 hari terakhir');
        $view->assertSee('Refresh');
        $view->assertSee('Konten Kartu Utama');
        $view->assertSee('Terakhir diperbarui 5 menit lalu');
        $view->assertSee('glass-card-hover');
    }

    public function test_ui_toast_renders_listeners_and_container(): void
    {
        $view = $this->blade('<x-ui.toast />');

        $view->assertSee('toast-message.window', false);
        $view->assertSee('toast.window', false);
        $view->assertSee('show-toast.window', false);
        $view->assertSee('toasts: []', false);
    }

    public function test_ui_confirm_modal_renders_structure(): void
    {
        $view = $this->blade(
            '<x-ui.confirm-modal
                name="delete-item"
                title="Hapus Menu Pilihan"
                message="Data yang dihapus tidak dapat dikembalikan."
                confirmText="Ya, Hapus Sekarang"
                cancelText="Batalkan"
                variant="danger"
            />'
        );

        $view->assertSee('Hapus Menu Pilihan');
        $view->assertSee('Data yang dihapus tidak dapat dikembalikan.');
        $view->assertSee('Ya, Hapus Sekarang');
        $view->assertSee('Batalkan');
        $view->assertSee('open-confirm-delete-item', false);
        $view->assertSee('close-confirm-delete-item', false);
    }
}
