---
version: alpha
name: POS Kuliner Satu Outlet
description: Desktop-first web application · Warm sand sidebar, crisp white cards on a cool off-white canvas, bold periwinkle primary actions, and clean tabular data hierarchy.
colors:
  primary: "#5E63E8"
  on-primary: "#FFFFFF"
  secondary: "#EFECE1"
  background: "#F8F8FA"
  surface: "#FFFFFF"
  on-surface: "#1E2029"
  on-surface-muted: "#8E92A4"
  outline: "#ECEEF2"
  error: "#E5484D"
  success: "#2EB872"
  scrim: "#1E2029"
typography:
  headline-lg: { fontFamily: "Plus Jakarta Sans", fontSize: 24px, fontWeight: 700, lineHeight: 1.25, letterSpacing: -0.01em }
  title-md: { fontFamily: "Plus Jakarta Sans", fontSize: 15px, fontWeight: 600, lineHeight: 1.35, letterSpacing: -0.005em }
  body-md: { fontFamily: "Plus Jakarta Sans", fontSize: 13px, fontWeight: 500, lineHeight: 1.5, letterSpacing: 0em }
  label-md: { fontFamily: "Plus Jakarta Sans", fontSize: 13px, fontWeight: 600, lineHeight: 1.25, letterSpacing: 0em }
  caption: { fontFamily: "Plus Jakarta Sans", fontSize: 11px, fontWeight: 500, lineHeight: 1.35, letterSpacing: 0.01em }
rounded: { sm: 6px, md: 10px, lg: 16px, full: 9999px }
spacing: { xs: 4px, sm: 8px, md: 16px, lg: 24px, xl: 32px, gutter: 20px, margin: 28px }
components:
  button-primary: { backgroundColor: "{colors.primary}", textColor: "{colors.on-primary}", rounded: "{rounded.md}", padding: 10px 16px, typography: "{typography.label-md}" }
  button-primary-pressed: { backgroundColor: "#4D52D6", textColor: "{colors.on-primary}", rounded: "{rounded.md}", padding: 10px 16px, typography: "{typography.label-md}" }
  button-secondary: { backgroundColor: "{colors.surface}", textColor: "{colors.on-surface}", rounded: "{rounded.md}", padding: 10px 16px, typography: "{typography.label-md}" }
  button-danger: { backgroundColor: "{colors.error}", textColor: "{colors.on-primary}", rounded: "{rounded.md}", padding: 10px 16px, typography: "{typography.label-md}" }
  input: { backgroundColor: "{colors.surface}", textColor: "{colors.on-surface}", borderColor: "{colors.outline}", rounded: "{rounded.md}", padding: 10px 14px, typography: "{typography.body-md}" }
  select: { backgroundColor: "{colors.surface}", textColor: "{colors.on-surface}", borderColor: "{colors.outline}", rounded: "{rounded.md}", padding: 10px 14px, typography: "{typography.body-md}" }
  card: { backgroundColor: "{colors.surface}", rounded: "{rounded.lg}", padding: 24px }
  table: { backgroundColor: "{colors.surface}", borderColor: "{colors.outline}", rounded: "{rounded.lg}", cellPadding: 14px 16px, typography: "{typography.body-md}" }
  badge: { rounded: "{rounded.sm}", padding: 4px 8px, typography: "{typography.caption}" }
  tabs: { activeColor: "{colors.primary}", inactiveColor: "{colors.on-surface-muted}", borderColor: "{colors.outline}", typography: "{typography.label-md}" }
  modal-dialog: { backgroundColor: "{colors.surface}", rounded: "{rounded.lg}", padding: 24px }
  modal-scrim: { backgroundColor: "{colors.scrim}" }
  confirm-dialog: { backgroundColor: "{colors.surface}", rounded: "{rounded.lg}", padding: 24px }
---

# POS Kuliner Satu Outlet

## Overview
POS Kuliner Satu Outlet is a single-outlet culinary Point of Sale, inventory, HPP recipe costing, and hybrid AI online-order extraction web application for Indonesian F&B owners (`Pemilik`) and cashiers (`Kasir`). Its visual personality combines a warm sand navigation sidebar (`#EFECE1`) with crisp white cards (`#FFFFFF`) on a cool off-white canvas (`#F8F8FA`) and high-contrast periwinkle (`#5E63E8`) primary triggers. Following a strict "less is more" engineering principle, every UI element directly serves an MVP requirement from the PRD without decorative filler. The application renders edge-to-edge across the full viewport on its canvas color, and every create, edit, view-detail, and delete/void confirmation opens as a centered modal dialog over the dimmed parent screen.

