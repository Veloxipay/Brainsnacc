<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Every signed-in person can change their own login email and password (current password required). */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['username' => strtolower(trim((string) $request->input('username'))) ?: null]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => \App\Models\User::usernameRules($user->id),
            'email' => [$user->isStudent() ? 'nullable' : 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is not right.',
            'username.unique' => 'That username is taken.',
            'username.regex' => 'Usernames can only use lower-case letters, numbers, dots, dashes and underscores.',
        ]);

        if (empty($data['username']) && empty($data['email'])) {
            return back()->withErrors(['username' => 'Keep a username or an email so you can log in.']);
        }
        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'] ?: null;
        if (! empty($data['password'])) {
            $user->password = $data['password']; // hashed by the model's cast
        }
        $user->save();

        return redirect()->route('account')->with('status', 'Your account is updated.'.(! empty($data['password']) ? ' Use your new password next time you log in.' : ''));
    }
}
