# Trading Strategy Model System

This document explains the new strategy model system that allows you to attach different trading strategies to your bots and have them take instructions from these strategies.

## 🏗️ Architecture Overview

The strategy system consists of several key components:

### Models
- **TradingStrategy**: Defines different trading strategies (trend following, mean reversion, etc.)
- **StrategyParameter**: Configurable parameters for each strategy
- **BotStrategy**: Links strategies to bots with custom parameters

### Services
- **StrategyService**: Executes strategy logic and returns trading instructions
- **StrategyFactory**: Creates and manages strategies, handles bot attachments

### Database Tables
- `trading_strategies`: Strategy definitions
- `strategy_parameters`: Strategy parameter configurations
- `bot_strategies`: Bot-strategy relationships with custom parameters

## 🚀 Quick Start

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Setup Default Strategies
```bash
php artisan trading:setup-strategies
```

### 3. Attach Strategy to Bot
```bash
php artisan trading:attach-strategy <bot_id> <strategy_id>
```

## 📊 Available Strategies

### System Strategies (Pre-built)

1. **Trend Following**
   - Follows market trends using moving averages and RSI
   - Parameters: signal_strength, trend_period, rsi_oversold, rsi_overbought
   - Timeframes: 1h, 4h, 1d
   - Markets: Both spot and futures

2. **Mean Reversion**
   - Trades against the trend when price deviates from mean
   - Parameters: bollinger_period, bollinger_std, rsi_oversold, rsi_overbought
   - Timeframes: 1h, 4h, 1d
   - Markets: Both spot and futures

3. **Momentum Trading**
   - Trades in the direction of strong momentum with volume confirmation
   - Parameters: momentum_period, volume_threshold, signal_strength
   - Timeframes: 15m, 1h, 4h
   - Markets: Both spot and futures

4. **Scalping Strategy**
   - Quick trades with small profits using fast EMAs
   - Parameters: ema_fast, ema_slow, profit_target
   - Timeframes: 1m, 5m, 15m
   - Markets: Futures only

5. **Swing Trading**
   - Medium-term trades based on swing highs and lows
   - Parameters: swing_period, atr_period, atr_multiplier
   - Timeframes: 4h, 1d
   - Markets: Both spot and futures

6. **Grid Trading**
   - Places buy and sell orders at regular intervals
   - Parameters: grid_size, grid_levels, profit_per_grid
   - Timeframes: 1m, 5m, 15m
   - Markets: Futures only

7. **Dollar Cost Averaging (DCA)**
   - Systematic buying at regular intervals
   - Parameters: dca_interval, dca_amount, max_dca_periods
   - Timeframes: 1d
   - Markets: Spot only

## 🔧 Usage Examples

### Attaching a Strategy to a Bot

```php
use App\Services\StrategyFactory;
use App\Models\FuturesTradingBot;

// Get a bot
$bot = FuturesTradingBot::find(1);

// Get a strategy
$strategy = TradingStrategy::where('name', 'Trend Following')->first();

// Attach with custom parameters
$botStrategy = StrategyFactory::attachStrategyToBot($bot, $strategy->id, [
    'signal_strength' => 75,
    'trend_period' => 25,
    'rsi_oversold' => 25,
    'rsi_overbought' => 75
], 1); // Priority 1
```

### Getting Strategy Recommendations

```php
use App\Services\StrategyFactory;

$bot = FuturesTradingBot::find(1);
$recommendations = StrategyFactory::getStrategyRecommendations($bot);

foreach ($recommendations as $rec) {
    echo "Strategy: {$rec['strategy']->name}\n";
    echo "Score: {$rec['score']}\n";
    echo "Reasons: " . implode(', ', $rec['reasons']) . "\n\n";
}
```

### Executing Strategy Logic

```php
use App\Services\StrategyService;

$bot = FuturesTradingBot::find(1);
$strategyService = new StrategyService();

$results = $strategyService->executeStrategy($bot);

foreach ($results as $result) {
    if ($result['success']) {
        $strategyResult = $result['result'];
        echo "Strategy: {$result['strategy']}\n";
        echo "Action: {$strategyResult['action']}\n";
        echo "Confidence: {$strategyResult['confidence']}%\n";
        echo "Reason: {$strategyResult['reason']}\n\n";
    }
}
```

## 🎯 How It Works

### 1. Strategy Execution Flow

1. **Bot runs** → Calls `StrategyService::executeStrategy()`
2. **Strategy Service** → Gets active strategies for the bot
3. **For each strategy** → Executes strategy-specific logic
4. **Returns results** → Action (buy/sell/hold), confidence, reason
5. **Bot decides** → Whether to place trade based on strategy signals

### 2. Strategy Logic

Each strategy implements its own logic:

```php
// Example: Trend Following Strategy
private function executeTrendFollowingStrategy(array $params, array $marketData, $bot): array
{
    $trendDirection = $this->calculateTrendDirection($marketData, $params['trend_period']);
    $rsi = $this->calculateRSI($marketData, 14);
    
    $action = 'hold';
    $confidence = 0;
    
    if ($trendDirection === 'up' && $rsi < $params['rsi_overbought']) {
        $action = 'buy';
        $confidence = min(100, $params['signal_strength'] + ($params['rsi_overbought'] - $rsi));
    }
    
    return [
        'action' => $action,
        'confidence' => $confidence,
        'reason' => "Trend: {$trendDirection}, RSI: {$rsi}",
        'parameters' => [
            'trend_direction' => $trendDirection,
            'rsi' => $rsi
        ]
    ];
}
```

### 3. Bot Integration

The bot service now uses strategy results:

```php
// Execute strategy logic
$strategyResults = $this->strategyService->executeStrategy($this->bot);

// Check for buy signals
foreach ($strategyResults as $result) {
    if ($result['success'] && $result['result']['action'] === 'buy' && $result['result']['confidence'] >= 70) {
        // Place trade with strategy-based parameters
        $this->placeNewTrade($currentPrice, $strategyResults);
        break;
    }
}
```

## 🛠️ Creating Custom Strategies

### 1. Create Strategy via Factory

```php
use App\Services\StrategyFactory;

$strategy = StrategyFactory::createCustomStrategy([
    'name' => 'My Custom Strategy',
    'description' => 'A custom trading strategy',
    'type' => 'custom',
    'market_type' => 'futures',
    'default_parameters' => [
        'custom_param1' => 10,
        'custom_param2' => 0.5
    ],
    'required_indicators' => ['sma', 'rsi'],
    'supported_timeframes' => ['1h', '4h'],
    'parameters' => [
        [
            'parameter_name' => 'custom_param1',
            'parameter_type' => 'integer',
            'default_value' => 10,
            'min_value' => 5,
            'max_value' => 20,
            'is_required' => true,
            'description' => 'Custom parameter 1'
        ]
    ]
], $userId);
```

### 2. Implement Strategy Logic

Add your custom strategy logic to `StrategyService::executeCustomStrategy()`:

```php
private function executeCustomStrategy(array $params, array $marketData, $bot): array
{
    // Your custom logic here
    $customParam1 = $params['custom_param1'] ?? 10;
    
    // Calculate indicators
    $sma = $this->calculateSMA($marketData, $customParam1);
    $rsi = $this->calculateRSI($marketData, 14);
    
    // Your trading logic
    $action = 'hold';
    $confidence = 0;
    
    if ($sma > $marketData['current_price'] && $rsi < 30) {
        $action = 'buy';
        $confidence = 80;
    }
    
    return [
        'action' => $action,
        'confidence' => $confidence,
        'reason' => "Custom logic: SMA={$sma}, RSI={$rsi}",
        'parameters' => [
            'sma' => $sma,
            'rsi' => $rsi
        ]
    ];
}
```

## 📈 Strategy Parameters

### Parameter Types
- **integer**: Whole numbers
- **float**: Decimal numbers
- **boolean**: True/false values
- **string**: Text values
- **array**: Lists of values
- **select**: Single choice from options
- **multiselect**: Multiple choices from options

### Parameter Validation
- **Required parameters**: Must be provided
- **Min/Max values**: For numeric parameters
- **Options**: For select/multiselect parameters
- **Type validation**: Ensures correct data types

## 🔄 Bot Scheduling

Remember that futures bots should be scheduled to run every minute [[memory:7043401]]:

```php
// In your scheduler
$schedule->command('trading:run-futures-bot {bot_id}')
    ->everyMinute()
    ->withoutOverlapping();
```

## 🎛️ Management Commands

### Setup Strategies
```bash
php artisan trading:setup-strategies
php artisan trading:setup-strategies --force  # Recreate existing
```

### Attach Strategy to Bot
```bash
php artisan trading:attach-strategy 1 2  # Bot ID 1, Strategy ID 2
php artisan trading:attach-strategy 1 2 --parameters='{"signal_strength":75}' --priority=1
```

### List Available Strategies
```php
use App\Services\StrategyFactory;

$strategies = StrategyFactory::getAvailableStrategies('futures');
foreach ($strategies as $strategy) {
    echo "{$strategy->id}: {$strategy->name} - {$strategy->description}\n";
}
```

## 🚨 Important Notes

1. **Strategy Priority**: When multiple strategies are attached, they run in priority order
2. **Parameter Merging**: Bot-specific parameters override strategy defaults
3. **Compatibility**: Strategies check market type and timeframe compatibility
4. **Error Handling**: Failed strategies don't stop other strategies from running
5. **Performance**: Strategies are cached and optimized for frequent execution

## 🔮 Future Enhancements

- **Strategy Backtesting**: Test strategies against historical data
- **Strategy Performance Tracking**: Monitor strategy success rates
- **Dynamic Strategy Switching**: Automatically switch strategies based on market conditions
- **Strategy Combinations**: Combine multiple strategies for better signals
- **Machine Learning Integration**: AI-powered strategy optimization

This strategy system provides a flexible foundation for implementing various trading approaches while maintaining clean separation between strategy logic and bot execution.