## Colors
- **Primary (`#5E63E8`)**: Used strictly for the single primary CTA button per screen, active sidebar item icon/label, active tab text and `2px` underline, primary chart line stroke, and focused input rings (`20%` opacity). Usage ratio: < 8% of viewport.
- **On-Primary (`#FFFFFF`)**: Text and icons placed directly inside `#5E63E8` buttons and active tooltips.
- **Secondary / Sidebar Surface (`#EFECE1`)**: Used exclusively as the full-height left navigation sidebar background. Never used for cards or modals.
- **Background / Canvas (`#F8F8FA`)**: Full-viewport page canvas behind the main content region.
- **Surface (`#FFFFFF`)**: Background for all KPI cards, charts, full-width tables, search bar, active sidebar navigation pill, secondary action buttons, and modal dialogs.
- **On-Surface (`#1E2029`)**: Primary ink for page titles, Rupiah currency figures, table values, and menu names. Secondary body copy uses `#5A5D6C`.
- **On-Surface-Muted (`#8E92A4`)**: Field labels, table column headers, timestamps, inactive tabs, and search placeholder text.
- **Outline (`#ECEEF2`)**: `1px` horizontal table row dividers, tab bar baselines, and form input borders inside modals.
- **Success (`#2EB872`)**: Positive growth indicators, AI connected status badges, matched menu items, and positive stock movements (`purchase`, `void_return`).
- **Error (`#E5484D`)**: Destructive actions (`Void`, `Hapus`), negative trends, critical/negative stock warnings, unrecognized AI items, and failed connection alerts.
- **Data Visualization & Icon Badge Accents**: Sky Blue (`#64CCF3` / badge `#E3F5FC` for GoFood / online channel), Mint Green (`#A6E3B8` for offline POS), Warm Peach (`#F4A261` / badge `#FDEEE4` for ShopeeFood / expenses), and Sunny Yellow (`#F3D36C` for pending confirmation / warnings).
- **Scrim (`#1E2029` at 45% opacity)**: Full-viewport overlay behind centered modal dialogs (`rgba(30, 32, 41, 0.45)`).

## Typography
- **Font Family**: `Plus Jakarta Sans` (Google Fonts), weights `400`, `500`, `600`, `700`, `800`. All numeric currency and stock columns use `font-variant-numeric: tabular-nums`.
- **Headline-LG (`24px`, weight `700`, line-height `1.25`, tracking `-0.01em`)**: Screen title in the page header row.
- **KPI Number (`20px–22px`, weight `700`, line-height `1.2`, tracking `-0.01em`)**: Rupiah figures (`Rp12.500.000`) and stock counts inside summary cards.
- **Title-MD (`15px`, weight `600`, line-height `1.35`, tracking `-0.005em`)**: Card titles, modal dialog titles, and section headers.
- **Label-MD (`13px`, weight `600`, line-height `1.25`)**: Buttons, active navigation items, active tabs, and form input labels.
- **Body-MD (`13px`, weight `500`, line-height `1.5`)**: Table cell content, menu item rows, form inputs, and transaction list items.
- **Caption (`11px–12px`, weight `500`, line-height `1.35`, tracking `0.01em`)**: Timestamps (`04 Nov 2026, 14:30`), unit badges (`gram`, `ml`, `pcs`), and chart axis labels.
- **Case Rules**: Sentence case for all headings, labels, buttons, and tabs. Never use all-caps except for acronyms (`HPP`, `POS`, `AI`, `QRIS`, `CSV`).

## Layout
- **Viewport Shell**: Full-viewport layout (`min-h-screen flex`) with zero outer frame or margin.
- **Sidebar Navigation**: Fixed `232px` width on desktop (`>= 1024px`), sticky full-height (`100vh`), `20px` horizontal padding, `28px` vertical padding. Collapses to an icon-only rail (`72px`) on tablet and a slide-over drawer on mobile (`< 768px`).
- **Content Area**: Fluid `1fr` width, `28px 36px` padding on desktop (`20px` on mobile/tablet).
- **Grid System**: 12-column CSS Grid with `20px` gap (`16px` gap for inner 3-card KPI rows).
  - **Dashboard Layout**: 8 columns left (3 KPI cards + main trend chart + top menus) and 4 columns right (channel split donut + critical stock / pending online orders).
  - **POS Kasir Layout**: 8 columns left (category tabs + 3-column menu card grid) and 4 columns right (sticky active cart panel).
  - **List & Table Screens (`Riwayat Transaksi`, `Menu`, `Kategori`, `Bahan Baku`, `Pembelian`, `Stok Opname`)**: Use the full 12-column content width (`100%`). No side panels, split views, or drawers beside tables.

## Elevation & Depth
- **Level 0 (Canvas & Sidebar)**: Flat (`#F8F8FA` and `#EFECE1`), separated by tonal contrast without a vertical border line.
- **Level 1 (Cards, Tables, Search Input, Top Buttons)**: `0 2px 10px rgba(30, 32, 41, 0.03)` on `#FFFFFF` surface without hard outer borders.
- **Level 2 (Active Sidebar Item & Chart Floating Badges)**: `0 2px 6px rgba(30, 32, 41, 0.04)`.
- **Level 3 (Centered Modal Dialogs & Toast Notifications)**: Highest elevation in the system (`0 20px 40px rgba(30, 32, 41, 0.16)`) layered above the `#1E2029` (45% opacity) scrim.

## Shapes
- **Cards, Full-Width Table Containers, and Modal Dialogs**: `16px` (`{rounded.lg}`).
- **Buttons, Form Inputs, Selects, Sidebar Nav Items, and Icon Badges**: `10px` (`{rounded.md}`).
- **Status Badges & Inline Tags**: `6px` (`{rounded.sm}`).
- **Avatars, Notification Bell Button, and Transaction List Dots**: `9999px` (`{rounded.full}`).
- **Borders**: `1px solid #ECEEF2` for internal table row dividers, tab bars, and form inputs inside white modal surfaces.
- **Icons**: `18px` for sidebar navigation, `16px` for buttons/inputs/table actions, `1.8px–2px` stroke width with rounded caps.

