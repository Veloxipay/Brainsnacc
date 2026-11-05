<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Get back into the admin account (e.g. after forgetting the password).
 * Run in the Laravel Cloud console:  php artisan brainsnacc:admin
 * It shows the admin accounts, then asks for the login email and a new password
 * (typed by you, hidden, never stored in the code).
 */
Artisan::command('brainsnacc:admin', function () {
    $admins = App\Models\User::where('role', App\Models\User::ROLE_ADMIN)->orderBy('id')->get(['id', 'name', 'username', 'email']);
    if ($admins->isEmpty()) {
        $this->warn('There is no admin account yet. One will be created.');
    } else {
        $this->info('Admin accounts:');
        foreach ($admins as $a) {
            $this->line("  #{$a->id}  {$a->name}  ·  email: ".($a->email ?: '—').'  ·  username: '.($a->username ?: '—'));
        }
    }

    $email = trim((string) $this->ask('Login email for the admin account', $admins->first()?->email));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->error('That is not a valid email. Nothing was changed.');
    }
    $password = (string) $this->secret('New password (at least 10 characters, letters and numbers; hidden as you type)');
    if (strlen($password) < 10 || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/\d/', $password)) {
        return $this->error('Password too short or missing letters/numbers. Nothing was changed.');
    }
    if ($password !== (string) $this->secret('Type the new password again')) {
        return $this->error('The two passwords did not match. Nothing was changed.');
    }

    // Use the admin with that email, otherwise the first admin (its email is updated).
    $user = $admins->firstWhere('email', $email) ?? $admins->first() ?? new App\Models\User(['name' => 'Admin']);
    if (App\Models\User::where('email', $email)->where('id', '!=', $user->id ?? 0)->exists()) {
        return $this->error('A student, parent or teacher account already uses that email. Choose a different email. Nothing was changed.');
    }
    $user->email = $email;
    $user->password = $password; // hashed by the model
    $user->role = App\Models\User::ROLE_ADMIN;
    $user->save();

    $this->info("Done. Log in at /login with {$email} and your new password.");
})->purpose('Show the admin login and set a new admin password');
