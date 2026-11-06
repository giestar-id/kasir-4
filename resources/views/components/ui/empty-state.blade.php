@props([
    'icon' => 'inbox',
    'title' => 'Belum ada data',
    'description' => 'Mulai tambahkan data pertama Anda menggunakan tombol di bawah.',
])

<div class="py-12 px-4 flex flex-col items-center justify-center text-center">
    <div class="w-14 h-14 rounded-2xl bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center mb-3.5 shadow-xs">
        <x-ui.icon :name="$icon" class="w-7 h-7" />
    </div>
    <h3 class="text-sm sm:text-base font-bold text-[#1E2029] tracking-tight">{{ $title }}</h3>
    <p class="text-xs text-[#8E92A4] max-w-sm mt-1 leading-relaxed">{{ $description }}</p>

    @if($slot->isNotEmpty())
        <div class="mt-4 flex items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>

