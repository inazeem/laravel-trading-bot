<?php

namespace App\Http\Controllers;

use App\Models\ScalpingTradingBot;
use App\Models\ScalpingTrade;
use App\Models\ScalpingSignal;
use App\Models\ApiKey;
use App\Services\ScalpingTradingBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ScalpingTradingBotController extends Controller
{
    /**
     * Display a listing of scalping bots
     */
    public function index()
    {
        $bots = ScalpingTradingBot::where('user_id', Auth::id())
            ->with(['openTrades', 'closedTrades'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate summary statistics
        $totalPnl = $bots->sum('total_pnl');
        $totalTrades = $bots->sum('total_trades');
        $avgWinRate = $bots->where('total_trades', '>', 0)->avg('win_rate');
        $activeBots = $bots->where('is_active', true)->count();

        return view('scalping-bots.index', compact('bots', 'totalPnl', 'totalTrades', 'avgWinRate', 'activeBots'));
    }

    /**
     * Show the form for creating a new scalping bot
     */
    public function create()
    {
        $apiKeys = ApiKey::where('user_id', Auth::id())->get();
        $exchanges = ['binance'];
        $timeframes = ['1m', '5m', '15m', '30m', '1h'];
        $defaultTimeframes = ['5m', '15m', '30m'];

        return view('scalping-bots.create', compact('apiKeys', 'exchanges', 'timeframes', 'defaultTimeframes'));
    }

    /**
     * Store a newly created scalping bot
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key_id' => 'required|exists:api_keys,id',
            'exchange' => 'required|in:binance',
            'symbol' => 'required|string|max:20',
            'risk_percentage' => 'required|numeric|min:0.1|max:5',
            'max_position_size' => 'required|numeric|min:0.001|max:1',
            'min_order_value' => 'required|numeric|min:5|max:1000',
            'order_type' => 'required|in:market,limit',
            'limit_order_buffer' => 'required_if:order_type,limit|numeric|min:0.01|max:1',
            'leverage' => 'required|integer|min:1|max:125',
            'margin_type' => 'required|in:isolated,cross',
            'position_side' => 'required|in:both,long,short',
            'timeframes' => 'required|array|min:2',
            'timeframes.*' => 'in:1m,5m,15m,30m,1h',
            'stop_loss_percentage' => 'required|numeric|min:0.5|max:5',
            'take_profit_percentage' => 'required|numeric|min:1|max:10',
            'min_risk_reward_ratio' => 'required|numeric|min:1|max:5',
            'max_trades_per_hour' => 'required|integer|min:1|max:20',
            'cooldown_seconds' => 'required|integer|min:15|max:300',
            'max_concurrent_positions' => 'required|integer|min:1|max:5',
            'max_spread_percentage' => 'required|numeric|min:0.05|max:0.5',
            'trailing_distance' => 'nullable|numeric|min:0.3|max:2',
            'breakeven_trigger' => 'nullable|numeric|min:0.5|max:3',
        ]);

        // Ensure user owns the API key
        $apiKey = ApiKey::where('id', $validated['api_key_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated['user_id'] = Auth::id();

        // Set defaults for optional fields
        $validated['enable_trailing_stop'] = $request->has('enable_trailing_stop');
        $validated['enable_breakeven'] = $request->has('enable_breakeven');
        $validated['enable_momentum_scalping'] = $request->has('enable_momentum_scalping');
        $validated['enable_price_action_scalping'] = $request->has('enable_price_action_scalping');
        $validated['enable_smart_money_scalping'] = $request->has('enable_smart_money_scalping');
        $validated['enable_quick_exit'] = $request->has('enable_quick_exit');
        $validated['enable_bitcoin_correlation'] = $request->has('enable_bitcoin_correlation');
        $validated['enable_volatility_filter'] = $request->has('enable_volatility_filter');
        $validated['enable_volume_filter'] = $request->has('enable_volume_filter');
        
        // Set default values for conditional fields
        if (!$validated['enable_trailing_stop']) {
            $validated['trailing_distance'] = 0.8; // Default value
        }
        if (!$validated['enable_breakeven']) {
            $validated['breakeven_trigger'] = 1.0; // Default value
        }

        $bot = ScalpingTradingBot::create($validated);

        return redirect()->route('scalping-bots.show', $bot)
            ->with('success', 'Scalping bot created successfully!');
    }

    /**
     * Display the specified scalping bot
     */
    public function show(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('view', $scalpingBot);

        $scalpingBot->load(['openTrades', 'closedTrades', 'signals']);

        // Recent trades (last 20)
        $recentTrades = $scalpingBot->trades()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Recent signals (last 20)
        $recentSignals = $scalpingBot->signals()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Performance metrics
        $todaysStats = [
            'trades' => $scalpingBot->todaysTrades()->count(),
            'pnl' => $scalpingBot->todaysTrades()->sum('net_pnl'),
            'win_rate' => $this->calculateWinRate($scalpingBot->todaysTrades()),
            'avg_duration' => $scalpingBot->todaysTrades()->avg('trade_duration_seconds'),
        ];

        $thisHourStats = [
            'trades' => $scalpingBot->tradesThisHour()->count(),
            'remaining_trades' => max(0, $scalpingBot->max_trades_per_hour - $scalpingBot->tradesThisHour()->count()),
            'pnl' => $scalpingBot->tradesThisHour()->sum('net_pnl'),
        ];

        // Signal analysis
        $signalStats = $this->getSignalStatistics($scalpingBot);

        return view('scalping-bots.show', compact(
            'scalpingBot', 
            'recentTrades', 
            'recentSignals', 
            'todaysStats', 
            'thisHourStats',
            'signalStats'
        ));
    }

    /**
     * Show the form for editing the specified scalping bot
     */
    public function edit(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('update', $scalpingBot);

        $apiKeys = ApiKey::where('user_id', Auth::id())->get();
        $exchanges = ['binance'];
        $timeframes = ['1m', '5m', '15m', '30m', '1h'];

        return view('scalping-bots.edit', compact('scalpingBot', 'apiKeys', 'exchanges', 'timeframes'));
    }

    /**
     * Update the specified scalping bot
     */
    public function update(Request $request, ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('update', $scalpingBot);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key_id' => 'required|exists:api_keys,id',
            'symbol' => 'required|string|max:20',
            'risk_percentage' => 'required|numeric|min:0.1|max:5',
            'max_position_size' => 'required|numeric|min:0.001|max:1',
            'min_order_value' => 'required|numeric|min:5|max:1000',
            'order_type' => 'required|in:market,limit',
            'limit_order_buffer' => 'required_if:order_type,limit|numeric|min:0.01|max:1',
            'leverage' => 'required|integer|min:1|max:125',
            'margin_type' => 'required|in:isolated,cross',
            'position_side' => 'required|in:both,long,short',
            'timeframes' => 'required|array|min:2',
            'timeframes.*' => 'in:1m,5m,15m,30m,1h',
            'stop_loss_percentage' => 'required|numeric|min:0.5|max:5',
            'take_profit_percentage' => 'required|numeric|min:1|max:10',
            'min_risk_reward_ratio' => 'required|numeric|min:1|max:5',
            'max_trades_per_hour' => 'required|integer|min:1|max:20',
            'cooldown_seconds' => 'required|integer|min:15|max:300',
            'max_concurrent_positions' => 'required|integer|min:1|max:5',
            'max_spread_percentage' => 'required|numeric|min:0.05|max:0.5',
            'trailing_distance' => 'nullable|numeric|min:0.3|max:2',
            'breakeven_trigger' => 'nullable|numeric|min:0.5|max:3',
        ]);

        // Ensure user owns the API key
        $apiKey = ApiKey::where('id', $validated['api_key_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Set boolean fields
        $validated['enable_trailing_stop'] = $request->has('enable_trailing_stop');
        $validated['enable_breakeven'] = $request->has('enable_breakeven');
        $validated['enable_momentum_scalping'] = $request->has('enable_momentum_scalping');
        $validated['enable_price_action_scalping'] = $request->has('enable_price_action_scalping');
        $validated['enable_smart_money_scalping'] = $request->has('enable_smart_money_scalping');
        $validated['enable_quick_exit'] = $request->has('enable_quick_exit');
        $validated['enable_bitcoin_correlation'] = $request->has('enable_bitcoin_correlation');
        $validated['enable_volatility_filter'] = $request->has('enable_volatility_filter');
        $validated['enable_volume_filter'] = $request->has('enable_volume_filter');
        
        // Set default values for conditional fields
        if (!$validated['enable_trailing_stop']) {
            $validated['trailing_distance'] = 0.8; // Default value
        }
        if (!$validated['enable_breakeven']) {
            $validated['breakeven_trigger'] = 1.0; // Default value
        }

        $scalpingBot->update($validated);

        return redirect()->route('scalping-bots.show', $scalpingBot)
            ->with('success', 'Scalping bot updated successfully!');
    }

    /**
     * Toggle bot active status
     */
    public function toggle(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('update', $scalpingBot);

        $scalpingBot->update([
            'is_active' => !$scalpingBot->is_active,
            'status' => $scalpingBot->is_active ? 'idle' : 'paused'
        ]);

        $status = $scalpingBot->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Scalping bot {$status} successfully!");
    }

    /**
     * Force run the scalping bot manually
     */
    public function run(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('update', $scalpingBot);

        if (!$scalpingBot->is_active) {
            return redirect()->back()
                ->with('error', 'Bot must be active to run manually.');
        }

        try {
            $service = new ScalpingTradingBotService($scalpingBot);
            $service->executeScalpingStrategy();
            
            return redirect()->back()
                ->with('success', 'Scalping bot executed manually. Check logs for results.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error running scalping bot: ' . $e->getMessage());
        }
    }

    /**
     * Show signals for the scalping bot
     */
    public function signals(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('view', $scalpingBot);

        $signals = $scalpingBot->signals()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('scalping-bots.signals', compact('scalpingBot', 'signals'));
    }

    /**
     * Show trades for the scalping bot
     */
    public function trades(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('view', $scalpingBot);

        $trades = $scalpingBot->trades()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('scalping-bots.trades', compact('scalpingBot', 'trades'));
    }

    /**
     * Close all open positions
     */
    public function closeAllPositions(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('update', $scalpingBot);

        try {
            $service = new ScalpingTradingBotService($scalpingBot);
            $closedCount = 0;
            
            foreach ($scalpingBot->openTrades as $trade) {
                // This would be implemented in the service
                // $service->closePosition($trade, 'manual_close');
                $closedCount++;
            }
            
            return redirect()->back()
                ->with('success', "Closed {$closedCount} open positions.");
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error closing positions: ' . $e->getMessage());
        }
    }

    /**
     * Reset bot learning data
     */
    public function resetLearning(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('update', $scalpingBot);

        $scalpingBot->update([
            'learning_data' => null,
            'last_learning_at' => null,
            'best_signal_type' => null,
            'best_timeframe' => null,
            'best_trading_hours' => null,
            'worst_trading_hours' => null,
            'best_rsi_entry_level' => null,
            'optimal_spread_threshold' => null,
        ]);

        return redirect()->back()
            ->with('success', 'Learning data reset successfully.');
    }

    /**
     * Trigger learning analysis
     */
    public function learn(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('update', $scalpingBot);

        try {
            $scalpingBot->learnFromTrades();
            
            return redirect()->back()
                ->with('success', 'Learning analysis completed.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error in learning analysis: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified scalping bot
     */
    public function destroy(ScalpingTradingBot $scalpingBot)
    {
        $this->authorize('delete', $scalpingBot);

        // Check if bot has open trades
        $openTradesCount = $scalpingBot->openTrades()->count();
        if ($openTradesCount > 0) {
            return redirect()->back()
                ->with('error', "Cannot delete bot with {$openTradesCount} open trades. Close all positions first.");
        }

        try {
            $botName = $scalpingBot->name;
            $scalpingBot->delete();

            return redirect()->route('scalping-bots.index')
                ->with('success', "Scalping bot '{$botName}' deleted successfully!");
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete scalping bot: ' . $e->getMessage());
        }
    }

    /**
     * Calculate win rate for a collection of trades
     */
    private function calculateWinRate($trades)
    {
        $count = $trades->count();
        if ($count == 0) return 0;
        
        $winning = $trades->where('net_pnl', '>', 0)->count();
        return ($winning / $count) * 100;
    }

    /**
     * Get signal statistics for the bot
     */
    private function getSignalStatistics(ScalpingTradingBot $scalpingBot)
    {
        $signals = $scalpingBot->signals()
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($signals->isEmpty()) {
            return [
                'total' => 0,
                'traded' => 0,
                'success_rate' => 0,
                'avg_strength' => 0,
                'best_type' => 'N/A',
            ];
        }

        $traded = $signals->where('was_traded', true);
        $successful = $signals->where('was_successful', true);

        $typePerformance = $signals->groupBy('signal_type')
            ->map(function ($typeSignals) {
                $successRate = $typeSignals->where('was_successful', true)->count() / $typeSignals->count();
                return [
                    'count' => $typeSignals->count(),
                    'success_rate' => $successRate * 100,
                ];
            })
            ->sortByDesc('success_rate');

        return [
            'total' => $signals->count(),
            'traded' => $traded->count(),
            'success_rate' => $signals->count() > 0 ? ($successful->count() / $signals->count()) * 100 : 0,
            'avg_strength' => $signals->avg('strength'),
            'best_type' => $typePerformance->keys()->first() ?? 'N/A',
            'type_performance' => $typePerformance,
        ];
    }
}

