<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuturesTradingBot;
use App\Models\TradingBot;

class CheckApiKey extends Command
{
    protected $signature = 'check:apikey {bot_id} {--type=regular}';
    protected $description = 'Check API key details for a specific bot';

    public function handle()
    {
        $botId = $this->argument('bot_id');
        $type = $this->option('type');
        
        if ($type === 'futures') {
            $bot = FuturesTradingBot::find($botId);
        } else {
            $bot = TradingBot::find($botId);
        }
        
        if (!$bot) {
            $this->error("Bot not found!");
            return;
        }
        
        $this->info("Bot Details:");
        $this->line("Name: {$bot->name}");
        $this->line("Exchange: {$bot->exchange}");
        $this->line("Symbol: {$bot->symbol}");
        $this->line("API Key ID: {$bot->api_key_id}");
        
        $apiKey = $bot->apiKey;
        if ($apiKey) {
            $this->info("\nAPI Key Details:");
            $this->line("Name: {$apiKey->name}");
            $this->line("Exchange: {$apiKey->exchange}");
            $this->line("Is Active: " . ($apiKey->is_active ? 'Yes' : 'No'));
            $this->line("Permissions: " . json_encode($apiKey->permissions));
        } else {
            $this->error("API Key not found!");
        }
    }
}
