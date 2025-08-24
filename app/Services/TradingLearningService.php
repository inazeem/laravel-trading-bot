<?php

namespace App\Services;

use App\Models\FuturesTrade;
use App\Models\FuturesSignal;
use App\Models\FuturesTradingBot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TradingLearningService
{
    private FuturesTradingBot $bot;
    
    public function __construct(FuturesTradingBot $bot)
    {
        $this->bot = $bot;
    }
    
    /**
     * Analyze trading performance and learn from patterns
     */
    public function analyzeAndLearn(): array
    {
        $this->log("🧠 [LEARNING] Starting trading performance analysis...");
        
        $analysis = [
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'avg_win' => 0,
            'avg_loss' => 0,
            'profit_factor' => 0,
            'best_timeframes' => [],
            'best_signal_types' => [],
            'best_market_conditions' => [],
            'risk_adjustments' => [],
            'recommendations' => []
        ];
        
        try {
            // Get all completed trades for this bot
            $trades = FuturesTrade::where('futures_trading_bot_id', $this->bot->id)
                ->where('status', 'closed')
                ->with('signals')
                ->orderBy('closed_at', 'desc')
                ->get();
            
            if ($trades->isEmpty()) {
                $this->log("ℹ️ [LEARNING] No completed trades found for analysis");
                return $analysis;
            }
            
            $analysis['total_trades'] = $trades->count();
            $this->log("📊 [LEARNING] Analyzing {$analysis['total_trades']} completed trades");
            
            // Basic performance metrics
            $winningTrades = $trades->filter(fn($trade) => $trade->realized_pnl > 0);
            $losingTrades = $trades->filter(fn($trade) => $trade->realized_pnl < 0);
            
            $analysis['winning_trades'] = $winningTrades->count();
            $analysis['losing_trades'] = $losingTrades->count();
            $analysis['win_rate'] = $analysis['total_trades'] > 0 ? 
                ($analysis['winning_trades'] / $analysis['total_trades']) * 100 : 0;
            
            // Calculate average win and loss
            if ($winningTrades->isNotEmpty()) {
                $analysis['avg_win'] = $winningTrades->avg('realized_pnl');
            }
            
            if ($losingTrades->isNotEmpty()) {
                $analysis['avg_loss'] = abs($losingTrades->avg('realized_pnl'));
            }
            
            // Calculate profit factor
            $totalWins = $winningTrades->sum('realized_pnl');
            $totalLosses = abs($losingTrades->sum('realized_pnl'));
            $analysis['profit_factor'] = $totalLosses > 0 ? $totalWins / $totalLosses : 0;
            
            $this->log("📈 [LEARNING] Win Rate: {$analysis['win_rate']}%, Profit Factor: {$analysis['profit_factor']}");
            
            // Analyze signal performance
            $signalAnalysis = $this->analyzeSignalPerformance($trades);
            $analysis['best_signal_types'] = $signalAnalysis['best_signals'];
            $analysis['best_timeframes'] = $signalAnalysis['best_timeframes'];
            
            // Analyze market conditions
            $marketAnalysis = $this->analyzeMarketConditions($trades);
            $analysis['best_market_conditions'] = $marketAnalysis;
            
            // Generate risk adjustments
            $riskAdjustments = $this->generateRiskAdjustments($analysis);
            $analysis['risk_adjustments'] = $riskAdjustments;
            
            // Generate recommendations
            $recommendations = $this->generateRecommendations($analysis);
            $analysis['recommendations'] = $recommendations;
            
            // Store learning data in database
            $this->storeLearningData($analysis);
            
            // Apply learning to bot configuration
            $this->applyLearningToBot($analysis);
            
            $this->log("✅ [LEARNING] Analysis completed successfully");
            
        } catch (\Exception $e) {
            $this->log("❌ [LEARNING] Error during analysis: " . $e->getMessage());
        }
        
        return $analysis;
    }
    
    /**
     * Analyze signal performance patterns
     */
    private function analyzeSignalPerformance($trades): array
    {
        $signalStats = [];
        $timeframeStats = [];
        
        foreach ($trades as $trade) {
            $signal = $trade->signals->first();
            if (!$signal) continue;
            
            $signalType = $signal->signal_type;
            $timeframe = $signal->timeframe;
            $isWin = $trade->realized_pnl > 0;
            
            // Track signal type performance
            if (!isset($signalStats[$signalType])) {
                $signalStats[$signalType] = ['wins' => 0, 'losses' => 0, 'total_pnl' => 0];
            }
            
            if ($isWin) {
                $signalStats[$signalType]['wins']++;
            } else {
                $signalStats[$signalType]['losses']++;
            }
            $signalStats[$signalType]['total_pnl'] += $trade->realized_pnl;
            
            // Track timeframe performance
            if (!isset($timeframeStats[$timeframe])) {
                $timeframeStats[$timeframe] = ['wins' => 0, 'losses' => 0, 'total_pnl' => 0];
            }
            
            if ($isWin) {
                $timeframeStats[$timeframe]['wins']++;
            } else {
                $timeframeStats[$timeframe]['losses']++;
            }
            $timeframeStats[$timeframe]['total_pnl'] += $trade->realized_pnl;
        }
        
        // Calculate win rates and sort by performance
        $bestSignals = [];
        foreach ($signalStats as $signalType => $stats) {
            $total = $stats['wins'] + $stats['losses'];
            if ($total > 0) {
                $winRate = ($stats['wins'] / $total) * 100;
                $avgPnl = $stats['total_pnl'] / $total;
                
                $bestSignals[] = [
                    'signal_type' => $signalType,
                    'win_rate' => $winRate,
                    'avg_pnl' => $avgPnl,
                    'total_trades' => $total
                ];
            }
        }
        
        $bestTimeframes = [];
        foreach ($timeframeStats as $timeframe => $stats) {
            $total = $stats['wins'] + $stats['losses'];
            if ($total > 0) {
                $winRate = ($stats['wins'] / $total) * 100;
                $avgPnl = $stats['total_pnl'] / $total;
                
                $bestTimeframes[] = [
                    'timeframe' => $timeframe,
                    'win_rate' => $winRate,
                    'avg_pnl' => $avgPnl,
                    'total_trades' => $total
                ];
            }
        }
        
        // Sort by win rate and average PnL
        usort($bestSignals, fn($a, $b) => ($b['win_rate'] + $b['avg_pnl']) <=> ($a['win_rate'] + $a['avg_pnl']));
        usort($bestTimeframes, fn($a, $b) => ($b['win_rate'] + $b['avg_pnl']) <=> ($a['win_rate'] + $a['avg_pnl']));
        
        $this->log("📊 [SIGNALS] Best performing signals: " . json_encode(array_slice($bestSignals, 0, 3)));
        $this->log("⏰ [TIMEFRAMES] Best performing timeframes: " . json_encode(array_slice($bestTimeframes, 0, 3)));
        
        return [
            'best_signals' => array_slice($bestSignals, 0, 5),
            'best_timeframes' => array_slice($bestTimeframes, 0, 5)
        ];
    }
    
    /**
     * Analyze market conditions for winning trades
     */
    private function analyzeMarketConditions($trades): array
    {
        $winningTrades = $trades->filter(fn($trade) => $trade->realized_pnl > 0);
        $losingTrades = $trades->filter(fn($trade) => $trade->realized_pnl < 0);
        
        $conditions = [
            'winning_patterns' => [],
            'losing_patterns' => [],
            'market_volatility' => [],
            'time_of_day' => []
        ];
        
        // Analyze trade duration patterns
        $winningDurations = $winningTrades->map(function($trade) {
            $opened = \Carbon\Carbon::parse($trade->opened_at);
            $closed = \Carbon\Carbon::parse($trade->closed_at);
            return $opened->diffInMinutes($closed);
        });
        
        $losingDurations = $losingTrades->map(function($trade) {
            $opened = \Carbon\Carbon::parse($trade->opened_at);
            $closed = \Carbon\Carbon::parse($trade->closed_at);
            return $opened->diffInMinutes($closed);
        });
        
        if ($winningDurations->isNotEmpty()) {
            $conditions['winning_patterns']['avg_duration_minutes'] = $winningDurations->avg();
            $conditions['winning_patterns']['min_duration'] = $winningDurations->min();
            $conditions['winning_patterns']['max_duration'] = $winningDurations->max();
        }
        
        if ($losingDurations->isNotEmpty()) {
            $conditions['losing_patterns']['avg_duration_minutes'] = $losingDurations->avg();
            $conditions['losing_patterns']['min_duration'] = $losingDurations->min();
            $conditions['losing_patterns']['max_duration'] = $losingDurations->max();
        }
        
        // Analyze time of day patterns
        $winningHours = $winningTrades->map(function($trade) {
            return \Carbon\Carbon::parse($trade->opened_at)->hour;
        })->countBy();
        
        $losingHours = $losingTrades->map(function($trade) {
            return \Carbon\Carbon::parse($trade->opened_at)->hour;
        })->countBy();
        
        $conditions['time_of_day']['best_hours'] = $winningHours->sortDesc()->take(3)->keys()->toArray();
        $conditions['time_of_day']['worst_hours'] = $losingHours->sortDesc()->take(3)->keys()->toArray();
        
        // Analyze position size patterns
        $winningSizes = $winningTrades->pluck('quantity');
        $losingSizes = $losingTrades->pluck('quantity');
        
        if ($winningSizes->isNotEmpty()) {
            $conditions['winning_patterns']['avg_position_size'] = $winningSizes->avg();
        }
        
        if ($losingSizes->isNotEmpty()) {
            $conditions['losing_patterns']['avg_position_size'] = $losingSizes->avg();
        }
        
        $this->log("📈 [MARKET] Best trading hours: " . json_encode($conditions['time_of_day']['best_hours']));
        $this->log("📉 [MARKET] Worst trading hours: " . json_encode($conditions['time_of_day']['worst_hours']));
        
        return $conditions;
    }
    
    /**
     * Generate risk adjustments based on performance
     */
    private function generateRiskAdjustments(array $analysis): array
    {
        $adjustments = [];
        
        // Adjust risk percentage based on win rate
        $currentRisk = $this->bot->risk_percentage;
        $winRate = $analysis['win_rate'];
        
        if ($winRate < 40) {
            // Poor performance - reduce risk
            $adjustments['risk_percentage'] = max(0.5, $currentRisk * 0.8);
            $adjustments['risk_reason'] = "Low win rate ({$winRate}%) - reducing risk";
        } elseif ($winRate > 60) {
            // Good performance - can increase risk slightly
            $adjustments['risk_percentage'] = min(5.0, $currentRisk * 1.1);
            $adjustments['risk_reason'] = "High win rate ({$winRate}%) - increasing risk";
        } else {
            $adjustments['risk_percentage'] = $currentRisk;
            $adjustments['risk_reason'] = "Moderate win rate ({$winRate}%) - maintaining current risk";
        }
        
        // Adjust position size based on profit factor
        $profitFactor = $analysis['profit_factor'];
        $currentMaxSize = $this->bot->max_position_size;
        
        if ($profitFactor < 1.0) {
            // Losing money - reduce position size
            $adjustments['max_position_size'] = max(0.001, $currentMaxSize * 0.7);
            $adjustments['position_size_reason'] = "Low profit factor ({$profitFactor}) - reducing position size";
        } elseif ($profitFactor > 2.0) {
            // Very profitable - can increase position size
            $adjustments['max_position_size'] = $currentMaxSize * 1.2;
            $adjustments['position_size_reason'] = "High profit factor ({$profitFactor}) - increasing position size";
        } else {
            $adjustments['max_position_size'] = $currentMaxSize;
            $adjustments['position_size_reason'] = "Moderate profit factor ({$profitFactor}) - maintaining position size";
        }
        
        // Adjust stop loss and take profit based on average win/loss
        $avgWin = $analysis['avg_win'];
        $avgLoss = $analysis['avg_loss'];
        
        if ($avgWin > 0 && $avgLoss > 0) {
            $currentSL = $this->bot->stop_loss_percentage;
            $currentTP = $this->bot->take_profit_percentage;
            
            // Calculate optimal risk/reward ratio
            $optimalRatio = $avgWin / $avgLoss;
            $currentRatio = $currentTP / $currentSL;
            
            if ($optimalRatio > $currentRatio) {
                // Increase take profit to match optimal ratio
                $adjustments['take_profit_percentage'] = $currentSL * $optimalRatio;
                $adjustments['tp_reason'] = "Increasing TP to match optimal R:R ratio ({$optimalRatio})";
            }
        }
        
        return $adjustments;
    }
    
    /**
     * Generate trading recommendations
     */
    private function generateRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        // Signal type recommendations
        if (!empty($analysis['best_signal_types'])) {
            $bestSignal = $analysis['best_signal_types'][0];
            $recommendations[] = "Focus on {$bestSignal['signal_type']} signals (win rate: {$bestSignal['win_rate']}%)";
        }
        
        // Timeframe recommendations
        if (!empty($analysis['best_timeframes'])) {
            $bestTimeframe = $analysis['best_timeframes'][0];
            $recommendations[] = "Prioritize {$bestTimeframe['timeframe']} timeframe (win rate: {$bestTimeframe['win_rate']}%)";
        }
        
        // Market condition recommendations
        if (!empty($analysis['best_market_conditions']['time_of_day']['best_hours'])) {
            $bestHours = implode(', ', $analysis['best_market_conditions']['time_of_day']['best_hours']);
            $recommendations[] = "Trade during hours: {$bestHours} (best performance)";
        }
        
        // Risk management recommendations
        if ($analysis['win_rate'] < 50) {
            $recommendations[] = "Consider reducing risk percentage due to low win rate";
        }
        
        if ($analysis['profit_factor'] < 1.5) {
            $recommendations[] = "Improve risk/reward ratio to increase profit factor";
        }
        
        // Performance improvement suggestions
        if ($analysis['avg_loss'] > $analysis['avg_win']) {
            $recommendations[] = "Average loss is higher than average win - tighten stop losses";
        }
        
        if (count($analysis['best_signal_types']) > 1) {
            $recommendations[] = "Consider focusing on top 2-3 signal types for better consistency";
        }
        
        return $recommendations;
    }
    
    /**
     * Store learning data in database
     */
    private function storeLearningData(array $analysis): void
    {
        try {
            // Get trades for total PnL calculation
            $trades = FuturesTrade::where('futures_trading_bot_id', $this->bot->id)
                ->where('status', 'closed')
                ->get();
            
            $updates = [
                'learning_data' => $analysis,
                'total_pnl' => $trades->sum('realized_pnl'),
                'total_trades' => $analysis['total_trades'],
                'winning_trades' => $analysis['winning_trades'],
                'win_rate' => $analysis['win_rate'],
                'profit_factor' => $analysis['profit_factor'],
                'avg_win' => $analysis['avg_win'],
                'avg_loss' => $analysis['avg_loss'],
                'last_learning_at' => now(),
            ];
            
            // Store best performing patterns
            if (!empty($analysis['best_signal_types'])) {
                $updates['best_signal_type'] = $analysis['best_signal_types'][0]['signal_type'];
            }
            
            if (!empty($analysis['best_timeframes'])) {
                $updates['best_timeframe'] = $analysis['best_timeframes'][0]['timeframe'];
            }
            
            if (!empty($analysis['best_market_conditions']['time_of_day']['best_hours'])) {
                $updates['best_trading_hours'] = $analysis['best_market_conditions']['time_of_day']['best_hours'];
            }
            
            if (!empty($analysis['best_market_conditions']['time_of_day']['worst_hours'])) {
                $updates['worst_trading_hours'] = $analysis['best_market_conditions']['time_of_day']['worst_hours'];
            }
            
            $this->bot->update($updates);
            $this->log("💾 [LEARNING] Learning data stored in database");
            
        } catch (\Exception $e) {
            $this->log("❌ [LEARNING] Error storing learning data: " . $e->getMessage());
        }
    }
    
    /**
     * Apply learning to bot configuration
     */
    private function applyLearningToBot(array $analysis): void
    {
        $adjustments = $analysis['risk_adjustments'];
        $updates = [];
        
        // Apply risk percentage adjustment
        if (isset($adjustments['risk_percentage']) && $adjustments['risk_percentage'] != $this->bot->risk_percentage) {
            $updates['risk_percentage'] = $adjustments['risk_percentage'];
            $this->log("🔄 [LEARNING] Adjusting risk percentage: {$this->bot->risk_percentage}% → {$adjustments['risk_percentage']}%");
        }
        
        // Apply position size adjustment
        if (isset($adjustments['max_position_size']) && $adjustments['max_position_size'] != $this->bot->max_position_size) {
            $updates['max_position_size'] = $adjustments['max_position_size'];
            $this->log("🔄 [LEARNING] Adjusting max position size: {$this->bot->max_position_size} → {$adjustments['max_position_size']}");
        }
        
        // Apply take profit adjustment
        if (isset($adjustments['take_profit_percentage']) && $adjustments['take_profit_percentage'] != $this->bot->take_profit_percentage) {
            $updates['take_profit_percentage'] = $adjustments['take_profit_percentage'];
            $this->log("🔄 [LEARNING] Adjusting take profit: {$this->bot->take_profit_percentage}% → {$adjustments['take_profit_percentage']}%");
        }
        
        // Update bot configuration if there are changes
        if (!empty($updates)) {
            $this->bot->update($updates);
            $this->log("✅ [LEARNING] Bot configuration updated with learned parameters");
        } else {
            $this->log("ℹ️ [LEARNING] No configuration changes needed");
        }
    }
    
    /**
     * Get learning summary for the bot
     */
    public function getLearningSummary(): array
    {
        $trades = FuturesTrade::where('futures_trading_bot_id', $this->bot->id)
            ->where('status', 'closed')
            ->get();
        
        if ($trades->isEmpty()) {
            return ['message' => 'No trading history available for learning'];
        }
        
        $winningTrades = $trades->filter(fn($trade) => $trade->realized_pnl > 0);
        $totalPnL = $trades->sum('realized_pnl');
        $winRate = ($winningTrades->count() / $trades->count()) * 100;
        
        return [
            'total_trades' => $trades->count(),
            'winning_trades' => $winningTrades->count(),
            'win_rate' => round($winRate, 2),
            'total_pnl' => round($totalPnL, 4),
            'avg_pnl_per_trade' => round($totalPnL / $trades->count(), 4),
            'learning_status' => $trades->count() >= 10 ? 'Active' : 'Insufficient data'
        ];
    }
    
    private function log(string $message): void
    {
        Log::info($message);
    }
}
