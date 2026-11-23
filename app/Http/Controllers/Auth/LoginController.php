<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * The accounts created by DatabaseSeeder, one or more per role.
     * Every seeded user's password is "password".
     */
    private const DEMO_ACCOUNTS = [
        ['role' => 'Admin', 'name' => 'Admin', 'email' => 'admin@example.com'],
        ['role' => 'Lecturer', 'name' => 'Dr. Budi', 'email' => 'budi@example.com'],
        ['role' => 'Lecturer', 'name' => 'Dr. Sari', 'email' => 'sari@example.com'],
        ['role' => 'Student', 'name' => 'Raka', 'email' => 'raka@example.com'],
        ['role' => 'Student', 'name' => 'Dewi', 'email' => 'dewi@example.com'],
        ['role' => 'Student', 'name' => 'Andi', 'email' => 'andi@example.com'],
        ['role' => 'Student', 'name' => 'Putri', 'email' => 'putri@example.com'],
        ['role' => 'Student', 'name' => 'Fajar', 'email' => 'fajar@example.com'],
    ];

    public function create(): View
    {
        // Demo credentials are a convenience for local and CI environments only.
        return view('auth.login', [
            'demoAccounts' => app()->isProduction() ? [] : self::DEMO_ACCOUNTS,
            'demoPassword' => 'password',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        // A new session id after login prevents session fixation.
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
