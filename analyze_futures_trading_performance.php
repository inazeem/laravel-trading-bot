<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Models\FuturesSignal;
use App\Services\ExchangeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive Futures Trading Performance Analysis Script
 * 
 * This script analyzes the last 20 days of futures trading data and provides
 * detailed recommendations for improving bot accuracy and performance.
 */

class FuturesTradingAnalyzer
{
    private $analysisStartDate;
    private $analysisEndDate;
    private $report = [];
    
    public function __construct()
    {
        $this->analysisEndDate = Carbon::now();
        $this->analysisStartDate = Carbon::now()->subDays(20);
        
        echo "🔍 Futures Trading Performance Analysis\n";
        echo "📅 Analysis Period: {$this->analysisStartDate->format('Y-m-d')} to {$this->analysisEndDate->format('Y-m-d')}\n";
        echo "==========================================\n\n";
    }
    
    public function runCompleteAnalysis()
    {
        try {
            // Load Laravel app
            $app = require_once __DIR__ . '/bootstrap/app.php';
            $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            
            $this->report['timestamp'] = now()->format('Y-m-d H:i:s');
            $this->report['analysis_period'] = [
                'start' => $this->analysisStartDate->format('Y-m-d'),
                'end' => $this->analysisEndDate->format('Y-m-d'),
                'days' => 20
            ];
            
            // 1. Bot Configuration Analysis
            $this->analyzeBotConfigurations();
            
            // 2. Trading Performance Analysis
            $this->analyzeTradingPerformance();
            
            // 3. Signal Analysis
            $this->analyzeSignalAccuracy();
            
            // 4. Risk Management Analysis
            $this->analyzeRiskManagement();
            
            // 5. Market Conditions Analysis
            $this->analyzeMarketConditions();
            
            // 6. Technical Analysis Performance
            $this->analyzeTechnicalIndicatorPerformance();
            
            // 7. Generate Recommendations
            $this->generateRecommendations();
            
            // 8. Generate Report
            $this->generateReport();
            
        } catch (\Exception $e) {
            echo "❌ Error during analysis: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }
    
    private function analyzeBotConfigurations()
    {
        echo "🤖 Analyzing Bot Configurations...\n";
        
        $bots = FuturesTradingBot::where('is_active', true)->get();
        
        $this->report['bot_configurations'] = [];
        
        foreach ($bots as $bot) {
            $config = [
                'id' => $bot->id,
                'name' => $bot->name,
                'symbol' => $bot->symbol,
                'exchange' => $bot->exchange,
                'timeframes' => $bot->timeframes,
                'risk_percentage' => $bot->risk_percentage,
                'leverage' => $bot->leverage,
                'margin_type' => $bot->margin_type,
                'stop_loss_percentage' => $bot->stop_loss_percentage,
                'take_profit_percentage' => $bot->take_profit_percentage,
                'position_side' => $bot->position_side,
                'enable_bitcoin_correlation' => $bot->enable_bitcoin_correlation,
                'total_pnl' => $bot->total_pnl,
                'total_trades' => $bot->total_trades,
                'win_rate' => $bot->win_rate,
                'profit_factor' => $bot->profit_factor
            ];
            
            $this->report['bot_configurations'][] = $config;
            
            echo "  Bot: {$bot->name} ({$bot->symbol})\n";
            echo "    Risk: {$bot->risk_percentage}%, Leverage: {$bot->leverage}x\n";
            echo "    Timeframes: " . implode(', ', $bot->timeframes) . "\n";
            echo "    Performance: {$bot->total_trades} trades, {$bot->win_rate}% win rate\n";
        }
        
        echo "\n";
    }
    
    private function analyzeTradingPerformance()
    {
        echo "📊 Analyzing Trading Performance...\n";
        
        $trades = FuturesTrade::whereBetween('created_at', [$this->analysisStartDate, $this->analysisEndDate])
            ->with('bot')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $totalTrades = $trades->count();
        $winningTrades = $trades->where('realized_pnl', '>', 0)->count();
        $losingTrades = $trades->where('realized_pnl', '<', 0)->count();
        $breakEvenTrades = $trades->where('realized_pnl', '=', 0)->count();
        
        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;
        
        $totalPnL = $trades->sum('realized_pnl');
        $avgWin = $winningTrades > 0 ? $trades->where('realized_pnl', '>', 0)->avg('realized_pnl') : 0;
        $avgLoss = $losingTrades > 0 ? abs($trades->where('realized_pnl', '<', 0)->avg('realized_pnl')) : 0;
        $profitFactor = $avgLoss > 0 ? $avgWin / $avgLoss : 0;
        
        $this->report['trading_performance'] = [
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'losing_trades' => $losingTrades,
            'break_even_trades' => $breakEvenTrades,
            'win_rate' => round($winRate, 2),
            'total_pnl' => round($totalPnL, 8),
            'avg_win' => round($avgWin, 8),
            'avg_loss' => round($avgLoss, 8),
            'profit_factor' => round($profitFactor, 2),
            'daily_trades' => round($totalTrades / 20, 1)
        ];
        
        echo "  📈 Total Trades: {$totalTrades}\n";
        echo "  ✅ Winning: {$winningTrades} ({$winRate}%)\n";
        echo "  ❌ Losing: {$losingTrades}\n";
        echo "  💰 Total PnL: {$totalPnL}\n";
        echo "  📊 Profit Factor: {$profitFactor}\n";
        echo "  📅 Daily Average: " . round($totalTrades / 20, 1) . " trades\n";
        
        // Analyze by symbol
        $symbolPerformance = [];
        foreach ($trades->groupBy('symbol') as $symbol => $symbolTrades) {
            $symbolTotalTrades = $symbolTrades->count();
            $symbolWins = $symbolTrades->where('realized_pnl', '>', 0)->count();
            $symbolWinRate = $symbolTotalTrades > 0 ? ($symbolWins / $symbolTotalTrades) * 100 : 0;
            $symbolPnL = $symbolTrades->sum('realized_pnl');
            
            $symbolPerformance[$symbol] = [
                'trades' => $symbolTotalTrades,
                'win_rate' => round($symbolWinRate, 2),
                'total_pnl' => round($symbolPnL, 8)
            ];
            
            echo "  📊 {$symbol}: {$symbolTotalTrades} trades, {$symbolWinRate}% win rate, PnL: {$symbolPnL}\n";
        }
        
        $this->report['symbol_performance'] = $symbolPerformance;
        
        // Analyze losing trades patterns
        $this->analyzeLossingTradePatterns($trades->where('realized_pnl', '<', 0));
        
        echo "\n";
    }
    
    private function analyzeLossingTradePatterns($losingTrades)
    {
        echo "  🔍 Analyzing Losing Trade Patterns...\n";
        
        if ($losingTrades->isEmpty()) {
            echo "    ✅ No losing trades in analysis period\n";
            return;
        }
        
        $patterns = [
            'timeframe_losses' => [],
            'symbol_losses' => [],
            'side_losses' => [],
            'common_issues' => []
        ];
        
        // Group by timeframes (from related signals)
        foreach ($losingTrades as $trade) {
            $signals = FuturesSignal::where('futures_trading_bot_id', $trade->futures_trading_bot_id)
                ->where('created_at', '>=', $trade->created_at->subMinutes(5))
                ->where('created_at', '<=', $trade->created_at->addMinutes(5))
                ->get();
            
            foreach ($signals as $signal) {
                $timeframe = $signal->timeframe;
                if (!isset($patterns['timeframe_losses'][$timeframe])) {
                    $patterns['timeframe_losses'][$timeframe] = 0;
                }
                $patterns['timeframe_losses'][$timeframe]++;
            }
            
            // Group by symbol
            $symbol = $trade->symbol;
            if (!isset($patterns['symbol_losses'][$symbol])) {
                $patterns['symbol_losses'][$symbol] = 0;
            }
            $patterns['symbol_losses'][$symbol]++;
            
            // Group by side
            $side = $trade->side;
            if (!isset($patterns['side_losses'][$side])) {
                $patterns['side_losses'][$side] = 0;
            }
            $patterns['side_losses'][$side]++;
            
            // Analyze stop loss hit vs take profit distance
            $entryPrice = $trade->entry_price;
            $stopLoss = $trade->stop_loss;
            $takeProfit = $trade->take_profit;
            $exitPrice = $trade->exit_price;
            
            if ($trade->side === 'long') {
                $stopLossDistance = (($entryPrice - $stopLoss) / $entryPrice) * 100;
                $takeProfitDistance = (($takeProfit - $entryPrice) / $entryPrice) * 100;
            } else {
                $stopLossDistance = (($stopLoss - $entryPrice) / $entryPrice) * 100;
                $takeProfitDistance = (($entryPrice - $takeProfit) / $entryPrice) * 100;
            }
            
            if ($stopLossDistance < 1.0) {
                $patterns['common_issues'][] = "Stop loss too tight: {$stopLossDistance}%";
            }
            
            if ($takeProfitDistance / $stopLossDistance < 2.0) {
                $patterns['common_issues'][] = "Poor risk/reward ratio: " . round($takeProfitDistance / $stopLossDistance, 2);
            }
        }
        
        $this->report['losing_trade_patterns'] = $patterns;
        
        echo "    📉 Timeframe Loss Distribution:\n";
        foreach ($patterns['timeframe_losses'] as $timeframe => $count) {
            echo "      {$timeframe}: {$count} losing trades\n";
        }
        
        echo "    📉 Side Loss Distribution:\n";
        foreach ($patterns['side_losses'] as $side => $count) {
            echo "      {$side}: {$count} losing trades\n";
        }
        
        if (!empty($patterns['common_issues'])) {
            echo "    ⚠️ Common Issues:\n";
            $issues = array_count_values($patterns['common_issues']);
            foreach ($issues as $issue => $count) {
                echo "      {$issue} (occurred {$count} times)\n";
            }
        }
    }
    
    private function analyzeSignalAccuracy()
    {
        echo "🎯 Analyzing Signal Accuracy...\n";
        
        $signals = FuturesSignal::whereBetween('created_at', [$this->analysisStartDate, $this->analysisEndDate])
            ->with('bot', 'trade')
            ->get();
        
        $totalSignals = $signals->count();
        $executedSignals = $signals->where('executed', true)->count();
        $executionRate = $totalSignals > 0 ? ($executedSignals / $totalSignals) * 100 : 0;
        
        // Group by signal type
        $signalTypePerformance = [];
        foreach ($signals->groupBy('signal_type') as $type => $typeSignals) {
            $typeExecuted = $typeSignals->where('executed', true)->count();
            $typeWins = 0;
            $typeTotalPnL = 0;
            
            foreach ($typeSignals->where('executed', true) as $signal) {
                if ($signal->trade && $signal->trade->realized_pnl > 0) {
                    $typeWins++;
                }
                if ($signal->trade) {
                    $typeTotalPnL += $signal->trade->realized_pnl;
                }
            }
            
            $typeWinRate = $typeExecuted > 0 ? ($typeWins / $typeExecuted) * 100 : 0;
            
            $signalTypePerformance[$type] = [
                'total_signals' => $typeSignals->count(),
                'executed_signals' => $typeExecuted,
                'win_rate' => round($typeWinRate, 2),
                'total_pnl' => round($typeTotalPnL, 8)
            ];
            
            echo "  📊 {$type}: {$typeSignals->count()} signals, {$typeExecuted} executed, {$typeWinRate}% win rate\n";
        }
        
        // Group by timeframe
        $timeframePerformance = [];
        foreach ($signals->groupBy('timeframe') as $timeframe => $timeframeSignals) {
            $tfExecuted = $timeframeSignals->where('executed', true)->count();
            $tfWins = 0;
            $tfTotalPnL = 0;
            
            foreach ($timeframeSignals->where('executed', true) as $signal) {
                if ($signal->trade && $signal->trade->realized_pnl > 0) {
                    $tfWins++;
                }
                if ($signal->trade) {
                    $tfTotalPnL += $signal->trade->realized_pnl;
                }
            }
            
            $tfWinRate = $tfExecuted > 0 ? ($tfWins / $tfExecuted) * 100 : 0;
            
            $timeframePerformance[$timeframe] = [
                'total_signals' => $timeframeSignals->count(),
                'executed_signals' => $tfExecuted,
                'win_rate' => round($tfWinRate, 2),
                'total_pnl' => round($tfTotalPnL, 8)
            ];
            
            echo "  ⏰ {$timeframe}: {$timeframeSignals->count()} signals, {$tfExecuted} executed, {$tfWinRate}% win rate\n";
        }
        
        $this->report['signal_analysis'] = [
            'total_signals' => $totalSignals,
            'executed_signals' => $executedSignals,
            'execution_rate' => round($executionRate, 2),
            'signal_type_performance' => $signalTypePerformance,
            'timeframe_performance' => $timeframePerformance
        ];
        
        echo "  📈 Total Signals: {$totalSignals}\n";
        echo "  ✅ Executed: {$executedSignals} ({$executionRate}%)\n";
        echo "\n";
    }
    
    private function analyzeRiskManagement()
    {
        echo "🛡️ Analyzing Risk Management...\n";
        
        $trades = FuturesTrade::whereBetween('created_at', [$this->analysisStartDate, $this->analysisEndDate])
            ->whereNotNull('realized_pnl')
            ->get();
        
        $riskRewardRatios = [];
        $stopLossHits = 0;
        $takeProfitHits = 0;
        $maxDrawdown = 0;
        $currentDrawdown = 0;
        $peak = 0;
        $runningPnL = 0;
        
        foreach ($trades->sortBy('created_at') as $trade) {
            $runningPnL += $trade->realized_pnl;
            
            if ($runningPnL > $peak) {
                $peak = $runningPnL;
                $currentDrawdown = 0;
            } else {
                $currentDrawdown = $peak - $runningPnL;
                if ($currentDrawdown > $maxDrawdown) {
                    $maxDrawdown = $currentDrawdown;
                }
            }
            
            // Calculate risk/reward ratio
            $entryPrice = $trade->entry_price;
            $stopLoss = $trade->stop_loss;
            $takeProfit = $trade->take_profit;
            $exitPrice = $trade->exit_price;
            
            if ($trade->side === 'long') {
                $risk = abs($entryPrice - $stopLoss);
                $reward = abs($takeProfit - $entryPrice);
                
                // Check if SL or TP was hit
                if ($exitPrice <= $stopLoss * 1.001) { // 0.1% tolerance
                    $stopLossHits++;
                }
                if ($exitPrice >= $takeProfit * 0.999) { // 0.1% tolerance
                    $takeProfitHits++;
                }
            } else {
                $risk = abs($stopLoss - $entryPrice);
                $reward = abs($entryPrice - $takeProfit);
                
                // Check if SL or TP was hit
                if ($exitPrice >= $stopLoss * 0.999) { // 0.1% tolerance
                    $stopLossHits++;
                }
                if ($exitPrice <= $takeProfit * 1.001) { // 0.1% tolerance
                    $takeProfitHits++;
                }
            }
            
            if ($risk > 0) {
                $riskRewardRatios[] = $reward / $risk;
            }
        }
        
        $avgRiskReward = count($riskRewardRatios) > 0 ? array_sum($riskRewardRatios) / count($riskRewardRatios) : 0;
        
        $this->report['risk_management'] = [
            'avg_risk_reward_ratio' => round($avgRiskReward, 2),
            'stop_loss_hits' => $stopLossHits,
            'take_profit_hits' => $takeProfitHits,
            'max_drawdown' => round($maxDrawdown, 8),
            'sl_to_tp_ratio' => $takeProfitHits > 0 ? round($stopLossHits / $takeProfitHits, 2) : 'N/A'
        ];
        
        echo "  📊 Average Risk/Reward Ratio: {$avgRiskReward}\n";
        echo "  🛑 Stop Loss Hits: {$stopLossHits}\n";
        echo "  🎯 Take Profit Hits: {$takeProfitHits}\n";
        echo "  📉 Max Drawdown: {$maxDrawdown}\n";
        echo "\n";
    }
    
    private function analyzeMarketConditions()
    {
        echo "🌍 Analyzing Market Conditions...\n";
        
        // Get Bitcoin correlation data if available
        $bots = FuturesTradingBot::where('enable_bitcoin_correlation', true)->get();
        
        if ($bots->count() > 0) {
            echo "  🔗 Bitcoin Correlation is enabled for {$bots->count()} bots\n";
            
            // Analyze correlation effectiveness
            $correlationTrades = FuturesTrade::whereIn('futures_trading_bot_id', $bots->pluck('id'))
                ->whereBetween('created_at', [$this->analysisStartDate, $this->analysisEndDate])
                ->get();
                
            $correlationWins = $correlationTrades->where('realized_pnl', '>', 0)->count();
            $correlationTotal = $correlationTrades->count();
            $correlationWinRate = $correlationTotal > 0 ? ($correlationWins / $correlationTotal) * 100 : 0;
            
            echo "  📊 Correlation-enabled trades: {$correlationTotal}, Win rate: {$correlationWinRate}%\n";
            
            $this->report['market_conditions'] = [
                'bitcoin_correlation_enabled' => true,
                'correlation_trades' => $correlationTotal,
                'correlation_win_rate' => round($correlationWinRate, 2)
            ];
        } else {
            echo "  ⚠️ Bitcoin correlation is disabled for all bots\n";
            $this->report['market_conditions'] = [
                'bitcoin_correlation_enabled' => false
            ];
        }
        
        echo "\n";
    }
    
    private function analyzeTechnicalIndicatorPerformance()
    {
        echo "📈 Analyzing Technical Indicator Performance...\n";
        
        $signals = FuturesSignal::whereBetween('created_at', [$this->analysisStartDate, $this->analysisEndDate])
            ->where('executed', true)
            ->with('trade')
            ->get();
        
        $strengthPerformance = [];
        
        foreach ($signals as $signal) {
            $strength = round($signal->strength, 1);
            
            if (!isset($strengthPerformance[$strength])) {
                $strengthPerformance[$strength] = [
                    'count' => 0,
                    'wins' => 0,
                    'total_pnl' => 0
                ];
            }
            
            $strengthPerformance[$strength]['count']++;
            
            if ($signal->trade && $signal->trade->realized_pnl > 0) {
                $strengthPerformance[$strength]['wins']++;
            }
            
            if ($signal->trade) {
                $strengthPerformance[$strength]['total_pnl'] += $signal->trade->realized_pnl;
            }
        }
        
        // Calculate win rates for each strength level
        foreach ($strengthPerformance as $strength => &$data) {
            $data['win_rate'] = $data['count'] > 0 ? ($data['wins'] / $data['count']) * 100 : 0;
            $data['win_rate'] = round($data['win_rate'], 2);
            $data['total_pnl'] = round($data['total_pnl'], 8);
        }
        
        // Sort by strength
        ksort($strengthPerformance);
        
        echo "  📊 Signal Strength Performance:\n";
        foreach ($strengthPerformance as $strength => $data) {
            echo "    {$strength}: {$data['count']} signals, {$data['win_rate']}% win rate, PnL: {$data['total_pnl']}\n";
        }
        
        $this->report['technical_indicator_performance'] = $strengthPerformance;
        
        echo "\n";
    }
    
    private function generateRecommendations()
    {
        echo "💡 Generating Recommendations...\n";
        
        $recommendations = [];
        
        // 1. Win Rate Analysis
        $winRate = $this->report['trading_performance']['win_rate'];
        if ($winRate < 40) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'category' => 'Signal Quality',
                'issue' => "Low win rate ({$winRate}%)",
                'recommendation' => 'Increase signal strength requirement from 80% to 85-90% to filter out weaker signals'
            ];
        }
        
        // 2. Risk/Reward Analysis
        $avgRiskReward = $this->report['risk_management']['avg_risk_reward_ratio'];
        if ($avgRiskReward < 2.0) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'category' => 'Risk Management',
                'issue' => "Poor risk/reward ratio ({$avgRiskReward})",
                'recommendation' => 'Increase take profit target to achieve minimum 2.5:1 risk/reward ratio'
            ];
        }
        
        // 3. Stop Loss Analysis
        $slHits = $this->report['risk_management']['stop_loss_hits'];
        $tpHits = $this->report['risk_management']['take_profit_hits'];
        if ($slHits > $tpHits * 1.5) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'category' => 'Stop Loss',
                'issue' => "Too many stop loss hits ({$slHits} SL vs {$tpHits} TP)",
                'recommendation' => 'Consider widening stop loss or improving entry timing'
            ];
        }
        
        // 4. Signal Strength Analysis
        if (isset($this->report['technical_indicator_performance'])) {
            $strengthData = $this->report['technical_indicator_performance'];
            $lowStrengthWins = 0;
            $highStrengthWins = 0;
            
            foreach ($strengthData as $strength => $data) {
                if ($strength < 0.8) {
                    $lowStrengthWins += $data['wins'];
                } else {
                    $highStrengthWins += $data['wins'];
                }
            }
            
            if ($lowStrengthWins > 0) {
                $recommendations[] = [
                    'priority' => 'MEDIUM',
                    'category' => 'Signal Filtering',
                    'issue' => 'Trading signals below 80% strength',
                    'recommendation' => 'Implement minimum 85% signal strength requirement for better accuracy'
                ];
            }
        }
        
        // 5. Timeframe Analysis
        if (isset($this->report['signal_analysis']['timeframe_performance'])) {
            $timeframePerf = $this->report['signal_analysis']['timeframe_performance'];
            $bestTimeframe = '';
            $bestWinRate = 0;
            $worstTimeframe = '';
            $worstWinRate = 100;
            
            foreach ($timeframePerf as $timeframe => $data) {
                if ($data['executed_signals'] > 5) { // Only consider timeframes with sufficient data
                    if ($data['win_rate'] > $bestWinRate) {
                        $bestWinRate = $data['win_rate'];
                        $bestTimeframe = $timeframe;
                    }
                    if ($data['win_rate'] < $worstWinRate) {
                        $worstWinRate = $data['win_rate'];
                        $worstTimeframe = $timeframe;
                    }
                }
            }
            
            if ($bestTimeframe && $worstTimeframe && $bestWinRate - $worstWinRate > 20) {
                $recommendations[] = [
                    'priority' => 'MEDIUM',
                    'category' => 'Timeframe Optimization',
                    'issue' => "Large performance gap between timeframes ({$bestTimeframe}: {$bestWinRate}% vs {$worstTimeframe}: {$worstWinRate}%)",
                    'recommendation' => "Focus on {$bestTimeframe} timeframe signals and reduce weight of {$worstTimeframe} signals"
                ];
            }
        }
        
        // 6. Daily Trading Volume
        $dailyTrades = $this->report['trading_performance']['daily_trades'];
        if ($dailyTrades > 10) {
            $recommendations[] = [
                'priority' => 'LOW',
                'category' => 'Trading Frequency',
                'issue' => "High trading frequency ({$dailyTrades} trades/day)",
                'recommendation' => 'Consider implementing stricter filtering to reduce overtrading'
            ];
        } elseif ($dailyTrades < 1) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'category' => 'Trading Frequency',
                'issue' => "Low trading frequency ({$dailyTrades} trades/day)",
                'recommendation' => 'Consider relaxing signal requirements to capture more opportunities'
            ];
        }
        
        // 7. Bitcoin Correlation
        if (!$this->report['market_conditions']['bitcoin_correlation_enabled']) {
            $recommendations[] = [
                'priority' => 'LOW',
                'category' => 'Market Analysis',
                'issue' => 'Bitcoin correlation is disabled',
                'recommendation' => 'Enable Bitcoin correlation for non-BTC pairs to improve market timing'
            ];
        }
        
        $this->report['recommendations'] = $recommendations;
        
        // Display recommendations
        foreach ($recommendations as $rec) {
            $priority = $rec['priority'];
            $category = $rec['category'];
            $issue = $rec['issue'];
            $recommendation = $rec['recommendation'];
            
            echo "  🔥 {$priority} - {$category}\n";
            echo "     Issue: {$issue}\n";
            echo "     Recommendation: {$recommendation}\n\n";
        }
    }
    
    private function generateReport()
    {
        echo "📋 Generating Final Report...\n";
        
        $reportFile = 'futures_trading_analysis_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($this->report, JSON_PRETTY_PRINT));
        
        echo "✅ Analysis complete! Report saved to: {$reportFile}\n\n";
        
        // Summary
        echo "📊 ANALYSIS SUMMARY\n";
        echo "==================\n";
        echo "📅 Period: {$this->report['analysis_period']['start']} to {$this->report['analysis_period']['end']}\n";
        echo "🤖 Active Bots: " . count($this->report['bot_configurations']) . "\n";
        echo "📈 Total Trades: {$this->report['trading_performance']['total_trades']}\n";
        echo "✅ Win Rate: {$this->report['trading_performance']['win_rate']}%\n";
        echo "💰 Total PnL: {$this->report['trading_performance']['total_pnl']}\n";
        echo "📊 Profit Factor: {$this->report['trading_performance']['profit_factor']}\n";
        echo "🎯 Avg Risk/Reward: {$this->report['risk_management']['avg_risk_reward_ratio']}\n";
        echo "⚠️ Recommendations: " . count($this->report['recommendations']) . "\n\n";
        
        echo "🔥 TOP PRIORITY RECOMMENDATIONS:\n";
        $highPriorityRecs = array_filter($this->report['recommendations'], function($rec) {
            return $rec['priority'] === 'HIGH';
        });
        
        if (empty($highPriorityRecs)) {
            echo "  ✅ No high priority issues found!\n";
        } else {
            foreach ($highPriorityRecs as $rec) {
                echo "  • {$rec['category']}: {$rec['recommendation']}\n";
            }
        }
        
        echo "\n🎯 NEXT STEPS:\n";
        echo "1. Review high priority recommendations first\n";
        echo "2. Test configuration changes in small position sizes\n";
        echo "3. Monitor performance for 3-5 days after changes\n";
        echo "4. Run this analysis again weekly to track improvements\n";
    }
}

// Run the analysis
$analyzer = new FuturesTradingAnalyzer();
$analyzer->runCompleteAnalysis();
