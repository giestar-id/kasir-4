<?php

namespace App\Livewire\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public string $identifier = '';

    public string $password = '';

    public bool $remember = true;

    public string $errorMessage = '';

    protected function rules(): array
    {
        return [
            'identifier' => 'required|string',
            'password' => 'required|string|min:6',
        ];
    }

    protected function messages(): array
    {
        return [
            'identifier.required' => 'Email atau username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ];
    }

    public function login(): void
    {
        $this->errorMessage = '';
        $this->validate();

        $throttleKey = Str::transliterate(Str::lower($this->identifier).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->errorMessage = "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.";
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Akses Dibatasi',
                'message' => $this->errorMessage,
            ]);

            return;
        }

        // Check if user exists by email or name/username
        $user = User::where('email', $this->identifier)
            ->orWhere('name', $this->identifier)
            ->first();

        if (! $user || ! Auth::attempt(['email' => $user->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            $this->errorMessage = 'Kombinasi akun atau password tidak sesuai.';
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Login Gagal',
                'message' => $this->errorMessage,
            ]);

            return;
        }

        if (! $user->is_active) {
            Auth::logout();
            $this->errorMessage = 'Akun Anda dinonaktifkan oleh pemilik resto.';
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Akun Nonaktif',
                'message' => $this->errorMessage,
            ]);

            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $user->last_login_at = Carbon::now();
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'meta' => ['ip' => request()->ip()],
            'ip' => request()->ip(),
        ]);

        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil Masuk',
            'message' => 'Selamat datang kembali, '.$user->name.'!',
        ]);

        if ($user->isOwner()) {
            $this->redirectIntended(default: route('dashboard'), navigate: false);

            return;
        }

        $this->redirect(route('pos'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest');
    }
}