## Components
- **Sidebar Nav Item**: Full-width button (`height: 44px`, `padding: 12px 16px`, `rounded: 10px`, gap `12px`). Default: `#5A5D6C` text/icon on transparent bg. Active: `#FFFFFF` bg, `#5E63E8` text/icon, weight `600`, shadow `0 2px 6px rgba(30, 32, 41, 0.04)`.
- **Buttons**: Minimum touch target `40px–44px` height, `padding: 10px 16px`, `rounded: 10px`, `13px` weight `600`, optional `16px` leading icon.
  - **Primary (`button-primary`)**: `#5E63E8` bg, `#FFFFFF` text. Restricted to one main action per screen or modal footer.
  - **Secondary (`button-secondary`)**: `#FFFFFF` bg, `#1E2029` text, border `#ECEEF2` inside modals or `shadow-card` on canvas.
  - **Danger (`button-danger`)**: `#E5484D` bg, `#FFFFFF` text. Used only in destructive confirmation modals (`Void`, `Hapus`).
- **Inputs, Selects & Comboboxes**: `height: 42px`, `padding: 10px 14px`, `rounded: 10px`, `#FFFFFF` bg, `1px solid #ECEEF2` border (`#5E63E8` ring on focus). Inline validation errors appear `4px` below in `11px` `#E5484D`.
- **KPI Summary Card**: `#FFFFFF` bg, `rounded: 16px`, `padding: 20px`. Top row: `36px x 36px` pastel icon badge (`rounded: 10px`) on left, trend/status pill on right. Bottom: `12px` muted label (`#8E92A4`) above `20px` bold metric (`#1E2029`).
- **Data Table**: Full-width `#FFFFFF` container, `rounded: 16px`, `padding: 20px 24px`. Includes a top filter bar (search input + filter dropdowns) and server-side pagination footer. Column headers in `12px` `#8E92A4` weight `600`; rows separated by `1px solid #ECEEF2`; numeric/Rupiah columns right-aligned (`text-right`).
- **Tabs**: Horizontal text buttons (`12px–13px`, gap `24px`) along a `1px solid #ECEEF2` bottom line. Active tab has `#5E63E8` text, weight `600`, and a `2px` `#5E63E8` bottom border.
- **Modal Dialogs (Mandatory for all CRUD, Detail, and Confirmation flows)**:
  - **Position**: Horizontally and vertically centered (`fixed inset-0 flex items-center justify-center z-50`) over the dimmed parent screen; the parent screen remains visible behind the `#1E2029` (45% opacity) scrim.
  - **Widths**:
    - **Confirm Dialog (`confirm-dialog`)**: `400px` max-width (used for Delete Menu, Delete Category, Delete API Key, and Void Transaction confirmations).
    - **Form Dialog (`modal-dialog`)**: `520px` max-width (used for Add/Edit Menu, Category, Ingredient, Purchase, Stock Opname, Manual Online Order, Cashier Account, and Custom Date Filter).
    - **Detail / Split-Verification Dialog**: `640px` max-width (`760px` for AI Screenshot side-by-side image & extraction verification and Recipe/HPP editor), with `max-h-[85vh]` and internal body scroll when needed.
  - **Anatomy**:
    - **Header**: Modal title (`15px` weight `600` `#1E2029`) on the left + `X` close icon button (`#8E92A4`) on the right, separated by `16px` bottom margin.
    - **Body**: Vertically stacked form fields (`16px` gap) with `12px` `#5A5D6C` labels above each field, or structured read-only detail rows.
    - **Footer**: Right-aligned button group (`gap: 10px`, top margin `24px`): secondary `Batal` button followed by the primary action (`Simpan`, `Konfirmasi`) or destructive action (`Void Transaksi`, `Hapus`).

## Do's and Don'ts
- **Do** use only tokens defined in this file; **Don't** introduce new colors, fonts, radii, or shadows.
- **Do** keep one primary action per screen; **Don't** add decorative sections, banners, fake stats, or filler text.
- **Do** reuse listed components for new screens; **Don't** invent new component styles.
- **Do** render the app edge-to-edge on the canvas color (`#F8F8FA`); **Don't** add a backdrop, gradient wallpaper, window frame, device frame, showcase watermark, or floating decorative cards around the app.
- **Do** open every create/edit/detail/delete interaction as a centered modal dialog over the dimmed parent screen; **Don't** place detail or form cards beside the table, in a side panel, drawer, split view, or on a separate page.
- **Do** let tables use the full content width.
- **Do** maintain WCAG AA contrast; **Don't** use color alone to convey status (always pair status colors with clear Indonesian labels or icons).
- **Do** format all Indonesian Rupiah amounts as integer strings with dot thousand separators (`Rp12.500`) right-aligned in tables; **Don't** display decimal cents on currency or use float formatting.
- **Do** keep the `#EFECE1` warm sand background strictly for the left navigation sidebar; **Don't** use `#EFECE1` as a card background or table header fill.
- **Do** provide a clean empty state (one concise Indonesian sentence + one primary button) when tables have zero records; **Don't** populate screens with dummy menus or fake transactions.
- **Do** hide HPP, recipe cost, profit reports, and settings from the `Kasir` role; **Don't** expose owner-only cost columns in cashier views.

## Screen Inventory

### S1: Masuk (Login)
- **Platform**: Desktop web (`1440px` canvas)
- **PRD features**: F1 (Autentikasi)
- **Purpose**: Authenticate `Pemilik` or `Kasir` with rate-limiting protection.
- **Layout regions (top→bottom)**: Full-viewport `#F8F8FA` canvas with a single centered `#FFFFFF` login card (`420px` width, `rounded: 16px`, `padding: 32px`).
- **Components used**: `card`, `input`, `button-primary`.
- **Exact content/copy**: Brand header `POS Kuliner`, subtitle `Masuk ke akun outlet Anda`, fields: `Email atau Username`, `Password`, error banner slot `Kombinasi akun atau password tidak sesuai`, button `Masuk`.
- **Primary action**: `Masuk` (`button-primary`, full width).
- **Modals opened from this screen**: None.
- **Required states**: Inline validation error, rate-limit temporary lock alert (`Terlalu banyak percobaan login. Coba lagi dalam 60 detik.`).
- **Explicitly excluded**: Social login buttons, self-registration link, marketing illustration, forgot-password email flow.

