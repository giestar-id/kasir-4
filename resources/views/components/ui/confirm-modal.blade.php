@props([
    'name',
    'title' => 'Konfirmasi Tindakan',
    'message' => 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    'confirmText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'variant' => 'danger',
    'action' => null,
    'icon' => 'alert-triangle',
])

<div
    x-data="{ show: false, reason: '' }"
    x-init="
        $watch('show', value => {
            if (value) {
                document.body.classList.add('overflow-hidden');
                setTimeout(() => window.refreshIcons && window.refreshIcons(), 50);
            } else {
                document.body.classList.remove('overflow-hidden');
            }
        })
    "
    x-on:open-confirm-{{ $name }}.window="show = true; reason = ''"
    x-on:close-confirm-{{ $name }}.window="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center"
    style="display: none;"
>
    <!-- Scrim -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 glass-scrim"
        @click="show = false"
    ></div>

    <!-- Centered Card -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-[420px] glass-modal rounded-2xl shadow-2xl p-5 sm:p-6 z-10 flex flex-col"
    >
        <div class="flex items-start gap-4 mb-4">
            <div class="w-10 h-10 rounded-xl {{ $variant === 'danger' ? 'bg-[#E5484D]/15 text-[#E5484D]' : 'bg-[#5E63E8]/15 text-[#5E63E8]' }} flex items-center justify-center shrink-0">
                <x-ui.icon :name="$icon" class="w-5 h-5" />
            </div>
            <div>
                <h3 class="text-base font-bold text-[#1E2029] tracking-tight leading-snug">{{ $title }}</h3>
                <p class="text-xs text-[#5A5D6C] mt-1 leading-relaxed">{{ $message }}</p>
            </div>
        </div>

        {{ $slot }}

        <div class="mt-6 flex items-center justify-end gap-2.5">
            <x-ui.button variant="secondary" size="md" @click="show = false">
                {{ $cancelText }}
            </x-ui.button>
            
            <x-ui.button
                :variant="$variant"
                size="md"
                {{ $attributes }}
                @click="show = false"
            >
                {{ $confirmText }}
            </x-ui.button>
        </div>
    </div>
</div>

