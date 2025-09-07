<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\StrategyFactory;

class TradingStrategySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating system trading strategies...');
        
        try {
            StrategyFactory::createSystemStrategies();
            $this->command->info('✅ System trading strategies created successfully!');
        } catch (\Exception $e) {
            $this->command->error('❌ Failed to create system strategies: ' . $e->getMessage());
        }
    }
}