### S2: Dashboard
- **Platform**: Desktop web
- **PRD features**: F2 (Dashboard Pemilik)
- **Purpose**: Give the store owner a real-time overview of daily sales, channel split, gross profit estimate, critical stock, and AI queue status.
- **Layout regions (top→bottom)**: Left sidebar (`232px`, `Dashboard` active) + Main content: Top header (`Cari transaksi atau menu...`, status badge `AI Terhubung`, avatar) → Title row (`Dashboard`, period selector `Hari Ini / 7 Hari / Bulan Ini`, secondary button `Rentang Kustom`, primary button `+ Transaksi Baru`) → 12-column grid (Left 8 cols: 3 KPI cards + Area chart `Tren Penjualan & Estimasi Laba Kotor` + Card `5 Menu Terlaris`; Right 4 cols: Donut card `Pembagian Kanal Penjualan` + List card `Bahan Stok Kritis & Pesanan Menunggu`).
- **Components used**: `card`, `button-primary`, `button-secondary`, `input`, `tabs`, `badge`, `modal-dialog`.
- **Exact content/copy**: KPI 1: `Penjualan Hari Ini` (`Rp0` / `0 Transaksi`), KPI 2: `Estimasi Laba Kotor` (`Rp0` + badge `HPP Lengkap`), KPI 3: `Pesanan Online Menunggu` (`0 Pesanan` + `Status AI: Terhubung`), Chart tabs: `Omzet`, `Laba Kotor`, `Jumlah Transaksi`, Donut legend: `Offline`, `GoFood`, `ShopeeFood`, Right list tabs: `Stok Kritis`, `Menunggu Konfirmasi`.
- **Primary action**: `+ Transaksi Baru` (navigates to `Kasir`).
- **Modals opened from this screen**:
  - **M1: Filter Periode Kustom (`modal-dialog`, `520px`)**: Triggered by `Rentang Kustom`. Title: `Pilih Rentang Tanggal`. Fields: `Tanggal Mulai` (date input), `Tanggal Selesai` (date input). Footer: `Batal`, `Terapkan Filter`.
- **Required states**: Skeleton loading per independent card, empty state when zero transactions exist (`Belum ada transaksi pada periode ini`).
- **Explicitly excluded**: Multi-outlet comparison, customer demographics, marketing banners.

### S3: Kasir (POS Offline)
- **Platform**: Desktop web (responsive 2-panel tablet, bottom-sheet cart on mobile)
- **PRD features**: F3 (Kasir / POS Offline)
- **Purpose**: Complete offline walk-in orders in under 15 seconds with automatic stock deduction.
- **Layout regions (top→bottom)**: Left sidebar (`Kasir` active) + Top header (search input auto-focused with shortcut hint `/ Cari menu...`) → Title row (`Kasir POS`, transaction number badge `TRX-20261105-0001`) → 12-column workspace: Left 8 cols (Category tabs `Semua`, `Makanan`, `Minuman`, `Paket` + 3-column grid of clickable menu cards showing menu name and Rupiah price) and Right 4 cols (Sticky `Keranjang Aktif` card with item list, `-/+` qty steppers, per-item note input, transaction note, subtotal, discount row, grand total, and `Bayar (F2)` primary button).
- **Components used**: `card`, `tabs`, `input`, `button-primary`, `button-secondary`, `badge`, `modal-dialog`.
- **Exact content/copy**: Cart header `Keranjang Aktif`, empty cart text `Belum ada item dipilih. Klik menu di kiri atau tekan / untuk mencari.`, summary rows `Subtotal`, `Diskon`, `Total Tagihan`, button `Bayar (F2)`.
- **Primary action**: `Bayar (F2)` (`button-primary`).
- **Modals opened from this screen**:
  - **M2: Pembayaran & Konfirmasi Transaksi (`modal-dialog`, `520px`)**: Triggered by `Bayar (F2)`. Title: `Selesaikan Pembayaran`. Fields: `Metode Pembayaran` (tabs: `Tunai`, `QRIS`, `Transfer`), `Tipe Diskon` (`Nominal Rp` / `Persen %`), `Nilai Diskon`, `Alasan Diskon` (required if discount > 0), `Uang Diterima` (with quick nominal chips `Uang Pas`, `Rp50.000`, `Rp100.000`), read-only `Kembalian`. Footer: `Batal`, `Simpan & Cetak Struk`.
  - **M3: Struk Transaksi (`modal-dialog`, `400px`)**: Opens immediately after saving transaction. Title: `Struk Transaksi`. Body: Monospace/clean receipt preview (`58mm/80mm` print layout) with store name, `TRX-20261105-0001`, timestamp, item list, subtotal, discount, total, paid amount, change, and footer note. Footer: `Tutup (Esc)`, `Cetak Struk`.
- **Required states**: Disabled `Simpan` button during submission (idempotency protection), empty state when no menus exist.
- **Explicitly excluded**: Customer loyalty points, split-bill by seat, kitchen display routing.

