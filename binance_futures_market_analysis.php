<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\ExchangeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Binance Futures Market Analysis Script
 * 
 * This script uses the Binance API to analyze market conditions and compare
 * them with bot trading performance to identify improvement opportunities.
 */

class BinanceFuturesMarketAnalyzer
{
    private $analysisStartDate;
    private $analysisEndDate;
    private $binanceBaseUrl = 'https://fapi.binance.com';
    private $report = [];
    
    public function __construct()
    {
        $this->analysisEndDate = Carbon::now();
        $this->analysisStartDate = Carbon::now()->subDays(20);
        
        echo "🚀 Binance Futures Market Analysis\n";
        echo "📅 Analysis Period: {$this->analysisStartDate->format('Y-m-d')} to {$this->analysisEndDate->format('Y-m-d')}\n";
        echo "==========================================\n\n";
    }
    
    public function runAnalysis()
    {
        try {
            // Load Laravel app
            $app = require_once __DIR__ . '/bootstrap/app.php';
            $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            
            $this->report['timestamp'] = now()->format('Y-m-d H:i:s');
            
            // 1. Analyze market volatility
            $this->analyzeMarketVolatility();
            
            // 2. Analyze trading volume patterns
            $this->analyzeTradingVolumePatterns();
            
            // 3. Analyze price movements vs bot trades
            $this->analyzePriceMovementsVsBotTrades();
            
            // 4. Analyze optimal trading times
            $this->analyzeOptimalTradingTimes();
            
            // 5. Compare market trends with bot performance
            $this->compareMarketTrendsWithBotPerformance();
            
            // 6. Generate market-based recommendations
            $this->generateMarketBasedRecommendations();
            
            // 7. Save report
            $this->saveReport();
            
        } catch (\Exception $e) {
            echo "❌ Error during analysis: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }
    
    private function analyzeMarketVolatility()
    {
        echo "📊 Analyzing Market Volatility...\n";
        
        $bots = FuturesTradingBot::where('is_active', true)->get();
        $volatilityData = [];
        
        foreach ($bots as $bot) {
            $symbol = str_replace('-', '', $bot->symbol); // Convert BTC-USDT to BTCUSDT
            
            echo "  Analyzing {$symbol}...\n";
            
            // Get 24hr ticker statistics
            $tickerData = $this->getBinance24hrTicker($symbol);
            
            if ($tickerData) {
                $priceChange = (float) $tickerData['priceChangePercent'];
                $volume = (float) $tickerData['volume'];
                $high = (float) $tickerData['highPrice'];
                $low = (float) $tickerData['lowPrice'];
                $close = (float) $tickerData['lastPrice'];
                
                $dailyRange = (($high - $low) / $close) * 100;
                
                $volatilityData[$symbol] = [
                    'daily_change_percent' => $priceChange,
                    'daily_range_percent' => round($dailyRange, 2),
                    'volume_24h' => $volume,
                    'price' => $close
                ];
                
                echo "    Daily Change: {$priceChange}%\n";
                echo "    Daily Range: " . round($dailyRange, 2) . "%\n";
                echo "    Volume: {$volume}\n";
            }
            
            // Get historical klines for more detailed volatility analysis
            $this->analyzeHistoricalVolatility($symbol, $bot);
        }
        
        $this->report['market_volatility'] = $volatilityData;
        echo "\n";
    }
    
    private function analyzeHistoricalVolatility($symbol, $bot)
    {
        // Get 1-hour klines for the last 20 days
        $startTime = $this->analysisStartDate->timestamp * 1000;
        $endTime = $this->analysisEndDate->timestamp * 1000;
        
        $response = Http::get($this->binanceBaseUrl . '/fapi/v1/klines', [
            'symbol' => $symbol,
            'interval' => '1h',
            'startTime' => $startTime,
            'endTime' => $endTime,
            'limit' => 1000
        ]);
        
        if ($response->successful()) {
            $klines = $response->json();
            $hourlyReturns = [];
            $hourlyVolumes = [];
            
            for ($i = 1; $i < count($klines); $i++) {
                $prevClose = (float) $klines[$i-1][4];
                $currentClose = (float) $klines[$i][4];
                $volume = (float) $klines[$i][5];
                
                if ($prevClose > 0) {
                    $return = (($currentClose - $prevClose) / $prevClose) * 100;
                    $hourlyReturns[] = $return;
                    $hourlyVolumes[] = $volume;
                }
            }
            
            if (!empty($hourlyReturns)) {
                $avgReturn = array_sum($hourlyReturns) / count($hourlyReturns);
                $volatility = $this->calculateStandardDeviation($hourlyReturns);
                $avgVolume = array_sum($hourlyVolumes) / count($hourlyVolumes);
                
                $this->report['historical_analysis'][$symbol] = [
                    'avg_hourly_return' => round($avgReturn, 4),
                    'hourly_volatility' => round($volatility, 4),
                    'avg_hourly_volume' => $avgVolume
                ];
                
                echo "    Historical Analysis (20 days):\n";
                echo "      Avg Hourly Return: " . round($avgReturn, 4) . "%\n";
                echo "      Hourly Volatility: " . round($volatility, 4) . "%\n";
                echo "      Avg Hourly Volume: {$avgVolume}\n";
            }
        }
    }
    
    private function analyzeTradingVolumePatterns()
    {
        echo "📈 Analyzing Trading Volume Patterns...\n";
        
        $bots = FuturesTradingBot::where('is_active', true)->get();
        
        foreach ($bots as $bot) {
            $symbol = str_replace('-', '', $bot->symbol);
            
            // Analyze volume by hour of day
            $hourlyVolumePattern = $this->getHourlyVolumePattern($symbol);
            
            if ($hourlyVolumePattern) {
                $this->report['volume_patterns'][$symbol] = $hourlyVolumePattern;
                
                echo "  {$symbol} Volume Pattern:\n";
                echo "    Peak Hours: ";
                $peakHours = array_keys(array_slice($hourlyVolumePattern, 0, 3, true));
                echo implode(', ', $peakHours) . "\n";
                
                echo "    Low Hours: ";
                $lowVolumePattern = array_slice($hourlyVolumePattern, -3, 3, true);
                $lowHours = array_keys($lowVolumePattern);
                echo implode(', ', $lowHours) . "\n";
            }
        }
        
        echo "\n";
    }
    
    private function getHourlyVolumePattern($symbol)
    {
        // Get 1-hour klines for pattern analysis
        $response = Http::get($this->binanceBaseUrl . '/fapi/v1/klines', [
            'symbol' => $symbol,
            'interval' => '1h',
            'limit' => 168 // 7 days of hourly data
        ]);
        
        if ($response->successful()) {
            $klines = $response->json();
            $hourlyVolumes = [];
            
            foreach ($klines as $kline) {
                $timestamp = $kline[0];
                $volume = (float) $kline[5];
                $hour = date('H', $timestamp / 1000);
                
                if (!isset($hourlyVolumes[$hour])) {
                    $hourlyVolumes[$hour] = [];
                }
                $hourlyVolumes[$hour][] = $volume;
            }
            
            // Calculate average volume for each hour
            $avgHourlyVolumes = [];
            foreach ($hourlyVolumes as $hour => $volumes) {
                $avgHourlyVolumes[$hour] = array_sum($volumes) / count($volumes);
            }
            
            // Sort by volume (descending)
            arsort($avgHourlyVolumes);
            
            return $avgHourlyVolumes;
        }
        
        return null;
    }
    
    private function analyzePriceMovementsVsBotTrades()
    {
        echo "🎯 Analyzing Price Movements vs Bot Trades...\n";
        
        $trades = FuturesTrade::whereBetween('created_at', [$this->analysisStartDate, $this->analysisEndDate])
            ->with('bot')
            ->get();
        
        $tradeEffectiveness = [];
        
        foreach ($trades as $trade) {
            $symbol = str_replace('-', '', $trade->symbol);
            $entryTime = $trade->opened_at;
            $exitTime = $trade->closed_at;
            
            if (!$exitTime) continue;
            
            // Get price data around the trade
            $priceAnalysis = $this->analyzePriceAroundTrade($symbol, $entryTime, $exitTime, $trade);
            
            if ($priceAnalysis) {
                $tradeEffectiveness[] = $priceAnalysis;
            }
        }
        
        $this->report['trade_effectiveness'] = $tradeEffectiveness;
        
        // Calculate summary statistics
        if (!empty($tradeEffectiveness)) {
            $totalTrades = count($tradeEffectiveness);
            $goodEntries = count(array_filter($tradeEffectiveness, function($trade) {
                return $trade['entry_timing_score'] >= 0.6;
            }));
            $goodExits = count(array_filter($tradeEffectiveness, function($trade) {
                return $trade['exit_timing_score'] >= 0.6;
            }));
            
            echo "  Trade Timing Analysis:\n";
            echo "    Good Entry Timing: {$goodEntries}/{$totalTrades} (" . round(($goodEntries/$totalTrades)*100, 1) . "%)\n";
            echo "    Good Exit Timing: {$goodExits}/{$totalTrades} (" . round(($goodExits/$totalTrades)*100, 1) . "%)\n";
        }
        
        echo "\n";
    }
    
    private function analyzePriceAroundTrade($symbol, $entryTime, $exitTime, $trade)
    {
        // Get 5-minute klines around the trade
        $startTime = $entryTime->subHours(2)->timestamp * 1000;
        $endTime = $exitTime->addHours(2)->timestamp * 1000;
        
        $response = Http::get($this->binanceBaseUrl . '/fapi/v1/klines', [
            'symbol' => $symbol,
            'interval' => '5m',
            'startTime' => $startTime,
            'endTime' => $endTime,
            'limit' => 1000
        ]);
        
        if ($response->successful()) {
            $klines = $response->json();
            
            $entryTimestamp = $trade->opened_at->timestamp * 1000;
            $exitTimestamp = $trade->closed_at->timestamp * 1000;
            
            // Find price movements before and after entry
            $pricesBeforeEntry = [];
            $pricesAfterEntry = [];
            $pricesBeforeExit = [];
            $pricesAfterExit = [];
            
            foreach ($klines as $kline) {
                $timestamp = $kline[0];
                $close = (float) $kline[4];
                
                if ($timestamp < $entryTimestamp && $timestamp >= $entryTimestamp - (30 * 60 * 1000)) {
                    $pricesBeforeEntry[] = $close;
                } elseif ($timestamp > $entryTimestamp && $timestamp <= $entryTimestamp + (30 * 60 * 1000)) {
                    $pricesAfterEntry[] = $close;
                }
                
                if ($timestamp < $exitTimestamp && $timestamp >= $exitTimestamp - (30 * 60 * 1000)) {
                    $pricesBeforeExit[] = $close;
                } elseif ($timestamp > $exitTimestamp && $timestamp <= $exitTimestamp + (30 * 60 * 1000)) {
                    $pricesAfterExit[] = $close;
                }
            }
            
            // Calculate timing scores
            $entryScore = $this->calculateTimingScore($pricesBeforeEntry, $pricesAfterEntry, $trade->side, 'entry');
            $exitScore = $this->calculateTimingScore($pricesBeforeExit, $pricesAfterExit, $trade->side, 'exit');
            
            return [
                'trade_id' => $trade->id,
                'symbol' => $symbol,
                'side' => $trade->side,
                'entry_timing_score' => $entryScore,
                'exit_timing_score' => $exitScore,
                'realized_pnl' => $trade->realized_pnl
            ];
        }
        
        return null;
    }
    
    private function calculateTimingScore($pricesBefore, $pricesAfter, $side, $type)
    {
        if (empty($pricesBefore) || empty($pricesAfter)) {
            return 0.5; // Neutral score if no data
        }
        
        $avgBefore = array_sum($pricesBefore) / count($pricesBefore);
        $avgAfter = array_sum($pricesAfter) / count($pricesAfter);
        
        $priceChange = (($avgAfter - $avgBefore) / $avgBefore) * 100;
        
        // For entry timing
        if ($type === 'entry') {
            if ($side === 'long') {
                // Good long entry: price goes up after entry
                return $priceChange > 0 ? min(1.0, 0.5 + ($priceChange / 10)) : max(0.0, 0.5 + ($priceChange / 10));
            } else {
                // Good short entry: price goes down after entry
                return $priceChange < 0 ? min(1.0, 0.5 + (abs($priceChange) / 10)) : max(0.0, 0.5 - ($priceChange / 10));
            }
        }
        
        // For exit timing
        if ($type === 'exit') {
            if ($side === 'long') {
                // Good long exit: price goes down after exit (sold at peak)
                return $priceChange < 0 ? min(1.0, 0.5 + (abs($priceChange) / 10)) : max(0.0, 0.5 - ($priceChange / 10));
            } else {
                // Good short exit: price goes up after exit (covered at bottom)
                return $priceChange > 0 ? min(1.0, 0.5 + ($priceChange / 10)) : max(0.0, 0.5 + ($priceChange / 10));
            }
        }
        
        return 0.5;
    }
    
    private function analyzeOptimalTradingTimes()
    {
        echo "⏰ Analyzing Optimal Trading Times...\n";
        
        $trades = FuturesTrade::whereBetween('created_at', [$this->analysisStartDate, $this->analysisEndDate])
            ->whereNotNull('realized_pnl')
            ->get();
        
        $hourlyPerformance = [];
        $dailyPerformance = [];
        
        foreach ($trades as $trade) {
            $hour = $trade->opened_at->hour;
            $dayOfWeek = $trade->opened_at->dayOfWeek; // 0 = Sunday, 6 = Saturday
            
            // Hourly performance
            if (!isset($hourlyPerformance[$hour])) {
                $hourlyPerformance[$hour] = ['trades' => 0, 'wins' => 0, 'total_pnl' => 0];
            }
            $hourlyPerformance[$hour]['trades']++;
            $hourlyPerformance[$hour]['total_pnl'] += $trade->realized_pnl;
            if ($trade->realized_pnl > 0) {
                $hourlyPerformance[$hour]['wins']++;
            }
            
            // Daily performance
            if (!isset($dailyPerformance[$dayOfWeek])) {
                $dailyPerformance[$dayOfWeek] = ['trades' => 0, 'wins' => 0, 'total_pnl' => 0];
            }
            $dailyPerformance[$dayOfWeek]['trades']++;
            $dailyPerformance[$dayOfWeek]['total_pnl'] += $trade->realized_pnl;
            if ($trade->realized_pnl > 0) {
                $dailyPerformance[$dayOfWeek]['wins']++;
            }
        }
        
        // Calculate win rates
        foreach ($hourlyPerformance as $hour => &$data) {
            $data['win_rate'] = $data['trades'] > 0 ? ($data['wins'] / $data['trades']) * 100 : 0;
        }
        
        foreach ($dailyPerformance as $day => &$data) {
            $data['win_rate'] = $data['trades'] > 0 ? ($data['wins'] / $data['trades']) * 100 : 0;
        }
        
        $this->report['optimal_trading_times'] = [
            'hourly_performance' => $hourlyPerformance,
            'daily_performance' => $dailyPerformance
        ];
        
        // Find best and worst hours
        $bestHour = array_keys($hourlyPerformance, max($hourlyPerformance))[0];
        $worstHour = array_keys($hourlyPerformance, min($hourlyPerformance))[0];
        
        echo "  Best Trading Hour: {$bestHour}:00 UTC\n";
        echo "  Worst Trading Hour: {$worstHour}:00 UTC\n";
        
        // Find best and worst days
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $bestDay = array_keys($dailyPerformance, max($dailyPerformance))[0];
        $worstDay = array_keys($dailyPerformance, min($dailyPerformance))[0];
        
        echo "  Best Trading Day: {$dayNames[$bestDay]}\n";
        echo "  Worst Trading Day: {$dayNames[$worstDay]}\n";
        
        echo "\n";
    }
    
    private function compareMarketTrendsWithBotPerformance()
    {
        echo "📈 Comparing Market Trends with Bot Performance...\n";
        
        $bots = FuturesTradingBot::where('is_active', true)->get();
        $marketBotComparison = [];
        
        foreach ($bots as $bot) {
            $symbol = str_replace('-', '', $bot->symbol);
            
            // Get market trend for the period
            $marketTrend = $this->getMarketTrend($symbol);
            
            // Get bot performance for the same period
            $botTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                ->whereBetween('created_at', [$this->analysisStartDate, $this->analysisEndDate])
                ->get();
            
            $botPnL = $botTrades->sum('realized_pnl');
            $botWinRate = $botTrades->count() > 0 ? 
                ($botTrades->where('realized_pnl', '>', 0)->count() / $botTrades->count()) * 100 : 0;
            
            $marketBotComparison[$symbol] = [
                'market_trend_percent' => $marketTrend,
                'bot_pnl' => $botPnL,
                'bot_trades' => $botTrades->count(),
                'bot_win_rate' => round($botWinRate, 2),
                'trend_alignment' => $this->calculateTrendAlignment($marketTrend, $botPnL, $botTrades)
            ];
            
            echo "  {$symbol}:\n";
            echo "    Market Trend: {$marketTrend}%\n";
            echo "    Bot PnL: {$botPnL}\n";
            echo "    Bot Win Rate: {$botWinRate}%\n";
        }
        
        $this->report['market_bot_comparison'] = $marketBotComparison;
        echo "\n";
    }
    
    private function getMarketTrend($symbol)
    {
        // Get price at start and end of analysis period
        $startTime = $this->analysisStartDate->timestamp * 1000;
        $endTime = $this->analysisEndDate->timestamp * 1000;
        
        $response = Http::get($this->binanceBaseUrl . '/fapi/v1/klines', [
            'symbol' => $symbol,
            'interval' => '1d',
            'startTime' => $startTime,
            'endTime' => $endTime,
            'limit' => 30
        ]);
        
        if ($response->successful()) {
            $klines = $response->json();
            
            if (count($klines) >= 2) {
                $startPrice = (float) $klines[0][1]; // Open price of first candle
                $endPrice = (float) $klines[count($klines)-1][4]; // Close price of last candle
                
                return round((($endPrice - $startPrice) / $startPrice) * 100, 2);
            }
        }
        
        return 0;
    }
    
    private function calculateTrendAlignment($marketTrend, $botPnL, $botTrades)
    {
        if ($botTrades->isEmpty()) {
            return 'no_trades';
        }
        
        $longTrades = $botTrades->where('side', 'long')->count();
        $shortTrades = $botTrades->where('side', 'short')->count();
        
        if ($marketTrend > 2) { // Strong uptrend
            if ($longTrades > $shortTrades && $botPnL > 0) {
                return 'well_aligned';
            } elseif ($longTrades > $shortTrades && $botPnL < 0) {
                return 'aligned_but_losing';
            } else {
                return 'misaligned';
            }
        } elseif ($marketTrend < -2) { // Strong downtrend
            if ($shortTrades > $longTrades && $botPnL > 0) {
                return 'well_aligned';
            } elseif ($shortTrades > $longTrades && $botPnL < 0) {
                return 'aligned_but_losing';
            } else {
                return 'misaligned';
            }
        } else { // Sideways market
            return 'sideways_market';
        }
    }
    
    private function generateMarketBasedRecommendations()
    {
        echo "💡 Generating Market-Based Recommendations...\n";
        
        $recommendations = [];
        
        // 1. Volatility-based recommendations
        if (isset($this->report['market_volatility'])) {
            foreach ($this->report['market_volatility'] as $symbol => $data) {
                if ($data['daily_range_percent'] > 5) {
                    $recommendations[] = [
                        'priority' => 'MEDIUM',
                        'category' => 'Volatility Management',
                        'symbol' => $symbol,
                        'issue' => "High volatility ({$data['daily_range_percent']}% daily range)",
                        'recommendation' => 'Consider widening stop losses and reducing position size for high volatility periods'
                    ];
                } elseif ($data['daily_range_percent'] < 1) {
                    $recommendations[] = [
                        'priority' => 'LOW',
                        'category' => 'Volatility Management',
                        'symbol' => $symbol,
                        'issue' => "Low volatility ({$data['daily_range_percent']}% daily range)",
                        'recommendation' => 'Consider tighter stop losses and smaller take profit targets in low volatility'
                    ];
                }
            }
        }
        
        // 2. Volume pattern recommendations
        if (isset($this->report['volume_patterns'])) {
            foreach ($this->report['volume_patterns'] as $symbol => $hourlyVolumes) {
                $peakHours = array_slice(array_keys($hourlyVolumes), 0, 3);
                $lowHours = array_slice(array_keys($hourlyVolumes), -3);
                
                $recommendations[] = [
                    'priority' => 'MEDIUM',
                    'category' => 'Trading Schedule',
                    'symbol' => $symbol,
                    'issue' => 'Trading outside peak volume hours',
                    'recommendation' => "Focus trading on peak volume hours: " . implode(', ', $peakHours) . " UTC. Avoid: " . implode(', ', $lowHours) . " UTC"
                ];
            }
        }
        
        // 3. Timing effectiveness recommendations
        if (isset($this->report['trade_effectiveness'])) {
            $poorEntryTiming = array_filter($this->report['trade_effectiveness'], function($trade) {
                return $trade['entry_timing_score'] < 0.4;
            });
            
            $poorExitTiming = array_filter($this->report['trade_effectiveness'], function($trade) {
                return $trade['exit_timing_score'] < 0.4;
            });
            
            if (count($poorEntryTiming) > count($this->report['trade_effectiveness']) * 0.3) {
                $recommendations[] = [
                    'priority' => 'HIGH',
                    'category' => 'Entry Timing',
                    'issue' => 'Poor entry timing in ' . count($poorEntryTiming) . ' trades',
                    'recommendation' => 'Consider adding confluence requirements or waiting for better price action confirmation before entry'
                ];
            }
            
            if (count($poorExitTiming) > count($this->report['trade_effectiveness']) * 0.3) {
                $recommendations[] = [
                    'priority' => 'HIGH',
                    'category' => 'Exit Timing',
                    'issue' => 'Poor exit timing in ' . count($poorExitTiming) . ' trades',
                    'recommendation' => 'Consider implementing trailing stops or partial profit taking instead of fixed take profit levels'
                ];
            }
        }
        
        // 4. Market trend alignment recommendations
        if (isset($this->report['market_bot_comparison'])) {
            foreach ($this->report['market_bot_comparison'] as $symbol => $data) {
                if ($data['trend_alignment'] === 'misaligned') {
                    $recommendations[] = [
                        'priority' => 'HIGH',
                        'category' => 'Trend Alignment',
                        'symbol' => $symbol,
                        'issue' => 'Trading against market trend',
                        'recommendation' => 'Review signal logic to better align with market direction or add trend filters'
                    ];
                } elseif ($data['trend_alignment'] === 'aligned_but_losing') {
                    $recommendations[] = [
                        'priority' => 'MEDIUM',
                        'category' => 'Execution Quality',
                        'symbol' => $symbol,
                        'issue' => 'Trading with trend but still losing',
                        'recommendation' => 'Review entry and exit criteria - trend direction is correct but execution needs improvement'
                    ];
                }
            }
        }
        
        $this->report['market_recommendations'] = $recommendations;
        
        foreach ($recommendations as $rec) {
            echo "  🔥 {$rec['priority']} - {$rec['category']}\n";
            if (isset($rec['symbol'])) {
                echo "     Symbol: {$rec['symbol']}\n";
            }
            echo "     Issue: {$rec['issue']}\n";
            echo "     Recommendation: {$rec['recommendation']}\n\n";
        }
    }
    
    private function getBinance24hrTicker($symbol)
    {
        $response = Http::get($this->binanceBaseUrl . '/fapi/v1/ticker/24hr', [
            'symbol' => $symbol
        ]);
        
        return $response->successful() ? $response->json() : null;
    }
    
    private function calculateStandardDeviation($values)
    {
        $mean = array_sum($values) / count($values);
        $sumSquares = array_sum(array_map(function($val) use ($mean) {
            return pow($val - $mean, 2);
        }, $values));
        
        return sqrt($sumSquares / count($values));
    }
    
    private function saveReport()
    {
        $reportFile = 'binance_market_analysis_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($this->report, JSON_PRETTY_PRINT));
        
        echo "✅ Market analysis complete! Report saved to: {$reportFile}\n\n";
        
        echo "📊 MARKET ANALYSIS SUMMARY\n";
        echo "===========================\n";
        
        if (isset($this->report['market_volatility'])) {
            echo "📈 Symbols Analyzed: " . count($this->report['market_volatility']) . "\n";
        }
        
        if (isset($this->report['trade_effectiveness'])) {
            echo "🎯 Trades Analyzed: " . count($this->report['trade_effectiveness']) . "\n";
        }
        
        if (isset($this->report['market_recommendations'])) {
            echo "💡 Market-Based Recommendations: " . count($this->report['market_recommendations']) . "\n";
        }
        
        echo "\n🔥 KEY INSIGHTS:\n";
        
        // Market volatility insights
        if (isset($this->report['market_volatility'])) {
            $highVolSymbols = array_filter($this->report['market_volatility'], function($data) {
                return $data['daily_range_percent'] > 3;
            });
            
            if (!empty($highVolSymbols)) {
                echo "  • High volatility detected in: " . implode(', ', array_keys($highVolSymbols)) . "\n";
            }
        }
        
        // Trading time insights
        if (isset($this->report['optimal_trading_times']['hourly_performance'])) {
            $hourlyPerf = $this->report['optimal_trading_times']['hourly_performance'];
            $bestHours = array_keys(array_slice($hourlyPerf, 0, 3, true));
            echo "  • Best trading hours: " . implode(', ', $bestHours) . " UTC\n";
        }
        
        echo "\n🎯 IMPLEMENTATION PRIORITY:\n";
        echo "1. Address HIGH priority market recommendations first\n";
        echo "2. Adjust trading schedule based on volume patterns\n";
        echo "3. Adapt position sizing for current market volatility\n";
        echo "4. Monitor trend alignment weekly\n";
    }
}

// Run the analysis
$analyzer = new BinanceFuturesMarketAnalyzer();
$analyzer->runAnalysis();
