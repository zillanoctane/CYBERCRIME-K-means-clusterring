<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Kredensial yang diberikan tidak cocok.'])->onlyInput('email');
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun Anda tidak aktif.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('auth.login');

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'instansi' => ['nullable', 'string', 'max:160'],
            'role' => ['required', 'in:admin,analis,viewer'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'role.required' => 'Silakan pilih peran akun.',
            'role.in' => 'Peran yang dipilih tidak valid.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'instansi' => $data['instansi'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);
        ActivityLog::record('auth.register', $user, ['role' => $user->role]);

        return redirect()->route('dashboard')->with('status',
            'Pendaftaran berhasil! Selamat datang, '.$user->name.'. Anda masuk sebagai '.$user->roleLabel().'.'
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        ActivityLog::record('auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
