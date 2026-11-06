@props([
    'name' => null,
    'title' => null,
    'size' => 'md', // sm (400px), md (520px), lg (640px), xl (760px), 2xl
    'maxWidth' => null,
    'footer' => null,
])

@php
$widthClasses = match($maxWidth ?? $size) {
    'sm' => 'max-w-[420px]',
    'lg' => 'max-w-[640px]',
    'xl' => 'max-w-[760px]',
    '2xl' => 'max-w-[920px]',
    default => 'max-w-[540px]',
};
@endphp

<div
    wire:ignore.self
    x-data="{
        show: false,
        isTarget(detail, target) {
            if (!detail) return false;
            if (detail === target) return true;
            if (Array.isArray(detail)) {
                if (detail.includes(target) || detail[0] === target) return true;
                if (typeof detail[0] === 'object' && (detail[0]?.name === target || detail[0]?.id === target)) return true;
            }
            if (typeof detail === 'object') {
                return detail.name === target || detail.id === target || detail[0] === target;
            }
            return false;
        }
    }"
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
    @if($name)
        x-on:open-modal.window="if (isTarget($event.detail, '{{ $name }}')) show = true"
        x-on:close-modal.window="if (!$event.detail || isTarget($event.detail, '{{ $name }}')) show = false"
        x-on:open-modal-{{ $name }}.window="show = true"
        x-on:close-modal-{{ $name }}.window="show = false"
    @endif
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-6 flex items-center justify-center"
>
    <!-- Scrim with blur -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display: none;"
        class="fixed inset-0 glass-scrim"
        @click="show = false"
    ></div>

    <!-- Centered Glass Modal Dialog -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        style="display: none;"
        class="relative w-full {{ $widthClasses }} glass-modal rounded-2xl shadow-2xl overflow-hidden my-auto z-10 flex flex-col max-h-[90vh]"
    >
        @if($title || isset($header))
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-[#ECEEF2]/80 flex items-center justify-between shrink-0">
                @if(isset($header))
                    {{ $header }}
                @else
                    <h3 class="text-sm sm:text-base font-bold text-[#1E2029] tracking-tight">{{ $title }}</h3>
                @endif
                <button
                    type="button"
                    @click="show = false"
                    class="p-1.5 rounded-lg text-[#8E92A4] hover:text-[#1E2029] hover:bg-black/5 transition-colors cursor-pointer"
                >
                    <x-ui.icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif

        <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto flex-1">
            {{ $slot }}
        </div>

        @if($footer || isset($footerSlot))
            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-[#F8F8FA]/70 border-t border-[#ECEEF2]/80 flex items-center justify-end gap-2.5 shrink-0">
                {{ $footer ?? $footerSlot }}
            </div>
        @endif
    </div>
</div>
