# Smart Money Concepts Trading Bot

A Laravel-based automated trading bot that implements Smart Money Concepts (SMC) strategy for cryptocurrency trading on KuCoin and Binance exchanges.

## Features

### 🎯 Smart Money Concepts Strategy
- **Break of Structure (BOS)**: Detects when price breaks above/below key swing levels
- **Change of Character (CHoCH)**: Identifies trend reversals and market structure changes
- **Order Blocks**: Recognizes institutional order zones for entry/exit points
- **Fair Value Gaps**: Identifies price inefficiencies and gaps
- **Equal Highs/Lows**: Detects key support/resistance levels
- **Support/Resistance Levels**: Dynamic level identification

### 📊 Multi-Timeframe Analysis
- **1 Hour (1H)**: Short-term momentum and intraday opportunities
- **4 Hour (4H)**: Medium-term trend analysis
- **Daily (1D)**: Long-term market structure and major levels

### 🔒 Risk Management
- **Position Sizing**: Automatic calculation based on account balance and risk percentage
- **Stop Loss**: Dynamic placement based on support/resistance levels
- **Take Profit**: Calculated using risk/reward ratios (minimum 1.5:1)
- **Maximum Position Size**: Configurable limits to prevent over-exposure

### 🏦 Exchange Support
- **KuCoin**: Full API integration with spot trading
- **Binance**: Full API integration with spot trading
- **Real-time Data**: Live price feeds and candlestick data
- **Order Management**: Automated market order execution

### 📈 Performance Tracking
- **Trade History**: Complete record of all trades with P&L
- **Signal Analysis**: Track signal generation and execution
- **Performance Metrics**: Win rate, total profit/loss, risk metrics
- **Real-time Monitoring**: Live status updates and logging

## Installation

### Prerequisites
- Laravel 12.0+
- PHP 8.2+
- MySQL/PostgreSQL database
- Composer

### Setup Steps

