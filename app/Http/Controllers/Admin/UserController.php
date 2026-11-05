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
        return view('admin.users.index', [
            'users' => User::with(['level', 'parentUser'])->orderBy('role')->orderBy('name')->get(),
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
}
