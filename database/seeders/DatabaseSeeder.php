<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CourseSeeder::class);
        $this->call(LessonContentSeeder::class);

        // The first admin account comes from environment settings,
        // so no password is ever stored in the code.
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        // Only when there is no admin yet, so changing the admin's email later never creates a second admin.
        if ($email && $password && ! User::where('role', User::ROLE_ADMIN)->exists()) {
            User::firstOrCreate(
                ['email' => $email],
                ['name' => env('SEED_ADMIN_NAME', 'Admin'), 'password' => $password, 'role' => User::ROLE_ADMIN],
            );
        }
    }
}
