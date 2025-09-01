<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScalpingTradingBot;
use App\Services\ScalpingTradingBotService;
use Illuminate\Support\Facades\Log;

class RunScalpingBots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scalping:run {--bot-id= : Specific bot ID to run} {--all : Run all active bots}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scalping trading bots to analyze markets and execute trades';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting scalping bot execution...');

        $startTime = microtime(true);
        $botsRun = 0;
        $tradesExecuted = 0;
        $errors = 0;

        try {
            // Get bots to run
            if ($this->option('all')) {
                $bots = ScalpingTradingBot::where('is_active', true)
                    ->where('status', '!=', 'error')
                    ->where(function($query) {
                        $query->whereNull('last_run_at')
                            ->orWhere('last_run_at', '<=', now()->subSeconds(30));
                    })
                    ->get();
                    
                $this->info("Found {$bots->count()} active scalping bots to run");
            } elseif ($botId = $this->option('bot-id')) {
                $bots = ScalpingTradingBot::where('id', $botId)
                    ->where('is_active', true)
                    ->get();
                    
                if ($bots->isEmpty()) {
                    $this->error("No active scalping bot found with ID: {$botId}");
                    return 1;
                }
            } else {
                $this->error('Please specify --bot-id or --all option');
                return 1;
            }

            if ($bots->isEmpty()) {
                $this->info('✅ No scalping bots to run at this time');
                return 0;
            }

            // Run each bot
            foreach ($bots as $bot) {
                $this->info("⚡ Running scalping bot: {$bot->name} ({$bot->symbol})");
                
                try {
                    // Update bot status
                    $bot->update([
                        'status' => 'running',
                        'last_run_at' => now(),
                        'last_error' => null
                    ]);

                    // Check if bot can trade
                    if (!$bot->canTrade()) {
                        $this->info("⏸️ Bot {$bot->name} cannot trade at this time (cooldown/limits)");
                        $bot->update(['status' => 'idle']);
                        continue;
                    }

                    // Execute scalping strategy
                    $service = new ScalpingTradingBotService($bot);
                    $tradesBeforeRun = $bot->trades()->count();
                    
                    $service->executeScalpingStrategy();
                    
                    $tradesAfterRun = $bot->fresh()->trades()->count();
                    $newTrades = $tradesAfterRun - $tradesBeforeRun;
                    
                    if ($newTrades > 0) {
                        $this->info("✅ Bot {$bot->name}: {$newTrades} new trade(s) executed");
                        $tradesExecuted += $newTrades;
                    } else {
                        $this->info("📊 Bot {$bot->name}: Analysis completed, no trades executed");
                    }

                    // Update bot status
                    $bot->update([
                        'status' => 'idle',
                        'consecutive_losses' => $this->updateConsecutiveLosses($bot),
                    ]);

                    // Check if risk management should be paused
                    if ($bot->shouldPauseRiskManagement()) {
                        $bot->update([
                            'risk_management_paused' => true,
                            'status' => 'paused'
                        ]);
                        $this->warn("⚠️ Bot {$bot->name}: Risk management paused due to consecutive losses");
                    }

                    $botsRun++;

                } catch (\Exception $e) {
                    $errors++;
                    $errorMessage = "Error running scalping bot {$bot->name}: " . $e->getMessage();
                    
                    $this->error("❌ {$errorMessage}");
                    Log::error($errorMessage, [
                        'bot_id' => $bot->id,
                        'bot_name' => $bot->name,
                        'exception' => $e->getTraceAsString()
                    ]);

                    // Update bot with error status
                    $bot->update([
                        'status' => 'error',
                        'last_error' => $e->getMessage()
                    ]);
                }

                // Small delay between bots to prevent API rate limiting
                usleep(100000); // 0.1 second
            }

        } catch (\Exception $e) {
            $this->error("🚨 Critical error in scalping bot execution: " . $e->getMessage());
            Log::critical("Critical error in scalping bot execution", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        // Summary
        $this->info("📊 Scalping bot execution summary:");
        $this->info("   Bots run: {$botsRun}");
        $this->info("   Trades executed: {$tradesExecuted}");
        $this->info("   Errors: {$errors}");
        $this->info("   Execution time: {$executionTime}ms");

        // Log execution summary
        Log::info("Scalping bot execution completed", [
            'bots_run' => $botsRun,
            'trades_executed' => $tradesExecuted,
            'errors' => $errors,
            'execution_time_ms' => $executionTime
        ]);

        return 0;
    }

    /**
     * Update consecutive losses count for the bot
     */
    private function updateConsecutiveLosses(ScalpingTradingBot $bot): int
    {
        $recentTrades = $bot->trades()
            ->where('status', 'closed')
            ->orderBy('closed_at', 'desc')
            ->limit(10)
            ->get();

        $consecutiveLosses = 0;
        
        foreach ($recentTrades as $trade) {
            if ($trade->net_pnl < 0) {
                $consecutiveLosses++;
            } else {
                break; // Stop counting at first winning trade
            }
        }

        return $consecutiveLosses;
    }
}

