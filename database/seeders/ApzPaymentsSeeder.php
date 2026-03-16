<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ApzPaymentsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Importing APZ payments from fakt_apz.csv...');
        Artisan::call('apz:import-payments', ['--fresh' => true]);
        $this->command->line(Artisan::output());
        $this->command->info('APZ payments import completed.');
    }
}
