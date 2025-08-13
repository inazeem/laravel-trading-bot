<?php

namespace App\Services;

use App\Models\FuturesTradingBot;
use App\Models\TradingBotLog;
use Illuminate\Support\Facades\Log;

class FuturesTradingBotLogger
{
    private FuturesTradingBot $futuresTradingBot;
    private array $context = [];

    public function __construct(FuturesTradingBot $futuresTradingBot)
    {
        $this->futuresTradingBot = $futuresTradingBot;
    }

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log a message with category
     */
    public function log(string $level, string $message, array $context = [], ?string $category = null): void
    {
        // Extract category from message if not provided
        if (!$category) {
            $category = $this->extractCategory($message);
        }

        // Store in database
        TradingBotLog::create([
            'futures_trading_bot_id' => $this->futuresTradingBot->id,
            'bot_type' => 'futures_trading_bot',
            'level' => $level,
            'category' => $category,
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'logged_at' => now(),
        ]);

        // Also log to Laravel's log system for backup
        Log::channel('daily')->log($level, "[FUTURES BOT: {$this->futuresTradingBot->name}] {$message}", $context);
    }

    /**
     * Extract category from log message
     */
    private function extractCategory(string $message): ?string
    {
        // Extract category from emoji patterns
        $patterns = [
            '🚀' => 'execution',
            '📊' => 'config',
            '⚙️' => 'config',
            '⏰' => 'config',
            '💰' => 'price',
            '✅' => 'success',
            '❌' => 'error',
            '🔍' => 'analysis',
            '📈' => 'candles',
            '🧠' => 'analysis',
            '📦' => 'analysis',
            '🕳️' => 'analysis',
            '⚖️' => 'analysis',
            '📋' => 'signals',
            '⚠️' => 'warning',
        ];

        foreach ($patterns as $emoji => $category) {
            if (str_contains($message, $emoji)) {
                return $category;
            }
        }

        // Extract from text patterns
        if (str_contains(strtolower($message), 'price')) return 'price';
        if (str_contains(strtolower($message), 'signal')) return 'signals';
        if (str_contains(strtolower($message), 'trade')) return 'execution';
        if (str_contains(strtolower($message), 'analysis')) return 'analysis';
        if (str_contains(strtolower($message), 'error')) return 'error';
        if (str_contains(strtolower($message), 'config')) return 'config';
        if (str_contains(strtolower($message), 'futures')) return 'futures';

        return 'general';
    }

    /**
     * Set context for subsequent log entries
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Add context for subsequent log entries
     */
    public function addContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * Clear context
     */
    public function clearContext(): void
    {
        $this->context = [];
    }

    /**
     * Get recent logs for this bot
     */
    public function getRecentLogs(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return $this->futuresTradingBot->logs()
            ->orderBy('logged_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get logs by level
     */
    public function getLogsByLevel(string $level, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return $this->futuresTradingBot->logs()
            ->byLevel($level)
            ->orderBy('logged_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get logs by category
     */
    public function getLogsByCategory(string $category, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return $this->futuresTradingBot->logs()
            ->byCategory($category)
            ->orderBy('logged_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get execution summary for the last run
     */
    public function getLastRunSummary(): array
    {
        $lastRun = $this->futuresTradingBot->logs()
            ->where('category', 'execution')
            ->where('message', 'like', '%FUTURES BOT START%')
            ->orderBy('logged_at', 'desc')
            ->first();

        if (!$lastRun) {
            return [];
        }

        $startTime = $lastRun->logged_at;
        $endTime = $this->futuresTradingBot->logs()
            ->where('logged_at', '>=', $startTime)
            ->where('message', 'like', '%FUTURES BOT END%')
            ->first()?->logged_at;

        $logs = $this->futuresTradingBot->logs()
            ->where('logged_at', '>=', $startTime)
            ->orderBy('logged_at', 'asc')
            ->get();

        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $endTime ? $endTime->diffInSeconds($startTime) : null,
            'total_logs' => $logs->count(),
            'errors' => $logs->where('level', 'error')->count(),
            'warnings' => $logs->where('level', 'warning')->count(),
            'logs' => $logs,
        ];
    }
}
