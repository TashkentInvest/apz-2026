<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn('Force seeding mode: existing APZ data will be deleted.');
        $this->truncateApzTables();

        $this->call([
            ApzPaymentsSeeder::class,
            ApzScheduleSeeder::class,
        ]);

        $this->command->info('APZ database seeding completed (payments and schedules).');
    }

    private function truncateApzTables(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            DB::table('apz_payments')->truncate();
            DB::table('apz_contracts')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
