# Futures Trading Bot System

A comprehensive futures trading bot system built with Laravel that analyzes 1-minute, 5-minute, and 15-minute charts to make automated trading decisions on cryptocurrency futures markets.

## Features

### 🤖 Automated Trading
- **Multi-timeframe Analysis**: Analyzes 1m, 5m, and 15m charts simultaneously
- **Smart Money Concepts**: Uses advanced technical analysis for signal generation
- **Risk Management**: Configurable stop-loss, take-profit, and position sizing
- **Leverage Support**: Configurable leverage from 1x to 100x
- **Margin Types**: Support for both isolated and cross margin

### 📊 Trading Features
- **Long/Short Positions**: Support for both long and short trading
- **Position Side Control**: Restrict to long-only, short-only, or both
- **Real-time PnL Tracking**: Live profit/loss monitoring
- **Signal Confluence**: Requires multiple timeframe confirmation
- **Risk/Reward Validation**: Minimum 1.5:1 risk/reward ratio

### 🔧 Configuration Options
- **Risk Management**: Configurable risk per trade (0.1% - 10%)
- **Position Sizing**: Maximum position size limits
- **Stop Loss**: Percentage-based stop loss (0.1% - 10%)
- **Take Profit**: Percentage-based take profit (0.1% - 20%)
- **Exchange Support**: Binance, KuCoin, and other major exchanges

## Installation

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd laravel-trading-bot
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Set up environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**:
   ```bash
   php artisan migrate
   ```

5. **Set up API keys**:
   - Add your exchange API keys through the web interface
   - Ensure futures trading permissions are enabled

## Usage

### Web Interface

1. **Create a Futures Bot**:
   - Navigate to `/futures-bots/create`
   - Configure bot parameters
   - Select timeframes (1m, 5m, 15m)
   - Set risk management parameters

2. **Monitor Performance**:
   - View real-time PnL
   - Track win rate and performance metrics
   - Monitor open positions

3. **Manual Controls**:
   - Run bot manually
   - Toggle bot activation
   - Close positions manually

### Command Line

**Run all active bots**:
```bash
php artisan futures:run --all
```

**Run specific bot**:
```bash
php artisan futures:run --bot=1
```

### Automated Execution

Set up a cron job to run bots automatically:
```bash
# Run every minute
* * * * * cd /path/to/laravel-trading-bot && php artisan futures:run --all >> /dev/null 2>&1
```

## Configuration

### Bot Settings

| Parameter | Description | Range | Default |
|-----------|-------------|-------|---------|
| `risk_percentage` | Risk per trade | 0.1% - 10% | 1% |
| `max_position_size` | Maximum position size | 0.001+ | 0.01 |
| `leverage` | Trading leverage | 1x - 100x | 10x |
| `margin_type` | Margin type | isolated/cross | isolated |
| `position_side` | Trading direction | long/short/both | both |
| `stop_loss_percentage` | Stop loss percentage | 0.1% - 10% | 2% |
| `take_profit_percentage` | Take profit percentage | 0.1% - 20% | 4% |

### Timeframes

The bot analyzes three timeframes:
- **1-minute (1m)**: Short-term momentum
- **5-minute (5m)**: Medium-term trends
- **15-minute (15m)**: Longer-term structure

### Signal Requirements

For a trade to be executed:
1. **Minimum Strength**: Signal strength ≥ 0.6
2. **Timeframe Confluence**: At least 2 timeframes showing same signal
3. **Risk/Reward**: Minimum 1.5:1 ratio
4. **Position Limits**: Within maximum position size

## Risk Management

### Position Sizing
```
Position Size = (Account Balance × Risk Percentage × Leverage) ÷ Current Price
```

### Stop Loss Calculation
- **Long**: Entry Price × (1 - Stop Loss Percentage)
- **Short**: Entry Price × (1 + Stop Loss Percentage)

### Take Profit Calculation
- **Long**: Entry Price × (1 + Take Profit Percentage)
- **Short**: Entry Price × (1 - Take Profit Percentage)

## Database Schema

### Futures Trading Bots
- Bot configuration and settings
- Risk management parameters
- Performance tracking

### Futures Trades
- Individual trade records
- Entry/exit prices
- PnL calculations
- Order details

### Futures Signals
- Trading signals generated
- Signal strength and confluence
- Execution status

## API Integration

### Supported Exchanges
- **Binance**: Full futures support
- **KuCoin**: Full futures support
- **Other exchanges**: Extensible architecture

### Required Permissions
- Futures trading enabled
- API key with trading permissions
- Sufficient balance for margin

## Monitoring & Logging

### Performance Metrics
- Total PnL
- Unrealized PnL
- Win rate percentage
- Number of trades
- Average trade duration

### Logging
- Detailed execution logs
- Error tracking
- Signal generation logs
- Order placement logs

## Safety Features

### Risk Controls
- Maximum position size limits
- Percentage-based risk per trade
- Automatic stop-loss execution
- Leverage limits

### Error Handling
- Graceful error recovery
- Failed order handling
- Network timeout handling
- Exchange API error management

## Development

### Adding New Exchanges

1. **Extend ExchangeService**:
   ```php
   // Add new exchange methods
   private function getNewExchangeFuturesBalance()
   private function placeNewExchangeFuturesOrder()
   private function closeNewExchangeFuturesPosition()
   ```

2. **Update exchange switch statements**:
   ```php
   case 'newexchange':
       return $this->getNewExchangeFuturesBalance();
   ```

### Custom Strategies

1. **Extend SmartMoneyConceptsService**:
   ```php
   // Add custom signal generation logic
   public function generateCustomSignals($currentPrice)
   ```

2. **Update FuturesTradingBotService**:
   ```php
   // Integrate custom strategy
   $signals = $this->customStrategyService->generateSignals($currentPrice);
   ```

## Troubleshooting

### Common Issues

1. **Insufficient Balance**:
   - Check futures account balance
   - Verify leverage settings
   - Ensure sufficient margin

2. **API Errors**:
   - Verify API key permissions
   - Check exchange connectivity
   - Review API rate limits

3. **No Signals Generated**:
   - Check timeframe data availability
   - Verify signal strength thresholds
   - Review market conditions

### Debug Mode

Enable detailed logging:
```php
// In .env
LOG_LEVEL=debug
```

## Disclaimer

⚠️ **Risk Warning**: Futures trading involves substantial risk of loss and is not suitable for all investors. The high degree of leverage can work against you as well as for you. Before deciding to trade futures, you should carefully consider your investment objectives, level of experience, and risk appetite.

This software is for educational and research purposes. Use at your own risk.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the logs for error details
