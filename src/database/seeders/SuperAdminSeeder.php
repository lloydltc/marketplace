<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => config('app.super_admin_email')],
            [
                'name'     => 'Super Admin',
                'password' => config('app.super_admin_password'),
                'role'     => 'super_admin',
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['super_admin']);
    }
}
