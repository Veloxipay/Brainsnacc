<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Get back into the admin account (e.g. after forgetting the password).
 * Run in the Laravel Cloud console:  php artisan brainsnacc:admin
 * It shows the admin accounts, asks for the login email, and prints a one-off temporary
 * password (never stored in the code). Change it straight away under My account.
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
    // The web console can't take hidden typing, so make a one-off temporary password instead.
    $password = Illuminate\Support\Str::password(14, symbols: false);

    // Use the admin with that email, otherwise the first admin (its email is updated).
    $user = $admins->firstWhere('email', $email) ?? $admins->first() ?? new App\Models\User(['name' => 'Admin']);
    if (App\Models\User::where('email', $email)->where('id', '!=', $user->id ?? 0)->exists()) {
        return $this->error('A student, parent or teacher account already uses that email. Choose a different email. Nothing was changed.');
    }
    $user->email = $email;
    $user->password = $password; // hashed by the model
    $user->role = App\Models\User::ROLE_ADMIN;
    $user->save();

    $this->newLine();
    $this->info('Done. Log in at https://brainsnacc.com.au/login with:');
    $this->line("   Email:              {$email}");
    $this->line("   Temporary password: {$password}");
    $this->warn('Straight after logging in, open "My account" and set your own password. Then clear this console.');
})->purpose('Show the admin login and give it a temporary password');
