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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is not right.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password']; // hashed by the model's cast
        }
        $user->save();

        return redirect()->route('account')->with('status', 'Your account is updated.'.(! empty($data['password']) ? ' Use your new password next time you log in.' : ''));
    }
}
