<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the local database: the admin panel user and the demo accounts.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
