<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BitcoinCorrelationService;
use App\Services\ExchangeService;
use App\Models\ApiKey;

class TestBitcoinCorrelation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:bitcoin-correlation {symbol=SUI-USDT}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Bitcoin correlation strategy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $symbol = $this->argument('symbol');
        
        $this->info("🔗 Testing Bitcoin Correlation Strategy");
        $this->info("Asset: {$symbol}");
        $this->info("Timeframe: 1h");
        $this->line('');
        
        try {
            // Get the first API key
            $apiKey = ApiKey::first();
            if (!$apiKey) {
                $this->error("No API key found. Please create an API key first.");
                return 1;
            }
            
            $exchangeService = new ExchangeService($apiKey);
            $btcCorrelationService = new BitcoinCorrelationService($exchangeService);
            
            // Test asset signal (simulated)
            $assetSignal = [
                'type' => 'OrderBlock_Breakout',
                'direction' => 'bearish',
                'strength' => 0.65,
                'timeframe' => '1h'
            ];
            
            $this->info("📊 Asset Signal:");
            $this->line("   Type: {$assetSignal['type']}");
            $this->line("   Direction: {$assetSignal['direction']}");
            $this->line("   Strength: {$assetSignal['strength']}");
            $this->line("   Timeframe: {$assetSignal['timeframe']}");
            $this->line('');
            
            // Get Bitcoin sentiment
            $this->info("🔍 Analyzing Bitcoin sentiment...");
            $btcSentiment = $btcCorrelationService->getBitcoinSentiment('1h');
            $this->line("   BTC Sentiment: " . number_format($btcSentiment, 3));
            
            if ($btcSentiment > 0.6) {
                $this->line("   📈 Bitcoin is strongly bullish");
            } elseif ($btcSentiment < -0.6) {
                $this->line("   📉 Bitcoin is strongly bearish");
            } else {
                $this->line("   ➡️ Bitcoin is neutral");
            }
            $this->line('');
            
            // Get correlation recommendation
            $this->info("🎯 Correlation Recommendation:");
            $recommendation = $btcCorrelationService->getCorrelationRecommendation($assetSignal, '1h');
            
            $this->line("   Should Trade: " . ($recommendation['should_trade'] ? '✅ YES' : '❌ NO'));
            $this->line("   Reason: {$recommendation['reason']}");
            $this->line("   Correlation Strength: " . number_format($recommendation['correlation_strength'], 3));
            $this->line('');
            
            // Test different scenarios
            $this->info("🧪 Testing Different Scenarios:");
            
            // Bullish asset signal
            $bullishSignal = array_merge($assetSignal, ['direction' => 'bullish']);
            $bullishRecommendation = $btcCorrelationService->getCorrelationRecommendation($bullishSignal, '1h');
            
            $this->line("   Bullish Asset Signal: " . ($bullishRecommendation['should_trade'] ? '✅ ALLOW' : '❌ BLOCK'));
            $this->line("   Reason: {$bullishRecommendation['reason']}");
            
            // Check if Bitcoin is in strong trend
            $isStrongTrend = $btcCorrelationService->isBitcoinInStrongTrend('1h');
            $this->line("   Bitcoin Strong Trend: " . ($isStrongTrend ? '✅ YES' : '❌ NO'));
            
            $this->line('');
            $this->info("✅ Bitcoin correlation test completed successfully!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error testing Bitcoin correlation: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
