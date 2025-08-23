<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuturesTradingBot;
use App\Services\TradingLearningService;

class AnalyzeTradingPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:trading-performance {--bot-id= : Specific bot ID to analyze} {--apply-learning : Apply learning to bot configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze trading performance and learn from patterns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🧠 Starting trading performance analysis...");
        
        try {
            // Get bots to analyze
            $botId = $this->option('bot-id');
            if ($botId) {
                $bots = FuturesTradingBot::where('id', $botId)->get();
            } else {
                $bots = FuturesTradingBot::all();
            }
            
            if ($bots->isEmpty()) {
                $this->error("No bots found to analyze.");
                return 1;
            }
            
            $this->info("Found " . $bots->count() . " bot(s) to analyze.");
            
            foreach ($bots as $bot) {
                $this->info("\n📊 Analyzing bot: {$bot->name} ({$bot->symbol})");
                
                $learningService = new TradingLearningService($bot);
                
                // Get learning summary
                $summary = $learningService->getLearningSummary();
                
                if (isset($summary['message'])) {
                    $this->warn("⚠️ {$summary['message']}");
                    continue;
                }
                
                // Display summary
                $this->info("📈 Trading Summary:");
                $this->info("   Total Trades: {$summary['total_trades']}");
                $this->info("   Winning Trades: {$summary['winning_trades']}");
                $this->info("   Win Rate: {$summary['win_rate']}%");
                $this->info("   Total PnL: {$summary['total_pnl']}");
                $this->info("   Avg PnL per Trade: {$summary['avg_pnl_per_trade']}");
                $this->info("   Learning Status: {$summary['learning_status']}");
                
                // Run full analysis if we have enough data
                if ($summary['total_trades'] >= 5) {
                    $this->info("\n🔍 Running detailed analysis...");
                    $analysis = $learningService->analyzeAndLearn();
                    
                    // Display best performing signals
                    if (!empty($analysis['best_signal_types'])) {
                        $this->info("\n📊 Best Performing Signal Types:");
                        foreach (array_slice($analysis['best_signal_types'], 0, 3) as $signal) {
                            $this->info("   {$signal['signal_type']}: {$signal['win_rate']}% win rate, {$signal['avg_pnl']} avg PnL ({$signal['total_trades']} trades)");
                        }
                    }
                    
                    // Display best performing timeframes
                    if (!empty($analysis['best_timeframes'])) {
                        $this->info("\n⏰ Best Performing Timeframes:");
                        foreach (array_slice($analysis['best_timeframes'], 0, 3) as $timeframe) {
                            $this->info("   {$timeframe['timeframe']}: {$timeframe['win_rate']}% win rate, {$timeframe['avg_pnl']} avg PnL ({$timeframe['total_trades']} trades)");
                        }
                    }
                    
                    // Display market conditions
                    if (!empty($analysis['best_market_conditions']['time_of_day']['best_hours'])) {
                        $bestHours = implode(', ', $analysis['best_market_conditions']['time_of_day']['best_hours']);
                        $this->info("\n🕐 Best Trading Hours: {$bestHours}");
                    }
                    
                    if (!empty($analysis['best_market_conditions']['time_of_day']['worst_hours'])) {
                        $worstHours = implode(', ', $analysis['best_market_conditions']['time_of_day']['worst_hours']);
                        $this->info("🕐 Worst Trading Hours: {$worstHours}");
                    }
                    
                    // Display risk adjustments
                    if (!empty($analysis['risk_adjustments'])) {
                        $this->info("\n⚙️ Risk Adjustments:");
                        foreach ($analysis['risk_adjustments'] as $key => $value) {
                            if (is_string($value)) {
                                $this->info("   {$key}: {$value}");
                            }
                        }
                    }
                    
                    // Display recommendations
                    if (!empty($analysis['recommendations'])) {
                        $this->info("\n💡 Recommendations:");
                        foreach ($analysis['recommendations'] as $recommendation) {
                            $this->info("   • {$recommendation}");
                        }
                    }
                    
                    // Apply learning if requested
                    if ($this->option('apply-learning')) {
                        $this->info("\n🔄 Applying learning to bot configuration...");
                        // The learning is already applied in the analyzeAndLearn method
                        $this->info("✅ Learning applied successfully!");
                    }
                    
                } else {
                    $this->warn("⚠️ Need at least 5 trades for detailed analysis (current: {$summary['total_trades']})");
                }
            }
            
            $this->info("\n✅ Trading performance analysis completed!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error during analysis: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
