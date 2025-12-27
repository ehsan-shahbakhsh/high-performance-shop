<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->create([
            'first_name' => 'Ehsan',
            'last_name' => 'Shahbakhsh',
            'email' => 'ehsan.shahbakhsh.email@gmail.com',
            'email_verified_at' => now(),
            'password' => '1234',
            'is_admin' => true,
        ]);
    }
}