### S4: Pesanan Online (AI Screenshot & Input Manual)
- **Platform**: Desktop web
- **PRD features**: F4 (AI Screenshot Pesanan Hybrid), F5 (Pesanan Online Manual), F9 (Pencegahan Transaksi Ganda)
- **Purpose**: Import GoFood/ShopeeFood orders from screenshots via hybrid AI/local OCR or manual fallback without duplicates.
- **Layout regions (top→bottom)**: Left sidebar (`Pesanan Online` active) + Top header → Title row (`Pesanan Online`, secondary button `+ Input Manual`, primary button `+ Unggah Screenshot`) → Status filter tabs (`Semua`, `Menunggu`, `Membaca`, `Perlu Dicek`, `Siap Disimpan`, `Duplikat`, `Gagal`) → Full-width 12-column table/grid of screenshot import queue cards showing file thumbnail, platform badge (`GoFood`/`ShopeeFood`), external order ID, source badge (`Dibaca oleh: AI` or `Dibaca oleh: Mesin lokal`), status badge, and action button `Periksa & Konfirmasi`.
- **Components used**: `card`, `table`, `tabs`, `badge`, `button-primary`, `button-secondary`, `input`, `select`, `modal-dialog`.
- **Exact content/copy**: Queue columns: `Waktu Unggah`, `Kanal`, `Nomor Pesanan`, `Sumber Baca`, `Skor Keyakinan`, `Status`, `Aksi`.
- **Primary action**: `+ Unggah Screenshot` (`button-primary`).
- **Modals opened from this screen**:
  - **M4: Unggah Screenshot Pesanan (`modal-dialog`, `520px`)**: Triggered by `+ Unggah Screenshot`. Title: `Unggah Screenshot Pesanan Online`. Body: Drag-and-drop / `Ctrl+V` paste dropzone (`Format JPG, PNG, WEBP · Maks 8 MB per file`), selected files list with SHA-256 duplicate check status. Footer: `Batal`, `Proses Screenshot`.
  - **M5: Konfirmasi Bacaan Pesanan (`modal-dialog`, `760px`)**: Triggered by `Periksa & Konfirmasi`. Title: `Konfirmasi Pesanan Online`. Body split inside modal: Left half shows zoomable screenshot preview; Right half shows editable extraction fields: badge `Dibaca oleh: AI (Gemini) / Mesin lokal`, `Platform` (`GoFood` / `ShopeeFood`), `Nomor Pesanan Eksternal`, `Waktu Pesanan`, item rows (raw extracted name, matched menu combobox, warning highlight if unrecognized, checkbox `Simpan sebagai alias`, `Qty`, `Harga Menu`), read-only `Subtotal Menu`, `Penyesuaian Platform`, and editable `Total Platform`. Duplicate warning alert if timestamp+total matches an existing order. Footer: `Batal`, `Simpan Pesanan`.
  - **M6: Tambah Pesanan Online Manual (`modal-dialog`, `520px`)**: Triggered by `+ Input Manual`. Title: `Input Pesanan Online Manual`. Fields: `Kanal` (`GoFood` / `ShopeeFood`), `Nomor Pesanan Eksternal`, `Waktu Pesanan`, repeatable `Item Menu` (combobox + `Qty`), `Total dari Platform (Rp)`, read-only calculated `Penyesuaian Platform (Rp)`, `Catatan`. Footer: `Batal`, `Simpan Pesanan`.
- **Required states**: Polling state (`Membaca...`), duplicate file hash rejection message with link to existing transaction.
- **Explicitly excluded**: Customer name or delivery address fields (strictly forbidden by PRD privacy rules).

### S5: Riwayat Transaksi
- **Platform**: Desktop web
- **PRD features**: F6 (Riwayat Transaksi & Pembatalan/Void)
- **Purpose**: Search, inspect, and void transactions with automatic stock return and audit logging.
- **Layout regions (top→bottom)**: Left sidebar (`Riwayat Transaksi` active) + Top header → Title row (`Riwayat Transaksi`) → Full-width table card with filter bar (`Tanggal`, `Kanal: Semua/Offline/GoFood/ShopeeFood`, `Status: Semua/Selesai/Void`, search `Cari nomor transaksi...`) and paginated table.
- **Components used**: `card`, `table`, `input`, `select`, `badge`, `button-secondary`, `button-danger`, `modal-dialog`, `confirm-dialog`.
- **Exact content/copy**: Table columns: `Nomor Transaksi`, `Waktu`, `Kanal`, `Nomor Eksternal`, `Metode Bayar`, `Total (Rp)`, `Status`, `Kasir`, `Aksi` (`Detail`, `Void`).
- **Primary action**: `Lihat Detail` per row (read-only inspection screen).
- **Modals opened from this screen**:
  - **M7: Detail Transaksi (`modal-dialog`, `640px`)**: Triggered by `Detail`. Title: `Detail Transaksi #TRX-20261105-0001`. Body: Read-only metadata grid (`Waktu`, `Kanal`, `Nomor Eksternal`, `Sumber`, `Kasir`, `Status`), item table (`Menu`, `Qty`, `Harga Satuan`, `Subtotal`, plus `HPP Snapshot` visible only to `Pemilik`), summary totals (`Subtotal`, `Diskon`, `Penyesuaian Platform`, `Total`), and void metadata if voided. Footer: `Tutup`, `Cetak Ulang Struk`, `Void Transaksi` (if active).
  - **M8: Batalkan (Void) Transaksi? (`confirm-dialog`, `400px`)**: Triggered by `Void`. Title: `Batalkan Transaksi Ini?`. Body: Warning `Stok bahan baku akan dikembalikan otomatis (void_return) dan transaksi tidak dihitung di omzet.`, mandatory field `Alasan Pembatalan (Wajib)`. Footer: `Batal`, `Void Transaksi` (`button-danger`).
