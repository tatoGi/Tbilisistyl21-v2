<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@tbilisistyle.ge'],
            [
                'name' => 'Admin',
                // Plain text — User model `hashed` cast handles bcrypt.
                'password' => 'secret',
            ]
        );

        // role is guarded from mass assignment; set explicitly in seeder.
        if ($user->role !== 'admin') {
            $user->forceFill(['role' => 'admin'])->save();
        }
    }
}
