<?php

namespace App\Http\Controllers;

use App\Models\FuturesTradingBot;
use App\Models\ApiKey;
use App\Services\FuturesTradingBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FuturesTradingBotController extends Controller
{
    public function index()
    {
        $bots = Auth::user()->futuresTradingBots()
            ->with(['apiKey', 'trades'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Load current open trade for each bot
        foreach ($bots as $bot) {
            $bot->currentOpenTrade = $bot->openTrades()
                ->orderBy('opened_at', 'desc')
                ->first();
        }

        return view('futures-bots.index', compact('bots'));
    }

    public function create()
    {
        $apiKeys = Auth::user()->apiKeys()
            ->where('is_active', true)
            ->where('exchange', '!=', 'coinbase') // Most exchanges support futures
            ->get();

        $timeframes = ['1m', '5m', '15m'];
        $leverages = [1, 2, 3, 5, 10, 20, 50, 100];
        $marginTypes = ['isolated', 'cross'];
        $positionSides = ['long', 'short', 'both'];

        return view('futures-bots.create', compact('apiKeys', 'timeframes', 'leverages', 'marginTypes', 'positionSides'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'api_key_id' => 'required|exists:api_keys,id',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'risk_percentage' => 'required|numeric|min:0.1|max:10',
            'max_position_size' => 'required|numeric|min:0.001',
            'timeframes' => 'required|array|min:1',
            'timeframes.*' => 'in:1m,5m,15m',
            'leverage' => 'required|integer|min:1|max:100',
            'margin_type' => 'required|in:isolated,cross',
            'position_side' => 'required|in:long,short,both',
            'stop_loss_percentage' => 'required|numeric|min:0.1|max:10',
            'take_profit_percentage' => 'required|numeric|min:0.1|max:20',
        ]);

        $apiKey = ApiKey::where('id', $request->api_key_id)
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $bot = FuturesTradingBot::create([
                'user_id' => Auth::id(),
                'api_key_id' => $apiKey->id,
                'name' => $request->name,
                'exchange' => $apiKey->exchange,
                'symbol' => strtoupper($request->symbol),
                'is_active' => true,
                'risk_percentage' => $request->risk_percentage,
                'max_position_size' => $request->max_position_size,
                'timeframes' => $request->timeframes,
                'leverage' => $request->leverage,
                'margin_type' => $request->margin_type,
                'position_side' => $request->position_side,
                'stop_loss_percentage' => $request->stop_loss_percentage,
                'take_profit_percentage' => $request->take_profit_percentage,
                'strategy_settings' => $request->strategy_settings ?? [],
                'status' => 'idle',
            ]);

            return redirect()->route('futures-bots.index')
                ->with('success', "Futures trading bot '{$bot->name}' created successfully");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create futures trading bot: ' . $e->getMessage());
        }
    }

    public function show(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        $futuresBot->load(['apiKey', 'trades', 'signals']);

        $recentTrades = $futuresBot->trades()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentSignals = $futuresBot->signals()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get current open trade for countdown timer
        $currentOpenTrade = $futuresBot->openTrades()
            ->orderBy('opened_at', 'desc')
            ->first();

        $stats = [
            'total_trades' => $futuresBot->trades()->count(),
            'open_trades' => $futuresBot->openTrades()->count(),
            'closed_trades' => $futuresBot->closedTrades()->count(),
            'total_pnl' => $futuresBot->total_pnl,
            'unrealized_pnl' => $futuresBot->unrealized_pnl,
            'win_rate' => $futuresBot->win_rate,
        ];

        return view('futures-bots.show', compact('futuresBot', 'recentTrades', 'recentSignals', 'stats', 'currentOpenTrade'));
    }

    public function edit(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        $apiKeys = Auth::user()->apiKeys()
            ->where('is_active', true)
            ->get();

        $timeframes = ['1m', '5m', '15m'];
        $leverages = [1, 2, 3, 5, 10, 20, 50, 100];
        $marginTypes = ['isolated', 'cross'];
        $positionSides = ['long', 'short', 'both'];

        return view('futures-bots.edit', compact('futuresBot', 'apiKeys', 'timeframes', 'leverages', 'marginTypes', 'positionSides'));
    }

    public function update(Request $request, FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'api_key_id' => 'required|exists:api_keys,id',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'risk_percentage' => 'required|numeric|min:0.1|max:10',
            'max_position_size' => 'required|numeric|min:0.001',
            'timeframes' => 'required|array|min:1',
            'timeframes.*' => 'in:1m,5m,15m',
            'leverage' => 'required|integer|min:1|max:100',
            'margin_type' => 'required|in:isolated,cross',
            'position_side' => 'required|in:long,short,both',
            'stop_loss_percentage' => 'required|numeric|min:0.1|max:10',
            'take_profit_percentage' => 'required|numeric|min:0.1|max:20',
        ]);

        try {
            $futuresBot->update([
                'api_key_id' => $request->api_key_id,
                'name' => $request->name,
                'symbol' => strtoupper($request->symbol),
                'risk_percentage' => $request->risk_percentage,
                'max_position_size' => $request->max_position_size,
                'timeframes' => $request->timeframes,
                'leverage' => $request->leverage,
                'margin_type' => $request->margin_type,
                'position_side' => $request->position_side,
                'stop_loss_percentage' => $request->stop_loss_percentage,
                'take_profit_percentage' => $request->take_profit_percentage,
                'strategy_settings' => $request->strategy_settings ?? $futuresBot->strategy_settings,
            ]);

            return redirect()->route('futures-bots.show', $futuresBot)
                ->with('success', "Futures trading bot '{$futuresBot->name}' updated successfully");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update futures trading bot: ' . $e->getMessage());
        }
    }

    public function destroy(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if bot has open trades
        if ($futuresBot->openTrades()->count() > 0) {
            return back()->with('error', 'Cannot delete bot with open trades. Please close all positions first.');
        }

        try {
            $futuresBot->delete();
            return redirect()->route('futures-bots.index')
                ->with('success', "Futures trading bot '{$futuresBot->name}' deleted successfully");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete futures trading bot: ' . $e->getMessage());
        }
    }

    public function toggle(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $futuresBot->update([
                'is_active' => !$futuresBot->is_active
            ]);

            $status = $futuresBot->is_active ? 'activated' : 'deactivated';
            return back()->with('success', "Futures trading bot '{$futuresBot->name}' {$status} successfully");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle futures trading bot: ' . $e->getMessage());
        }
    }

    public function run(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$futuresBot->is_active) {
            return back()->with('error', 'Cannot run inactive futures trading bot');
        }

        try {
            $service = new FuturesTradingBotService($futuresBot);
            $service->run();

            return back()->with('success', "Futures trading bot '{$futuresBot->name}' executed successfully");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to run futures trading bot: ' . $e->getMessage());
        }
    }

    public function trades(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        $trades = $futuresBot->trades()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('futures-bots.trades', compact('futuresBot', 'trades'));
    }

    public function signals(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        $signals = $futuresBot->signals()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('futures-bots.signals', compact('futuresBot', 'signals'));
    }

    public function logs(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }
        
        $logs = $futuresBot->logs()->latest()->paginate(50);
        $summary = (new \App\Services\FuturesTradingBotLogger($futuresBot))->getLastRunSummary();
        
        return view('futures-bots.logs', compact('futuresBot', 'logs', 'summary'));
    }

    public function clearLogs(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }
        
        try {
            $futuresBot->logs()->delete();
            
            return back()->with('success', 'All logs for this futures trading bot have been cleared successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clear logs.');
        }
    }

    public function closePosition(FuturesTradingBot $futuresBot)
    {
        // Ensure user owns this bot
        if ($futuresBot->user_id !== Auth::id()) {
            abort(403);
        }

        $openTrade = $futuresBot->openTrades()->first();
        
        if (!$openTrade) {
            return back()->with('error', 'No open position to close');
        }

        try {
            $service = new FuturesTradingBotService($futuresBot);
            
            // Get current price
            $exchangeService = new \App\Services\ExchangeService($futuresBot->apiKey);
            $currentPrice = $exchangeService->getCurrentPrice($futuresBot->symbol);
            
            if (!$currentPrice) {
                return back()->with('error', 'Failed to get current price');
            }

            // Close position
            $service->closePosition($openTrade, $currentPrice);

            return back()->with('success', 'Position closed successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to close position: ' . $e->getMessage());
        }
    }
}
