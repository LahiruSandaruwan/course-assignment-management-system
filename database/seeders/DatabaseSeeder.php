<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->instructor()->create([
            'name' => 'Instructor User',
            'email' => 'instructor@example.com',
        ]);

        User::factory()->student()->create([
            'name' => 'Student User',
            'email' => 'student@example.com',
        ]);
    }
}
