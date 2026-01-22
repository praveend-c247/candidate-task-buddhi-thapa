<?php

namespace App\Console\Commands;

use Database\Seeders\LargeDatasetSeeder;
use Illuminate\Console\Command;

class SeedLargeDataset extends Command
{
    protected $signature = 'db:seed-large {--count=100000 : Number of tasks to create}';

    protected $description = 'Seed database with large dataset (100k+ records) for performance testing';

    public function handle()
    {
        $count = (int) $this->option('count');
        
        if ($count < 1000) {
            $this->error('Minimum count is 1000. Use standard seeder for smaller datasets.');
            return 1;
        }

        $this->info("Starting large dataset seeding with {$count} tasks...");
        
        putenv("SEED_RECORD_COUNT={$count}");
        
        $seeder = new LargeDatasetSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        return 0;
    }
}
