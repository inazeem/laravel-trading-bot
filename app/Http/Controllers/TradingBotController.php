<?php

namespace App\Http\Controllers;

use App\Models\TradingBot;
use App\Models\Trade;
use App\Models\Signal;
use App\Services\TradingBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TradingBotController extends Controller
{
    public function index()
    {
        $bots = auth()->user()->tradingBots()->withCount(['trades', 'signals'])->latest()->paginate(10);
        return view('trading-bots.index', compact('bots'));
    }

    public function create()
    {
        return view('trading-bots.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'exchange' => 'required|in:kucoin,binance',
            'symbol' => 'required|string|max:20',
            'api_key_id' => 'required|exists:api_keys,id',
            'risk_percentage' => 'required|numeric|min:0.1|max:10',
            'max_position_size' => 'required|numeric|min:0.001',
            'timeframes' => 'required|array|min:1',
            'timeframes.*' => 'in:1h,4h,1d',
            'is_active' => 'boolean'
        ]);

        // Verify API key belongs to user and has trade permission
        $apiKey = auth()->user()->apiKeys()->findOrFail($validated['api_key_id']);
        if (!$apiKey->hasPermission('trade')) {
            return back()->withInput()->with('error', 'Selected API key does not have trading permissions.');
        }

        $validated['user_id'] = auth()->id();

        try {
            $bot = TradingBot::create($validated);
            
            return redirect()->route('trading-bots.index')
                ->with('success', 'Trading bot created successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error creating trading bot: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create trading bot.');
        }
    }

    public function show(TradingBot $tradingBot)
    {
        // Ensure user can only view their own bots
        if ($tradingBot->user_id !== auth()->id()) {
            abort(403);
        }
        
        $tradingBot->load(['trades' => function($query) {
            $query->latest()->limit(20);
        }, 'signals' => function($query) {
            $query->latest()->limit(20);
        }]);
        
        $stats = [
            'total_trades' => $tradingBot->trades()->count(),
            'open_trades' => $tradingBot->trades()->where('status', 'open')->count(),
            'total_profit' => $tradingBot->trades()->where('status', 'closed')->sum('profit_loss'),
            'win_rate' => $this->calculateWinRate($tradingBot),
            'total_signals' => $tradingBot->signals()->count(),
            'executed_signals' => $tradingBot->signals()->where('is_executed', true)->count(),
        ];
        
        return view('trading-bots.show', compact('tradingBot', 'stats'));
    }

    public function edit(TradingBot $tradingBot)
    {
        // Ensure user can only edit their own bots
        if ($tradingBot->user_id !== auth()->id()) {
            abort(403);
        }
        
        return view('trading-bots.edit', compact('tradingBot'));
    }

    public function update(Request $request, TradingBot $tradingBot)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'exchange' => 'required|in:kucoin,binance',
            'symbol' => 'required|string|max:20',
            'api_key_id' => 'required|exists:api_keys,id',
            'risk_percentage' => 'required|numeric|min:0.1|max:10',
            'max_position_size' => 'required|numeric|min:0.001',
            'timeframes' => 'required|array|min:1',
            'timeframes.*' => 'in:1h,4h,1d',
            'is_active' => 'boolean'
        ]);

        // Verify API key belongs to user and has trade permission
        $apiKey = auth()->user()->apiKeys()->findOrFail($validated['api_key_id']);
        if (!$apiKey->hasPermission('trade')) {
            return back()->withInput()->with('error', 'Selected API key does not have trading permissions.');
        }

        try {
            $tradingBot->update($validated);
            
            return redirect()->route('trading-bots.show', $tradingBot)
                ->with('success', 'Trading bot updated successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error updating trading bot: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to update trading bot.');
        }
    }

    public function destroy(TradingBot $tradingBot)
    {
        // Ensure user can only delete their own bots
        if ($tradingBot->user_id !== auth()->id()) {
            abort(403);
        }
        
        try {
            $tradingBot->delete();
            return redirect()->route('trading-bots.index')
                ->with('success', 'Trading bot deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting trading bot: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete trading bot.');
        }
    }

    public function run(TradingBot $tradingBot)
    {
        // Ensure user can only run their own bots
        if ($tradingBot->user_id !== auth()->id()) {
            abort(403);
        }
        
        try {
            $botService = new TradingBotService($tradingBot);
            $botService->run();
            
            return back()->with('success', 'Trading bot executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error running trading bot: ' . $e->getMessage());
            return back()->with('error', 'Failed to run trading bot: ' . $e->getMessage());
        }
    }

    public function toggleStatus(TradingBot $tradingBot)
    {
        // Ensure user can only toggle their own bots
        if ($tradingBot->user_id !== auth()->id()) {
            abort(403);
        }
        
        try {
            $tradingBot->update(['is_active' => !$tradingBot->is_active]);
            
            $status = $tradingBot->is_active ? 'activated' : 'deactivated';
            return back()->with('success', "Trading bot {$status} successfully.");
        } catch (\Exception $e) {
            Log::error('Error toggling trading bot status: ' . $e->getMessage());
            return back()->with('error', 'Failed to update trading bot status.');
        }
    }

    public function trades(TradingBot $tradingBot)
    {
        // Ensure user can only view their own bot trades
        if ($tradingBot->user_id !== auth()->id()) {
            abort(403);
        }
        
        $trades = $tradingBot->trades()->latest()->paginate(20);
        return view('trading-bots.trades', compact('tradingBot', 'trades'));
    }

    public function signals(TradingBot $tradingBot)
    {
        // Ensure user can only view their own bot signals
        if ($tradingBot->user_id !== auth()->id()) {
            abort(403);
        }
        
        $signals = $tradingBot->signals()->latest()->paginate(20);
        return view('trading-bots.signals', compact('tradingBot', 'signals'));
    }

    private function calculateWinRate(TradingBot $tradingBot): float
    {
        $closedTrades = $tradingBot->trades()->where('status', 'closed')->get();
        
        if ($closedTrades->isEmpty()) {
            return 0;
        }
        
        $winningTrades = $closedTrades->filter(function($trade) {
            return $trade->profit_loss > 0;
        });
        
        return round(($winningTrades->count() / $closedTrades->count()) * 100, 2);
    }
}
