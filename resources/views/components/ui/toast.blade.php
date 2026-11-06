<div
    x-data="{
        toasts: [],
        add(rawToast) {
            let toast = rawToast;
            if (Array.isArray(rawToast)) {
                toast = rawToast[0] || {};
            }
            if (typeof toast !== 'object' || toast === null) {
                toast = { message: String(toast) };
            }
            const id = toast.id || Date.now() + Math.random();
            const type = toast.type || 'success';
            const defaultTitle = type === 'success' ? 'Berhasil' : (type === 'error' ? 'Gagal' : (type === 'warning' ? 'Peringatan' : 'Informasi'));

            const item = {
                id,
                message: toast.message || '',
                type: type,
                title: toast.title || defaultTitle,
                timeout: toast.timeout || 4500,
                show: false,
            };

            this.toasts.push(item);

            setTimeout(() => {
                const target = this.toasts.find(t => t.id === id);
                if (target) target.show = true;
            }, 25);

            setTimeout(() => {
                this.remove(id);
            }, item.timeout);
        },
        remove(id) {
            const target = this.toasts.find(t => t.id === id);
            if (target) {
                target.show = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 250);
            }
        }
    }"
    x-on:toast-message.window="add($event.detail)"
    x-on:toast.window="add($event.detail)"
    x-on:show-toast.window="add($event.detail)"
    class="fixed bottom-4 sm:bottom-5 right-4 left-4 sm:left-auto sm:right-5 z-[60] flex flex-col gap-2.5 max-w-sm pointer-events-none"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            x-show="t.show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto w-full glass-modal rounded-2xl shadow-2xl p-4 flex items-start gap-3 border border-white/80"
        >
            <div class="shrink-0 mt-0.5">
                <template x-if="t.type === 'success'">
                    <div class="w-8 h-8 rounded-xl bg-[#2EB872]/15 text-[#2EB872] flex items-center justify-center">
                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                </template>
                <template x-if="t.type === 'error'">
                    <div class="w-8 h-8 rounded-xl bg-[#E5484D]/15 text-[#E5484D] flex items-center justify-center">
                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                </template>
                <template x-if="t.type === 'warning'">
                    <div class="w-8 h-8 rounded-xl bg-[#F59E0B]/15 text-[#D97706] flex items-center justify-center">
                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                </template>
                <template x-if="t.type === 'info'">
                    <div class="w-8 h-8 rounded-xl bg-[#5E63E8]/15 text-[#5E63E8] flex items-center justify-center">
                        <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                </template>
            </div>

            <div class="flex-1 min-w-0 pt-0.5">
                <h4 class="text-xs font-bold text-[#1E2029]" x-text="t.title"></h4>
                <p class="text-xs text-[#5A5D6C] mt-0.5 leading-snug break-words" x-text="t.message"></p>
            </div>

            <button
                type="button"
                @click="remove(t.id)"
                class="shrink-0 text-[#4E5262] hover:text-[#1E2029] p-1 rounded-lg transition-colors cursor-pointer"
            >
                <svg class="w-4 h-4 stroke-current stroke-2 fill-none" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </template>
</div>
