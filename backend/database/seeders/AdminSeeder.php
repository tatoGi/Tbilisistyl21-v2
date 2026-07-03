<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate (not updateOrCreate): re-seeding must NEVER overwrite an
        // existing admin's password. Set ADMIN_PASSWORD in .env for the real
        // credential; the 'secret' fallback only seeds a brand-new local install.
        $user = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@tbilisistyle.ge')],
            [
                'name' => 'Admin',
                // Plain text — User model `hashed` cast handles bcrypt.
                'password' => env('ADMIN_PASSWORD', 'secret'),
            ]
        );

        // role is guarded from mass assignment; set explicitly in seeder.
        if ($user->role !== 'admin') {
            $user->forceFill(['role' => 'admin'])->save();
        }
    }
}
