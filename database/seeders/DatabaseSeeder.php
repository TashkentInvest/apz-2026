<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ApzPaymentsSeeder::class,
            ApzScheduleSeeder::class,
        ]);

        $this->command->info('APZ database seeding completed (payments and schedules).');
    }
}
