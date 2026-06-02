<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrator',  'email' => 'admin@siancek.test',   'role' => User::ROLE_ADMIN,  'instansi' => 'Direktorat Tindak Pidana Siber'],
            ['name' => 'Analis Data',    'email' => 'analis@siancek.test',  'role' => User::ROLE_ANALIS, 'instansi' => 'Bagian Analisis Kriminal'],
            ['name' => 'Pengamat Riset', 'email' => 'viewer@siancek.test',  'role' => User::ROLE_VIEWER, 'instansi' => 'Institusi Akademik'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], [
                'name' => $u['name'],
                'password' => Hash::make('password'),
                'role' => $u['role'],
                'instansi' => $u['instansi'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