- **Required states**: Empty table state, voided row badge (`Void` in `#E5484D`).
- **Explicitly excluded**: Hard delete button for transactions, editing items of a completed transaction.

### S6: Menu & Resep
- **Platform**: Desktop web (`Pemilik` only)
- **PRD features**: F7 (Menu, Kategori & Alias), F8 (Resep & HPP)
- **Purpose**: Manage single-price culinary menus, platform aliases for AI matching, and portion recipes for automated HPP calculation.
- **Layout regions (top→bottom)**: Left sidebar (`Menu` active) + Top header → Title row (`Menu & HPP`, secondary button `Kelola Kategori`, primary button `+ Tambah Menu`) → Full-width table card with filter bar (category select, recipe status filter, search input) and paginated menu table.
- **Components used**: `card`, `table`, `input`, `select`, `badge`, `button-primary`, `button-secondary`, `button-danger`, `modal-dialog`, `confirm-dialog`.
- **Exact content/copy**: Table columns: `Nama Menu`, `Kategori`, `Alias Platform`, `Harga Jual (Rp)`, `HPP per Porsi (Rp)`, `Margin (Rp / %)`, `Status Resep` (`Lengkap` / `Resep belum lengkap`), `Status` (`Aktif` / `Nonaktif`), `Aksi` (`Edit`, `Resep & HPP`, `Hapus`).
- **Primary action**: `+ Tambah Menu` (`button-primary`).
- **Modals opened from this screen**:
  - **M9: Tambah / Edit Menu & Alias (`modal-dialog`, `520px`)**: Triggered by `+ Tambah Menu` or `Edit`. Title: `Tambah Menu` / `Edit Menu`. Fields: `Nama Menu`, `Kategori` (select), `Harga Jual (Rp)`, `Daftar Alias GoFood/ShopeeFood` (tag/chip input), `Foto Menu (Opsional)`, `Catatan`, toggle `Status Aktif`. Footer: `Batal`, `Simpan Menu`.
  - **M10: Atur Resep & HPP Menu (`modal-dialog`, `640px`)**: Triggered by `Resep & HPP`. Title: `Resep & HPP: <Nama Menu>`. Body: Repeatable ingredient rows (`Bahan Baku` combobox, `Jumlah per Porsi` in base unit, read-only `Biaya Bahan`), repeatable extra cost rows (`Label Biaya Tambahan` e.g. Kemasan, `Nominal Rp`), live summary bar (`Total HPP`, `Harga Jual`, `Estimasi Margin Rp & %`). Footer: `Batal`, `Simpan Resep`.
  - **M11: Hapus Menu? (`confirm-dialog`, `400px`)**: Triggered by `Hapus` (only enabled if menu has zero transactions; otherwise `Nonaktifkan`). Title: `Hapus Menu Permanen?`. Body: `Menu yang belum memiliki riwayat transaksi akan dihapus permanen.` Footer: `Batal`, `Hapus Menu` (`button-danger`).
- **Required states**: `Resep belum lengkap` warning badge in `#F4A261` when recipe rows are empty.
- **Explicitly excluded**: Multi-channel price columns (GoFood/ShopeeFood separate prices), nested add-on/variant trees.

### S7: Kategori Menu
- **Platform**: Desktop web (`Pemilik` only)
- **PRD features**: F7 (Menu & Kategori)
- **Purpose**: Organize menu categories and their display order on the POS screen.
- **Layout regions (top→bottom)**: Left sidebar (`Kategori` active) + Top header → Title row (`Kategori Menu`, primary button `+ Tambah Kategori`) → Full-width table card.
- **Components used**: `card`, `table`, `input`, `button-primary`, `button-secondary`, `button-danger`, `modal-dialog`, `confirm-dialog`.
- **Exact content/copy**: Table columns: `Urutan`, `Nama Kategori`, `Jumlah Menu`, `Aksi` (`Edit`, `Hapus`).
- **Primary action**: `+ Tambah Kategori` (`button-primary`).
- **Modals opened from this screen**:
  - **M12: Tambah / Edit Kategori (`modal-dialog`, `520px`)**: Title: `Tambah Kategori` / `Edit Kategori`. Fields: `Nama Kategori`, `Urutan Tampil`. Footer: `Batal`, `Simpan Kategori`.
  - **M13: Hapus Kategori? (`confirm-dialog`, `400px`)**: Title: `Hapus Kategori?`. Body: `Kategori kosong ini akan dihapus permanen.` Footer: `Batal`, `Hapus Kategori` (`button-danger`).
- **Required states**: Empty state (`Belum ada kategori menu`).
- **Explicitly excluded**: Category images, nested subcategories.

