<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StrategyFactory;
use App\Models\TradingStrategy;

class SetupTradingStrategies extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'trading:setup-strategies {--force : Force recreation of strategies}';

    /**
     * The console command description.
     */
    protected $description = 'Setup default trading strategies for the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up trading strategies...');
        
        // Check if strategies already exist
        $existingStrategies = TradingStrategy::system()->count();
        
        if ($existingStrategies > 0 && !$this->option('force')) {
            $this->warn("⚠️  {$existingStrategies} system strategies already exist.");
            
            if (!$this->confirm('Do you want to recreate them? This will delete existing system strategies.')) {
                $this->info('Operation cancelled.');
                return;
            }
            
            // Delete existing system strategies
            TradingStrategy::system()->delete();
            $this->info('🗑️  Deleted existing system strategies.');
        }
        
        try {
            // Create system strategies
            StrategyFactory::createSystemStrategies();
            
            $strategyCount = TradingStrategy::system()->count();
            $this->info("✅ Successfully created {$strategyCount} system strategies!");
            
            // Display created strategies
            $this->displayStrategies();
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to create strategies: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Display created strategies
     */
    private function displayStrategies()
    {
        $this->info("\n📋 Created Strategies:");
        $this->line('─────────────────────────────────────────────────────────────');
        
        $strategies = TradingStrategy::system()->with('parameters')->get();
        
        foreach ($strategies as $strategy) {
            $this->line("📊 <fg=cyan>{$strategy->name}</>");
            $this->line("   Type: {$strategy->type}");
            $this->line("   Market: {$strategy->market_type}");
            $this->line("   Timeframes: " . implode(', ', $strategy->supported_timeframes ?? []));
            $this->line("   Parameters: {$strategy->parameters->count()}");
            $this->line("   Description: {$strategy->description}");
            $this->line('');
        }
        
        $this->info('💡 You can now attach these strategies to your trading bots!');
        $this->line('   Use: php artisan trading:attach-strategy <bot_id> <strategy_id>');
    }
}
