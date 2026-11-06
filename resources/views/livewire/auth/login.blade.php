<div class="w-full max-w-[420px] flex flex-col items-center">
    <!-- Centered Glass Login Card -->
    <div class="relative w-full glass-modal rounded-2xl shadow-2xl p-7 sm:p-8 flex flex-col">
        
        <!-- Brand & Header -->
        <div class="flex flex-col items-center text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#5E63E8]/10 text-[#5E63E8] mb-3 shadow-xs">
                <x-ui.icon name="utensils" class="w-6 h-6" />
            </div>
            <div class="flex items-center justify-center gap-1.5">
                <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">POS Kuliner</h1>
                <span class="w-2 h-2 rounded-full bg-[#5E63E8] inline-block"></span>
            </div>
            <p class="text-xs sm:text-sm text-[#4E5262] mt-1">
                Masuk ke akun outlet Anda
            </p>
        </div>

        @if($errorMessage)
            <div class="mb-4 p-3 rounded-xl bg-[#E5484D]/10 border border-[#E5484D]/20 flex items-start gap-2.5 text-xs text-[#E5484D]">
                <x-ui.icon name="alert-circle" class="w-4 h-4 shrink-0 mt-0.5" />
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        <!-- Form -->
        <form wire:submit.prevent="login" class="flex flex-col gap-4">
            <x-ui.input
                label="Email atau Username"
                wire:model="identifier"
                icon="user"
                placeholder="owner@outlet.id atau kasir@outlet.id"
                :error="$errors->first('identifier')"
                required
            />

            <div x-data="{ showPass: false }" class="flex flex-col gap-1.5 w-full">
                <label for="password" class="flex items-center justify-between text-xs font-semibold text-[#5A5D6C]">
                    <span>Password <span class="text-[#E5484D]">*</span></span>
                </label>
                <div class="relative flex items-center w-full">
                    <div class="absolute left-3 text-[#4E5262] pointer-events-none flex items-center justify-center">
                        <x-ui.icon name="lock" class="w-4 h-4" />
                    </div>
                    <input
                        id="password"
                        :type="showPass ? 'text' : 'password'"
                        wire:model="password"
                        placeholder="••••••••"
                        class="w-full bg-white/90 text-[#1E2029] text-xs sm:text-sm rounded-xl py-2.5 pl-9 pr-10 border border-[#ECEEF2] shadow-2xs placeholder:text-[#4E5262]/60 focus:bg-white focus:outline-none focus:ring-3 focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] transition-all"
                        required
                    />
                    <button
                        type="button"
                        @click="showPass = !showPass"
                        class="absolute right-2.5 text-[#4E5262] hover:text-[#1E2029] p-1 rounded-lg transition-colors cursor-pointer"
                        title="Tampilkan sandi"
                    >
                        <span x-show="!showPass" class="flex items-center justify-center">
                            <x-ui.icon name="eye" class="w-4 h-4" />
                        </span>
                        <span x-show="showPass" x-cloak class="flex items-center justify-center">
                            <x-ui.icon name="eye-off" class="w-4 h-4" />
                        </span>
                    </button>
                </div>
                @error('password')
                    <p class="text-[11px] font-medium text-[#E5484D] mt-0.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Sesi -->
            <div class="flex items-center gap-2 pt-1">
                <label class="flex items-center gap-2 text-xs text-[#5A5D6C] cursor-pointer select-none">
                    <input
                        type="checkbox"
                        wire:model="remember"
                        class="w-4 h-4 rounded text-[#5E63E8] focus:ring-0 accent-[#5E63E8] cursor-pointer"
                    />
                    <span>Ingat sesi masuk saya</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full mt-2 bg-[#5E63E8] hover:bg-[#4D52D6] active:bg-[#4448CE] text-white font-semibold text-sm py-3 px-4 rounded-xl shadow-md transition-all flex items-center justify-center gap-2 group cursor-pointer disabled:opacity-50"
            >
                <span wire:loading.remove>Masuk ke POS</span>
                <span wire:loading>Memverifikasi...</span>
                <x-ui.icon name="arrow-right" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </button>
        </form>
    </div>

    <!-- Help note -->
    <div class="mt-4 flex items-center gap-1.5 text-xs text-[#4E5262]">
        <x-ui.icon name="help-circle" class="w-3.5 h-3.5" />
        <span>Kendala akses? Gunakan akun default <b>owner@outlet.id</b> (pass: password123)</span>
    </div>
</div>