### S8: Bahan Baku
- **Platform**: Desktop web (`Pemilik` full CRUD; `Kasir` read-only stock view without cost columns)
- **PRD features**: F9 (Bahan Baku & Stok)
- **Purpose**: Track raw ingredient stock in base units, unit conversion factors, weighted average cost, and stock movement history.
- **Layout regions (top→bottom)**: Left sidebar (`Bahan Baku` active) + Top header → Title row (`Bahan Baku & Stok`, secondary buttons `Catat Pembelian` & `Stok Opname`, primary button `+ Tambah Bahan`) → Full-width table card with filter (`Semua`, `Stok Kritis`, `Stok Negatif`, `Aktif/Nonaktif`).
- **Components used**: `card`, `table`, `input`, `select`, `badge`, `button-primary`, `button-secondary`, `modal-dialog`.
- **Exact content/copy**: Table columns: `Nama Bahan`, `Satuan Dasar` (`gram`/`ml`/`pcs`), `Satuan Beli & Konversi`, `Stok Saat Ini`, `Stok Minimum`, `Harga Rata-rata / Satuan Dasar (Rp)` (hidden from `Kasir`), `Status Stok` (`Aman`, `Menipis`, `Negatif`), `Aksi` (`Edit`, `Riwayat Stok`).
- **Primary action**: `+ Tambah Bahan` (`button-primary`).
- **Modals opened from this screen**:
  - **M14: Tambah / Edit Bahan Baku (`modal-dialog`, `520px`)**: Title: `Tambah Bahan Baku` / `Edit Bahan Baku`. Fields: `Nama Bahan`, `Satuan Dasar` (`gram`, `ml`, `pcs`), `Satuan Beli` (e.g. `kg`, `liter`, `dus`), `Faktor Konversi ke Satuan Dasar` (e.g. `1000`), `Batas Stok Minimum`, toggle `Status Aktif`. Footer: `Batal`, `Simpan Bahan`.
  - **M15: Riwayat Pergerakan Stok (`modal-dialog`, `640px`)**: Triggered by `Riwayat Stok`. Title: `Kartu Stok: <Nama Bahan>`. Body: Read-only movement ledger table (`Waktu`, `Jenis`: `purchase`/`sale_usage`/`void_return`/`adjustment`/`waste`, `Perubahan Qty`, `Harga Satuan`, `Referensi`, `Alasan/Catatan`, `Pengguna`). Footer: `Tutup`.
- **Required states**: Red badge for `Stok ≤ Minimum` or negative stock.
- **Explicitly excluded**: Direct manual editing of `current_stock` without creating a `stock_movements` record.

### S9: Pembelian Bahan
- **Platform**: Desktop web (`Pemilik` only)
- **PRD features**: F9 (Pembelian Bahan & Harga Rata-Rata Tertimbang)
- **Purpose**: Record raw ingredient purchases to increase stock and automatically recalculate weighted-average cost per base unit.
- **Layout regions (top→bottom)**: Left sidebar (`Pembelian` active) + Top header → Title row (`Pembelian Bahan Baku`, primary button `+ Catat Pembelian`) → Full-width table card with date range and ingredient filter.
- **Components used**: `card`, `table`, `input`, `select`, `button-primary`, `button-secondary`, `modal-dialog`.
- **Exact content/copy**: Table columns: `Tanggal Beli`, `Nama Bahan`, `Jumlah (Satuan Beli)`, `Jumlah (Satuan Dasar)`, `Total Harga Dibayar (Rp)`, `Harga per Satuan Dasar (Rp)`, `Catatan`.
- **Primary action**: `+ Catat Pembelian` (`button-primary`).
- **Modals opened from this screen**:
  - **M16: Catat Pembelian Bahan (`modal-dialog`, `520px`)**: Title: `Catat Pembelian Bahan Baku`. Fields: `Bahan Baku` (combobox), `Tanggal Pembelian`, `Jumlah dalam Satuan Beli`, read-only preview `Konversi Satuan Dasar`, `Total Harga Dibayar (Rp)`, read-only preview `Harga Rata-Rata Tertimbang Baru`, `Catatan`. Footer: `Batal`, `Simpan Pembelian`.
- **Required states**: Empty state (`Belum ada catatan pembelian bahan`).
- **Explicitly excluded**: Supplier invoicing/accounts payable ledger, multi-installment credit tracking.

### S10: Stok Opname
- **Platform**: Desktop web (`Pemilik` only)
- **PRD features**: F9 (Stok Opname & Penyesuaian)
- **Purpose**: Reconcile physical kitchen stock against system stock and log mandatory reasons for adjustments or waste.
- **Layout regions (top→bottom)**: Left sidebar (`Stok Opname` active) + Top header → Title row (`Stok Opname & Penyesuaian`, primary button `+ Input Stok Opname`) → Full-width table card of adjustment history.
- **Components used**: `card`, `table`, `input`, `select`, `badge`, `button-primary`, `button-secondary`, `modal-dialog`.
- **Exact content/copy**: Table columns: `Waktu`, `Nama Bahan`, `Jenis` (`Adjustment` / `Waste`), `Stok Sistem Sebelumnya`, `Stok Fisik Aktual`, `Selisih`, `Alasan Wajib`, `Petugas`.
- **Primary action**: `+ Input Stok Opname` (`button-primary`).
- **Modals opened from this screen**:
  - **M17: Input Stok Opname Fisik (`modal-dialog`, `520px`)**: Title: `Input Stok Opname / Koreksi Stok`. Fields: `Bahan Baku` (combobox), `Jenis Koreksi` (`Stok Opname / Adjustment` or `Bahan Terbuang / Waste`), read-only `Stok Sistem Saat Ini`, `Stok Fisik Aktual (Satuan Dasar)`, read-only calculated `Selisih Qty`, `Alasan Koreksi (Wajib)`. Footer: `Batal`, `Simpan Koreksi Stok`.
- **Required states**: Empty state (`Belum ada riwayat stok opname`).
- **Explicitly excluded**: Barcode scanner hardware integration.