1. **Clone and Install Dependencies**
```bash
composer install
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Setup**
```bash
php artisan migrate
php artisan db:seed
```

4. **Build Assets**
```bash
npm run build
```

## Usage

### Web Interface

1. **Access the Application**
   - Navigate to your Laravel application URL
   - Register/Login to access the trading bot interface

2. **Create a Trading Bot**
   - Go to "Trading Bots" section
   - Click "Create New Bot"
   - Fill in the configuration:
     - **Basic Info**: Name, Exchange, Trading Pair
     - **API Keys**: Your exchange API credentials
     - **Risk Settings**: Risk percentage, position limits
     - **Timeframes**: Select 1H, 4H, 1D as needed

3. **Configure API Keys**
   - **KuCoin**: API Key, Secret, and Passphrase
   - **Binance**: API Key and Secret
   - Ensure API keys have trading permissions

4. **Run the Bot**
   - Click "Run" to execute the bot manually
   - Or set up automated scheduling

### Command Line Interface

1. **Run All Active Bots**
```bash
php artisan trading:run --all
```

2. **Run Specific Bot**
```bash
php artisan trading:run {bot_id}
```

3. **Schedule Automated Execution**
Add to your crontab for automated trading:
```bash
# Run every 5 minutes
*/5 * * * * cd /path/to/your/app && php artisan trading:run --all
```

## Strategy Details

### Smart Money Concepts Implementation

#### Break of Structure (BOS)
- **Bullish BOS**: Price breaks above recent swing high
- **Bearish BOS**: Price breaks below recent swing low
- **Signal Strength**: Based on volume and price momentum

#### Change of Character (CHoCH)
- **Bullish CHoCH**: Price breaks above swing high after breaking below swing low
- **Bearish CHoCH**: Price breaks below swing low after breaking above swing high
- **Trend Reversal**: Indicates potential market structure change

#### Order Blocks
- **Bullish Order Blocks**: Areas where price reversed from support
- **Bearish Order Blocks**: Areas where price reversed from resistance
- **Entry Zones**: Used for trade entry and stop loss placement

#### Fair Value Gaps
- **Bullish FVG**: Gap between previous high and next low
- **Bearish FVG**: Gap between previous low and next high
- **Fill Targets**: Price often returns to fill these gaps

### Risk Management Rules

1. **Position Sizing**
   - Risk per trade: 1-5% of account balance (configurable)
   - Maximum position size limits
   - Account for exchange minimums

2. **Stop Loss Placement**
   - Below nearest support for long positions
   - Above nearest resistance for short positions
   - Minimum distance based on volatility

3. **Take Profit Targets**
   - Risk/Reward ratio minimum: 1.5:1
   - Based on resistance levels for longs
   - Based on support levels for shorts

4. **Signal Confluence**
   - Multiple timeframe confirmation required
   - Minimum 2 timeframes showing same signal
   - Signal strength threshold filtering

## Configuration

### Bot Settings

```php
// Example bot configuration
$bot = [
    'name' => 'BTC-USDT SMC Bot',
    'exchange' => 'kucoin',
    'symbol' => 'BTC-USDT',
    'risk_percentage' => 2.0, // 2% risk per trade
    'max_position_size' => 1000, // Maximum position size
    'timeframes' => ['1h', '4h', '1d'],
    'is_active' => true
];
```

### Strategy Parameters

```php
// Smart Money Concepts settings
$strategySettings = [
    'swing_detection_length' => 5, // Bars for swing detection
    'order_block_threshold' => 0.02, // 2% threshold for order blocks
    'fair_value_gap_threshold' => 0.1, // 0.1% for FVG detection
    'equal_levels_threshold' => 0.1, // 0.1% for equal highs/lows
    'minimum_signal_strength' => 0.5, // Minimum signal strength
    'confluence_requirement' => 2, // Minimum timeframe confluence
];
```

## Monitoring and Analytics

### Dashboard Features
- **Real-time Bot Status**: Active, running, error states
- **Performance Metrics**: Total P&L, win rate, trade count
- **Recent Trades**: Latest executed trades with details
- **Signal History**: All generated signals and outcomes
- **Risk Metrics**: Current exposure and risk levels

### Logging and Alerts
- **Comprehensive Logging**: All bot activities logged
- **Error Handling**: Graceful error recovery
- **Performance Alerts**: Notifications for significant events
- **Trade Notifications**: Real-time trade execution alerts

## Security Considerations

### API Key Security
- **Encrypted Storage**: API keys encrypted in database
- **Limited Permissions**: Use API keys with trading permissions only
- **Regular Rotation**: Periodic API key updates recommended
- **IP Restrictions**: Configure exchange API IP restrictions

### Risk Controls
- **Maximum Loss Limits**: Daily/weekly loss limits
- **Position Limits**: Maximum concurrent positions
- **Emergency Stop**: Ability to halt all trading
- **Backup Systems**: Redundant monitoring and controls

## Troubleshooting

### Common Issues

1. **API Connection Errors**
   - Verify API keys and permissions
   - Check network connectivity
   - Ensure exchange API is operational

2. **No Signals Generated**
   - Check market conditions
   - Verify timeframe data availability
   - Review signal strength thresholds

3. **Order Execution Failures**
   - Verify account balance
   - Check minimum order sizes
   - Review exchange trading rules

### Debug Mode
Enable detailed logging for troubleshooting:
```php
// In .env file
LOG_LEVEL=debug
```

## Performance Optimization

### Database Optimization
- **Indexing**: Proper database indexes for queries
- **Partitioning**: Large tables partitioned by date
- **Cleanup**: Regular cleanup of old data

### Memory Management
- **Caching**: Cache frequently accessed data
- **Batch Processing**: Process signals in batches
- **Resource Limits**: Monitor memory usage

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Disclaimer

This trading bot is for educational and research purposes. Cryptocurrency trading involves significant risk of loss. Always:
- Test thoroughly on paper trading first
- Start with small amounts
- Monitor performance closely
- Never risk more than you can afford to lose

## Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the logs for error details

---

**Note**: This bot implements advanced trading strategies. Ensure you understand the risks involved in automated trading before using this system.
