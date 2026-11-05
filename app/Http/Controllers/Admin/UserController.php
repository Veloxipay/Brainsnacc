<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $all = User::with(['level', 'supervisors', 'students'])
            ->withMax('attempts', 'created_at')
            ->withCount('completedLessons')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'users' => $all,
            'counts' => $all->countBy('role'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'levels' => Level::orderBy('sort')->get(),
            'parents' => User::where('role', User::ROLE_PARENT)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_PARENT, User::ROLE_TEACHER])],
            'level_id' => ['nullable', 'required_if:role,student', 'exists:levels,id'],
            'parent_id' => ['nullable', Rule::exists('users', 'id')->where('role', User::ROLE_PARENT)],
        ]);

        if ($data['role'] !== User::ROLE_STUDENT) {
            $data['level_id'] = null;
            $data['parent_id'] = null;
        }

        $user = User::create($data);
        if (! empty($data['parent_id'])) {
            $user->supervisors()->syncWithoutDetaching([$data['parent_id']]);
        }

        return redirect()->route('admin.users.index')->with('status', 'Account created for '.$data['name'].'.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'levels' => Level::orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username'))) ?: null]);
        $isMe = $user->is($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => User::usernameRules($user->id),
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => [$isMe ? 'prohibited' : 'required', Rule::in([User::ROLE_STUDENT, User::ROLE_PARENT, User::ROLE_TEACHER, User::ROLE_ADMIN])],
            'level_id' => ['nullable', 'exists:levels,id'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'username.unique' => 'That username is taken.',
            'username.regex' => 'Usernames can only use lower-case letters, numbers, dots, dashes and underscores.',
            'role.prohibited' => 'You can’t change your own role.',
        ]);

        if (empty($data['username']) && empty($data['email'])) {
            return back()->withErrors(['username' => 'Keep a username or an email so they can log in.'])->withInput();
        }
        if (! $isMe) {
            $user->role = $data['role'];
        }
        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'] ?: null;
        $user->level_id = $user->role === User::ROLE_STUDENT ? ($data['level_id'] ?? null) : null;

        $temporary = null;
        if ($request->boolean('temporary')) {
            $temporary = \Illuminate\Support\Str::password(12, symbols: false);
            $user->password = $temporary;
        } elseif (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $status = "Saved {$user->name}'s account.";
        if ($temporary) {
            $status .= " Their temporary password is {$temporary} — give it to them and ask them to change it under My account. It won’t be shown again.";
        } elseif (! empty($data['password'])) {
            $status .= ' Their new password is set.';
        }

        return redirect()->route('admin.users.edit', $user)->with('status', $status);
    }
}