### S11: Laporan
- **Platform**: Desktop web (`Pemilik` only)
- **PRD features**: F10 (Laporan Penjualan, HPP, Laba Kotor & Ekspor CSV)
- **Purpose**: Analyze verified non-void revenue, COGS (HPP), estimated gross profit, ingredient consumption, and current inventory valuation.
- **Layout regions (top→bottom)**: Left sidebar (`Laporan` active) + Top header → Title row (`Laporan Penjualan & Laba`, period selector `Hari Ini / Minggu Ini / Bulan Ini / Kustom`, primary button `Ekspor CSV`) → 3 KPI summary cards (`Total Omzet Bersih`, `Total HPP Terpakai`, `Estimasi Laba Kotor & Margin %` with warning badge if any sold menu had incomplete recipe) → Report category tabs (`Per Periode`, `Per Kanal`, `Per Kategori`, `Per Menu`, `Pemakaian Bahan & Nilai Stok`) → Full-width aggregated SQL report table.
- **Components used**: `card`, `tabs`, `table`, `badge`, `button-primary`, `button-secondary`.
- **Exact content/copy**: Table columns (for `Per Menu`): `Nama Menu`, `Kategori`, `Terjual (Qty)`, `Omzet (Rp)`, `HPP Total (Rp)`, `Estimasi Laba Kotor (Rp)`, `Margin (%)`, `Status HPP`.
- **Primary action**: `Ekspor CSV` (`button-primary`).
- **Modals opened from this screen**: Reuses **M1 (`Filter Periode Kustom`)** when `Kustom` period is clicked.
- **Required states**: Incomplete recipe indicator (`HPP tidak lengkap pada X transaksi`).
- **Explicitly excluded**: Tax filing forms, payroll accounting, balance sheet depreciation.

### S12: Pengaturan
- **Platform**: Desktop web (`Pemilik` only)
- **PRD features**: F11 (Pengaturan Profil Toko, Keamanan & Kasir, Integrasi AI, Cadangan Data)
- **Purpose**: Configure outlet identity, account security, cashier users, encrypted hybrid AI credentials with connection/image testing, and database backups.
- **Layout regions (top→bottom)**: Left sidebar (`Pengaturan` active) + Top header → Title row (`Pengaturan Outlet`) → Horizontal tabs (`Profil Toko`, `Keamanan & Kasir`, `Integrasi AI`, `Cadangan Data`) → Full-width settings card content per active tab:
  - Tab 1 (`Profil Toko`): `Nama Toko`, `Alamat`, `Nomor Telepon`, `Catatan Kaki Struk`, `Komisi Estimasi GoFood (%)`, `Komisi Estimasi ShopeeFood (%)`, primary button `Simpan Perubahan`.
  - Tab 2 (`Keamanan & Kasir`): Password change form (`Password Lama`, `Password Baru min. 10 karakter`, `Konfirmasi Password Baru`, strength indicator, checkbox `Keluarkan semua perangkat lain`) + full-width table `Daftar Akun Kasir` (`Nama`, `Email`, `Login Terakhir`, `Status`, `Aksi`) with `+ Tambah Kasir` button.
  - Tab 3 (`Integrasi AI`): Toggle `Aktifkan Ekstraksi AI`, `Penyedia AI` (`Anthropic`, `Google Gemini`, `OpenAI`, `OpenRouter`, `Custom OpenAI Compatible`), `Base URL Kustom` (when Custom selected), masked `API Key` (`sk-••••••a1b2`) with `Hapus Key` button, `Model AI` (combobox/input), `Batas Anggaran Bulanan (Rp)`, `Ambang Keyakinan (%)`, `Masa Simpan Screenshot (Hari, default 30)`, toggle `Simpan Otomatis (Default Mati)`, privacy notice `Gambar pesanan dikirim ke penyedia AI yang dipilih; nama dan alamat pelanggan tidak disimpan.`, status badge (`Belum diatur` / `Terhubung` / `Gagal`), buttons `Tes Koneksi`, `Tes Baca Gambar`, and primary `Simpan Pengaturan AI`.
  - Tab 4 (`Cadangan Data`): Status card `Backup Terakhir`, retention info (`14 hari`), and primary button `Ekspor Database Sekarang (.sql)`.
- **Components used**: `card`, `tabs`, `input`, `select`, `table`, `badge`, `button-primary`, `button-secondary`, `button-danger`, `modal-dialog`, `confirm-dialog`.
- **Primary action**: `Simpan Pengaturan` (`button-primary` per active tab).
- **Modals opened from this screen**:
  - **M18: Tambah / Edit Akun Kasir (`modal-dialog`, `520px`)**: Triggered from `Keamanan & Kasir` tab. Title: `Tambah Akun Kasir` / `Edit Akun Kasir`. Fields: `Nama Lengkap`, `Email / Username`, `Password Baru (min. 10 karakter)`, checkbox `Izinkan Kasir Memberi Diskon`, toggle `Status Aktif`. Footer: `Batal`, `Simpan Kasir`.
  - **M19: Tes Baca Gambar AI (`modal-dialog`, `640px`)**: Triggered by `Tes Baca Gambar` in `Integrasi AI` tab. Title: `Uji Ekstraksi Screenshot (Tanpa Simpan Transaksi)`. Body: Sample file upload + read-only JSON/structured extraction preview showing `Sumber Baca`, `Latency (ms)`, `Platform`, `Nomor Pesanan`, `Daftar Item & Skor Kecocokan`, `Total`. Footer: `Tutup`, `Jalankan Tes`.
  - **M20: Hapus API Key? (`confirm-dialog`, `400px`)**: Triggered by `Hapus Key`. Title: `Hapus API Key Tersimpan?`. Body: `API key akan dihapus dari penyimpanan terenkripsi dan sistem otomatis beralih ke Mesin Lokal (OCR).` Footer: `Batal`, `Hapus Key` (`button-danger`).
- **Required states**: Connection test status states (`Belum diatur` gray, `Terhubung` green with latency & model name, `Gagal` red with human-readable reason).
- **Explicitly excluded**: Exposing raw saved API keys in browser DOM or logs.