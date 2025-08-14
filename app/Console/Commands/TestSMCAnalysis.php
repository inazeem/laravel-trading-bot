<?php

namespace App\Console\Commands;

use App\Models\TradingBot;
use App\Services\ExchangeService;
use App\Services\SmartMoneyConceptsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestSMCAnalysis extends Command
{
    protected $signature = 'test:smc {bot_id}';
    protected $description = 'Test Smart Money Concepts analysis for a specific bot';

    public function handle()
    {
        $botId = $this->argument('bot_id');
        $bot = TradingBot::find($botId);
        
        if (!$bot) {
            $this->error("Bot with ID {$botId} not found.");
            return;
        }
        
        $this->info("Testing SMC analysis for bot: {$bot->name}");
        $this->info("Symbol: {$bot->symbol}, Exchange: {$bot->exchange}");
        $this->info("Timeframes: " . implode(', ', $bot->timeframes));
        
        $exchangeService = new ExchangeService($bot->apiKey);
        
        // Get current price
        $currentPrice = $exchangeService->getCurrentPrice($bot->symbol);
        $this->info("Current price: {$currentPrice}");
        
        foreach ($bot->timeframes as $timeframe) {
            $this->info("\n=== Testing {$timeframe} timeframe ===");
            
            // Get interval for exchange
            $interval = $this->getExchangeInterval($bot->exchange, $timeframe);
            $this->info("Using interval: {$interval}");
            
            // Get candlesticks
            $candles = $exchangeService->getCandles($bot->symbol, $interval, 500);
            $this->info("Received " . count($candles) . " candlesticks");
            
            if (empty($candles)) {
                $this->warn("No candlestick data received for {$timeframe}");
                continue;
            }
            
            // Test SMC analysis
            $this->info("Running SMC analysis...");
            $smcService = new SmartMoneyConceptsService($candles);
            
            // Generate signals
            $signals = $smcService->generateSignals($currentPrice);
            $this->info("Generated " . count($signals) . " signals");
            
            foreach ($signals as $index => $signal) {
                $this->info("Signal {$index}: " . json_encode($signal));
            }
            
            // Test individual detection methods
            $this->info("\n--- Testing individual detection methods ---");
            
            $bos = $smcService->detectBOS($currentPrice);
            $this->info("BOS detection: " . ($bos ? json_encode($bos) : "null"));
            
            $choch = $smcService->detectCHoCH($currentPrice);
            $this->info("CHoCH detection: " . ($choch ? json_encode($choch) : "null"));
            
            $nearbyBlocks = $smcService->getNearbyOrderBlocks($currentPrice);
            $this->info("Nearby order blocks: " . count($nearbyBlocks));
            
            $levels = $smcService->getSupportResistanceLevels();
            $this->info("Support/Resistance levels: " . count($levels));
        }
    }
    
    private function getExchangeInterval(string $exchange, string $timeframe): string
    {
        if ($exchange === 'kucoin') {
            $kucoinIntervals = [
                '1h' => '1hour',
                '4h' => '4hour',
                '1d' => '1day'
            ];
            return $kucoinIntervals[$timeframe] ?? $timeframe;
        }
        
        return $timeframe;
    }
}
