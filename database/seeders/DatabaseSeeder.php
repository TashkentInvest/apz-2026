<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Importing APZ payments from fakt-apz.csv...');
        Artisan::call('apz:import-payments', ['--fresh' => true]);
        $this->command->line(Artisan::output());

        $this->command->info('Importing APZ contracts from grafik_apz.csv...');
        Artisan::call('apz:import-contracts', ['--fresh' => true]);
        $this->command->line(Artisan::output());

        $this->command->info('APZ database seeding completed!');
    }
}
