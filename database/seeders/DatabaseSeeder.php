<?php

namespace Database\Seeders;

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
        $this->call([
            DepartmentsSeeder::class,
            LocationsSeeder::class,
            ItemCategoriesSeeder::class,
            UsersSeeder::class,
            ItemsSeeder::class,
            IncidentsSeeder::class,
            AuditLogsSeeder::class,
            AttachmentsSeeder::class,
            NotificationsSeeder::class,
        ]);
    }
}
