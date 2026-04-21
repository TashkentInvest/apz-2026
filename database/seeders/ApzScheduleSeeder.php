<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ApzScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Importing APZ schedules from grafik_apz.csv...');
        Artisan::call('apz:import-contracts');
        $this->command->line(Artisan::output());
        $this->command->info('APZ schedules import completed.');
    }
}
