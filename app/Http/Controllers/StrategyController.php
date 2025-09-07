<?php

namespace App\Http\Controllers;

use App\Models\TradingStrategy;
use App\Models\StrategyParameter;
use App\Models\BotStrategy;
use App\Models\TradingBot;
use App\Models\FuturesTradingBot;
use App\Services\StrategyFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StrategyController extends Controller
{
    /**
     * Display a listing of strategies
     */
    public function index()
    {
        $strategies = TradingStrategy::with('parameters')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        return view('strategies.index', compact('strategies'));
    }

    /**
     * Show the form for creating a new strategy
     */
    public function create()
    {
        $typeOptions = TradingStrategy::getTypeOptions();
        $marketTypeOptions = TradingStrategy::getMarketTypeOptions();
        $parameterTypeOptions = StrategyParameter::getTypeOptions();
        $timeframeOptions = ['1m', '5m', '15m', '30m', '1h', '4h', '1d'];
        $indicatorOptions = ['sma', 'ema', 'rsi', 'macd', 'bollinger_bands', 'atr', 'volume', 'momentum'];

        return view('strategies.create', compact(
            'typeOptions', 
            'marketTypeOptions', 
            'parameterTypeOptions',
            'timeframeOptions',
            'indicatorOptions'
        ));
    }

    /**
     * Store a newly created strategy
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:trading_strategies,name',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:' . implode(',', array_keys(TradingStrategy::getTypeOptions())),
            'market_type' => 'required|in:spot,futures,both',
            'supported_timeframes' => 'nullable|array',
            'supported_timeframes.*' => 'in:1m,5m,15m,30m,1h,4h,1d',
            'required_indicators' => 'nullable|array',
            'required_indicators.*' => 'string|max:50',
            'parameters' => 'nullable|array',
            'parameters.*.parameter_name' => 'required|string|max:100',
            'parameters.*.parameter_type' => 'required|in:' . implode(',', array_keys(StrategyParameter::getTypeOptions())),
            'parameters.*.description' => 'nullable|string|max:500',
            'parameters.*.default_value' => 'nullable',
            'parameters.*.min_value' => 'nullable|numeric',
            'parameters.*.max_value' => 'nullable|numeric',
            'parameters.*.options' => 'nullable|array',
            'parameters.*.is_required' => 'boolean',
        ]);

        try {
            $strategy = StrategyFactory::createCustomStrategy([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'market_type' => $request->market_type,
                'default_parameters' => $request->default_parameters ?? [],
                'required_indicators' => $request->required_indicators ?? [],
                'supported_timeframes' => $request->supported_timeframes ?? [],
                'parameters' => $request->parameters ?? []
            ], Auth::id());

            return redirect()->route('strategies.index')
                ->with('success', "Strategy '{$strategy->name}' created successfully");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create strategy: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified strategy
     */
    public function show(TradingStrategy $strategy)
    {
        $strategy->load('parameters');
        
        // Get bots using this strategy
        $botStrategies = BotStrategy::with(['bot'])
            ->where('strategy_id', $strategy->id)
            ->where('is_active', true)
            ->get();

        return view('strategies.show', compact('strategy', 'botStrategies'));
    }

    /**
     * Show the form for editing the specified strategy
     */
    public function edit(TradingStrategy $strategy)
    {
        // Only allow editing of user-created strategies
        if ($strategy->is_system) {
            return back()->with('error', 'System strategies cannot be edited');
        }

        if ($strategy->created_by !== Auth::id()) {
            abort(403, 'You can only edit your own strategies');
        }

        $strategy->load('parameters');
        $typeOptions = TradingStrategy::getTypeOptions();
        $marketTypeOptions = TradingStrategy::getMarketTypeOptions();
        $parameterTypeOptions = StrategyParameter::getTypeOptions();
        $timeframeOptions = ['1m', '5m', '15m', '30m', '1h', '4h', '1d'];
        $indicatorOptions = ['sma', 'ema', 'rsi', 'macd', 'bollinger_bands', 'atr', 'volume', 'momentum'];

        return view('strategies.edit', compact(
            'strategy',
            'typeOptions', 
            'marketTypeOptions', 
            'parameterTypeOptions',
            'timeframeOptions',
            'indicatorOptions'
        ));
    }

    /**
     * Update the specified strategy
     */
    public function update(Request $request, TradingStrategy $strategy)
    {
        // Only allow editing of user-created strategies
        if ($strategy->is_system) {
            return back()->with('error', 'System strategies cannot be edited');
        }

        if ($strategy->created_by !== Auth::id()) {
            abort(403, 'You can only edit your own strategies');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:trading_strategies,name,' . $strategy->id,
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:' . implode(',', array_keys(TradingStrategy::getTypeOptions())),
            'market_type' => 'required|in:spot,futures,both',
            'supported_timeframes' => 'nullable|array',
            'supported_timeframes.*' => 'in:1m,5m,15m,30m,1h,4h,1d',
            'required_indicators' => 'nullable|array',
            'required_indicators.*' => 'string|max:50',
            'is_active' => 'boolean',
        ]);

        try {
            $strategy->update([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'market_type' => $request->market_type,
                'supported_timeframes' => $request->supported_timeframes ?? [],
                'required_indicators' => $request->required_indicators ?? [],
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()->route('strategies.show', $strategy)
                ->with('success', "Strategy '{$strategy->name}' updated successfully");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update strategy: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified strategy
     */
    public function destroy(TradingStrategy $strategy)
    {
        // Only allow deletion of user-created strategies
        if ($strategy->is_system) {
            return back()->with('error', 'System strategies cannot be deleted');
        }

        if ($strategy->created_by !== Auth::id()) {
            abort(403, 'You can only delete your own strategies');
        }

        // Check if strategy is being used by any bots
        $botCount = BotStrategy::where('strategy_id', $strategy->id)->count();
        if ($botCount > 0) {
            return back()->with('error', "Cannot delete strategy. It is currently being used by {$botCount} bot(s). Please detach it from all bots first.");
        }

        try {
            $strategy->delete();
            return redirect()->route('strategies.index')
                ->with('success', "Strategy '{$strategy->name}' deleted successfully");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete strategy: ' . $e->getMessage());
        }
    }

    /**
     * Show strategy selection for a bot
     */
    public function selectForBot(Request $request)
    {
        $botId = $request->get('bot_id');
        $botType = $request->get('bot_type', 'futures'); // 'spot' or 'futures'

        if (!$botId) {
            return back()->with('error', 'Bot ID is required');
        }

        // Get the bot
        $bot = $botType === 'futures' ? 
            FuturesTradingBot::find($botId) : 
            TradingBot::find($botId);

        if (!$bot || $bot->user_id !== Auth::id()) {
            return back()->with('error', 'Bot not found or access denied');
        }

        // Get available strategies for this bot
        $marketType = $botType === 'futures' ? 'futures' : 'spot';
        $strategies = StrategyFactory::getAvailableStrategies($marketType);

        // Get current strategies attached to this bot
        $currentStrategies = $bot->activeStrategies()->get();

        return view('strategies.select-for-bot', compact('bot', 'strategies', 'currentStrategies', 'botType'));
    }

    /**
     * Attach strategy to bot
     */
    public function attachToBot(Request $request)
    {
        $request->validate([
            'bot_id' => 'required|integer',
            'bot_type' => 'required|in:spot,futures',
            'strategy_id' => 'required|exists:trading_strategies,id',
            'parameters' => 'nullable|array',
            'priority' => 'integer|min:1|max:10',
        ]);

        try {
            // Get the bot
            $bot = $request->bot_type === 'futures' ? 
                FuturesTradingBot::find($request->bot_id) : 
                TradingBot::find($request->bot_id);

            if (!$bot || $bot->user_id !== Auth::id()) {
                return back()->with('error', 'Bot not found or access denied');
            }

            // Get the strategy
            $strategy = TradingStrategy::find($request->strategy_id);
            if (!$strategy) {
                return back()->with('error', 'Strategy not found');
            }

            // Check if already attached
            $existing = BotStrategy::where('strategy_id', $strategy->id)
                ->where('bot_type', $request->bot_type === 'futures' ? 'App\Models\FuturesTradingBot' : 'App\Models\TradingBot')
                ->where('bot_id', $bot->id)
                ->first();

            if ($existing) {
                return back()->with('error', 'Strategy is already attached to this bot');
            }

            // Attach strategy
            $botStrategy = StrategyFactory::attachStrategyToBot(
                $bot, 
                $strategy->id, 
                $request->parameters ?? [], 
                $request->priority ?? 1
            );

            $botTypeName = $request->bot_type === 'futures' ? 'Futures' : 'Spot';
            return back()->with('success', "Strategy '{$strategy->name}' attached to {$botTypeName} bot '{$bot->name}' successfully");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to attach strategy: ' . $e->getMessage());
        }
    }

    /**
     * Detach strategy from bot
     */
    public function detachFromBot(Request $request)
    {
        $request->validate([
            'bot_id' => 'required|integer',
            'bot_type' => 'required|in:spot,futures',
            'strategy_id' => 'required|exists:trading_strategies,id',
        ]);

        try {
            // Get the bot
            $bot = $request->bot_type === 'futures' ? 
                FuturesTradingBot::find($request->bot_id) : 
                TradingBot::find($request->bot_id);

            if (!$bot || $bot->user_id !== Auth::id()) {
                return back()->with('error', 'Bot not found or access denied');
            }

            // Detach strategy
            $success = StrategyFactory::detachStrategyFromBot($bot, $request->strategy_id);

            if ($success) {
                $strategy = TradingStrategy::find($request->strategy_id);
                $botTypeName = $request->bot_type === 'futures' ? 'Futures' : 'Spot';
                return back()->with('success', "Strategy '{$strategy->name}' detached from {$botTypeName} bot '{$bot->name}' successfully");
            } else {
                return back()->with('error', 'Strategy was not attached to this bot');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to detach strategy: ' . $e->getMessage());
        }
    }

    /**
     * Update strategy parameters for a bot
     */
    public function updateBotStrategyParameters(Request $request)
    {
        $request->validate([
            'bot_id' => 'required|integer',
            'bot_type' => 'required|in:spot,futures',
            'strategy_id' => 'required|exists:trading_strategies,id',
            'parameters' => 'nullable|array',
        ]);

        try {
            // Get the bot
            $bot = $request->bot_type === 'futures' ? 
                FuturesTradingBot::find($request->bot_id) : 
                TradingBot::find($request->bot_id);

            if (!$bot || $bot->user_id !== Auth::id()) {
                return back()->with('error', 'Bot not found or access denied');
            }

            // Update parameters
            $success = StrategyFactory::updateBotStrategyParameters($bot, $request->strategy_id, $request->parameters ?? []);

            if ($success) {
                $strategy = TradingStrategy::find($request->strategy_id);
                $botTypeName = $request->bot_type === 'futures' ? 'Futures' : 'Spot';
                return back()->with('success', "Strategy parameters updated for {$botTypeName} bot '{$bot->name}'");
            } else {
                return back()->with('error', 'Strategy is not attached to this bot');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update strategy parameters: ' . $e->getMessage());
        }
    }

    /**
     * Get strategy recommendations for a bot
     */
    public function getRecommendations(Request $request)
    {
        $request->validate([
            'bot_id' => 'required|integer',
            'bot_type' => 'required|in:spot,futures',
        ]);

        try {
            // Get the bot
            $bot = $request->bot_type === 'futures' ? 
                FuturesTradingBot::find($request->bot_id) : 
                TradingBot::find($request->bot_id);

            if (!$bot || $bot->user_id !== Auth::id()) {
                return response()->json(['error' => 'Bot not found or access denied'], 403);
            }

            $recommendations = StrategyFactory::getStrategyRecommendations($bot);

            return response()->json([
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get recommendations: ' . $e->getMessage()], 500);
        }
    }
}
