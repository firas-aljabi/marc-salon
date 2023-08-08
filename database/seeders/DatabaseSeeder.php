<?php

namespace Database\Seeders;

use App\Statuses\UserType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory()->create([
            "name" => "Super Admin",
            "email" => "super_admin@admin.com",
            "password" => bcrypt('0123456789'),
            "type" => UserType::SUPER_ADMIN,
        ]);
    }
}