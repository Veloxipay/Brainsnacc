<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Public sign-up for parents, teachers and students. */
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register', ['levels' => Level::orderBy('sort')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Simple bot trap: real people never fill in the hidden "website" field.
        if (filled($request->input('website'))) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_PARENT, User::ROLE_TEACHER, User::ROLE_STUDENT])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'level_id' => ['nullable', 'required_if:role,student', 'exists:levels,id'],
        ], [
            'email.unique' => 'There is already an account with that email. Try logging in instead.',
            'level_id.required_if' => 'Choose your year level.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'level_id' => $data['role'] === User::ROLE_STUDENT ? $data['level_id'] : null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', $user->isSupervisor()
            ? 'Welcome to Brainsnacc! Add your first student to get started.'
            : 'Welcome to Brainsnacc! Pick a subject in the side panel to start.');
    }
}
