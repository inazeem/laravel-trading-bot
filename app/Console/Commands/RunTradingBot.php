<?php

namespace App\Console\Commands;

use App\Models\TradingBot;
use App\Services\TradingBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunTradingBot extends Command
{
    protected $signature = 'trading:run {bot_id?} {--all : Run all active bots}';
    protected $description = 'Run trading bot(s) with Smart Money Concepts strategy';

    public function handle()
    {
        if ($this->option('all')) {
            $this->runAllBots();
        } else {
            $botId = $this->argument('bot_id');
            if (!$botId) {
                $this->error('Please provide a bot ID or use --all option');
                return 1;
            }
            
            $this->runSingleBot($botId);
        }
        
        return 0;
    }

    private function runAllBots(): void
    {
        $bots = TradingBot::where('is_active', true)->get();
        
        if ($bots->isEmpty()) {
            $this->info('No active trading bots found.');
            return;
        }
        
        $this->info("Found {$bots->count()} active trading bot(s)");
        
        foreach ($bots as $bot) {
            $this->info("Running bot: {$bot->name}");
            $this->runSingleBot($bot->id);
        }
    }

    private function runSingleBot(int $botId): void
    {
        $bot = TradingBot::find($botId);
        
        if (!$bot) {
            $this->error("Trading bot with ID {$botId} not found.");
            return;
        }
        
        if (!$bot->is_active) {
            $this->warn("Bot {$bot->name} is not active. Skipping.");
            return;
        }
        
        try {
            $this->info("Starting bot: {$bot->name} ({$bot->exchange} - {$bot->symbol})");
            
            $botService = new TradingBotService($bot);
            $botService->run();
            
            $this->info("Bot {$bot->name} completed successfully.");
            
        } catch (\Exception $e) {
            $this->error("Error running bot {$bot->name}: " . $e->getMessage());
            Log::error("Trading bot error", [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
