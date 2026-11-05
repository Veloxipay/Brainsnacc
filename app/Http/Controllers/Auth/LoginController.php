<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ], ['login.required' => 'Type your username or email.']);

        // Username (e.g. "mahek") or email both work.
        $login = trim($data['login']);
        $field = str_contains($login, '@') ? 'email' : 'username';
        $value = $field === 'username' ? strtolower($login) : $login;

        if (Auth::attempt([$field => $value, 'password' => $data['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        return back()
            ->withErrors(['login' => 'That username or email and password don’t match.'])
            ->onlyInput('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
