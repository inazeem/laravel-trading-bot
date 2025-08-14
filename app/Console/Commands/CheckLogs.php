<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingBotLog;

class CheckLogs extends Command
{
    protected $signature = 'check:logs {bot_id} {--type=regular}';
    protected $description = 'Check latest logs for a specific bot';

    public function handle()
    {
        $botId = $this->argument('bot_id');
        $type = $this->option('type');
        
        if ($type === 'futures') {
            $logs = TradingBotLog::where('futures_trading_bot_id', $botId)
                ->orderBy('logged_at', 'desc')
                ->limit(15)
                ->get();
        } else {
            $logs = TradingBotLog::where('trading_bot_id', $botId)
                ->orderBy('logged_at', 'desc')
                ->limit(15)
                ->get();
        }
        
        $this->info("Latest logs for bot ID {$botId} ({$type}):");
        $this->line('');
        
        foreach ($logs as $log) {
            $time = $log->logged_at->format('H:i:s');
            $level = strtoupper($log->level);
            $category = $log->category ?? 'general';
            $message = $log->message;
            
            $this->line("{$time} [{$level}] {$category}: {$message}");
        }
    }
}
