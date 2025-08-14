<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingBot;
use App\Models\FuturesTradingBot;

class CheckBots extends Command
{
    protected $signature = 'check:bots';
    protected $description = 'List all trading bots and their status';

    public function handle()
    {
        $this->info('=== Regular Trading Bots ===');
        $regularBots = TradingBot::all(['id', 'name', 'exchange', 'symbol', 'is_active']);
        
        if ($regularBots->count() > 0) {
            foreach ($regularBots as $bot) {
                $status = $bot->is_active ? 'Active' : 'Inactive';
                $this->line("ID {$bot->id}: {$bot->name} ({$bot->exchange} - {$bot->symbol}) - {$status}");
            }
        } else {
            $this->line('No regular trading bots found.');
        }

        $this->info('\n=== Futures Trading Bots ===');
        $futuresBots = FuturesTradingBot::all(['id', 'name', 'exchange', 'symbol', 'is_active']);
        
        if ($futuresBots->count() > 0) {
            foreach ($futuresBots as $bot) {
                $status = $bot->is_active ? 'Active' : 'Inactive';
                $this->line("ID {$bot->id}: {$bot->name} ({$bot->exchange} - {$bot->symbol}) - {$status}");
            }
        } else {
            $this->line('No futures trading bots found.');
        }
    }
}
