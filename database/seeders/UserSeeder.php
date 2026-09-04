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
            [
                'name' => 'Ahmed',
                'email' => 'ahmed@example.com',
                'phone' => '01000000001',
            ],
            [
                'name' => 'Mohamed',
                'email' => 'mohamed@example.com',
                'phone' => '01000000002',
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
