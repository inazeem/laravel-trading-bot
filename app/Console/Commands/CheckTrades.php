<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuturesTrade;
use App\Models\Trade;

class CheckTrades extends Command
{
    protected $signature = 'check:trades {bot_id} {--type=regular}';
    protected $description = 'Check trades for a specific bot';

    public function handle()
    {
        $botId = $this->argument('bot_id');
        $type = $this->option('type');
        
        if ($type === 'futures') {
            $trades = FuturesTrade::where('futures_trading_bot_id', $botId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } else {
            $trades = Trade::where('trading_bot_id', $botId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }
        
        $this->info("Latest trades for bot ID {$botId} ({$type}):");
        $this->line('');
        
        if ($trades->count() > 0) {
            foreach ($trades as $trade) {
                $this->line("ID: {$trade->id}");
                $this->line("Symbol: {$trade->symbol}");
                $this->line("Side: {$trade->side}");
                $this->line("Quantity: {$trade->quantity}");
                $this->line("Entry Price: {$trade->entry_price}");
                $this->line("Status: {$trade->status}");
                $this->line("Created: {$trade->created_at->format('Y-m-d H:i:s')}");
                $this->line("Order ID: {$trade->order_id}");
                $this->line("---");
            }
        } else {
            $this->line("No trades found for this bot.");
        }
    }
}
