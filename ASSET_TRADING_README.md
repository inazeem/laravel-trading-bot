# Asset Trading System with Real Exchange Integration

This system allows users to trade cryptocurrencies and other assets using real exchange APIs (KuCoin, Binance, etc.) instead of simulated trading.

## Features

- ✅ **Real Exchange Integration**: Connect to KuCoin, Binance, and other exchanges via API
- ✅ **Live Asset Data**: Get real-time trading pairs and prices from exchanges
- ✅ **Actual Trading**: Place real buy/sell orders on exchanges
- ✅ **Portfolio Management**: Track holdings worth more than $10
- ✅ **Transaction History**: Complete audit trail of all trades
- ✅ **Multi-Exchange Support**: Trade across multiple exchanges simultaneously

## Setup Instructions

### 1. Add Exchange API Keys

1. Navigate to **API Keys** in the main menu
2. Click **Add New API Key**
3. Select your exchange (KuCoin, Binance, etc.)
4. Enter your API credentials:
   - **API Key**: Your exchange API key
   - **Secret Key**: Your exchange secret key
   - **Passphrase**: Required for KuCoin (optional for others)
   - **Name**: Give your API key a descriptive name

### 2. Sync Assets from Exchange

1. Go to **Asset Trading** page
2. Select your API key from the dropdown
3. Click **Sync Assets** to import all available trading pairs
4. Click **Update Prices** to get current market prices

### 3. Start Trading

1. Browse available assets on the **Asset Trading** page
2. Click **Buy** or **Sell** on any asset
3. Select your API key for the transaction
4. Enter quantity and price
5. Confirm the trade

## Supported Exchanges

### KuCoin
- **API Endpoint**: https://api.kucoin.com
- **Required Permissions**: Trading, Reading
- **Features**: Market/Limit orders, Real-time prices

### Binance
- **API Endpoint**: https://api.binance.com
- **Required Permissions**: Spot Trading, Reading
- **Features**: Market/Limit orders, Real-time prices

## API Key Permissions

Make sure your API keys have the following permissions:

### KuCoin
- ✅ **Trading**: Enable spot trading
- ✅ **Reading**: View account information
- ❌ **Withdrawal**: Not required (disabled for security)

### Binance
- ✅ **Spot Trading**: Enable spot trading
- ✅ **Reading**: View account information
- ❌ **Withdrawal**: Not required (disabled for security)

## Commands

### Manual Asset Sync
```bash
# Sync all assets from all exchanges
php artisan assets:sync

# Sync from specific exchange
php artisan assets:sync --exchange=kucoin

# Sync for specific user
php artisan assets:sync --user=1
```

### Scheduled Sync (Recommended)
Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync assets every hour
    $schedule->command('assets:sync')->hourly();
}
```

## Trading Features

### Buy Orders
- **Market Orders**: Buy at current market price
- **Limit Orders**: Buy at specified price
- **Real-time Execution**: Orders placed directly on exchange
- **Transaction Tracking**: All orders logged with exchange order IDs

### Sell Orders
- **Market Orders**: Sell at current market price
- **Limit Orders**: Sell at specified price
- **Quantity Validation**: Prevents selling more than you own
- **Portfolio Updates**: Automatic holding calculations

### Portfolio Management
- **Assets > $10 Filter**: Only shows significant holdings
- **Profit/Loss Tracking**: Real-time P&L calculations
- **Average Buy Price**: Automatic calculation for cost basis
- **Current Value**: Live market value updates

## Security Features

- **API Key Validation**: Verifies API keys before trading
- **User Isolation**: Users can only access their own API keys
- **Transaction Logging**: Complete audit trail of all trades
- **Error Handling**: Graceful handling of API failures
- **No Withdrawal Permissions**: API keys configured for trading only

## Error Handling

The system handles various error scenarios:

- **API Connection Issues**: Retry mechanisms and user notifications
- **Insufficient Balance**: Validation before placing orders
- **Invalid Orders**: Exchange-specific error messages
- **Network Timeouts**: Automatic retries with exponential backoff

## Database Schema

### Assets Table
```sql
- id (Primary Key)
- symbol (Unique trading pair symbol)
- name (Asset name)
- current_price (Latest market price)
- type (crypto, stock, etc.)
- is_active (Whether trading is enabled)
```

### Asset Transactions Table
```sql
- id (Primary Key)
- user_id (Foreign Key to users)
- asset_id (Foreign Key to assets)
- type (buy/sell)
- quantity (Amount traded)
- price_per_unit (Price at execution)
- total_amount (Total transaction value)
- status (completed, pending, cancelled)
- exchange_order_id (Exchange order ID)
- exchange_response (Full exchange response)
```

### User Asset Holdings Table
```sql
- id (Primary Key)
- user_id (Foreign Key to users)
- asset_id (Foreign Key to assets)
- quantity (Current holdings)
- average_buy_price (Weighted average cost)
- total_invested (Total money invested)
```

## Troubleshooting

### Common Issues

1. **"No active API keys found"**
   - Add API keys in the API Keys section
   - Ensure API keys are marked as active
   - Verify API key permissions

2. **"Transaction failed"**
   - Check API key permissions
   - Verify sufficient balance on exchange
   - Ensure trading pair is active

3. **"Insufficient quantity to sell"**
   - Check your current holdings
   - Verify the asset is in your portfolio
   - Ensure you're not trying to sell more than you own

### API Key Testing

Use the **Test Connection** feature in API Keys to verify:
- API key validity
- Required permissions
- Network connectivity
- Exchange API status

## Best Practices

1. **Regular Price Updates**: Run price updates frequently for accurate portfolio values
2. **API Key Security**: Never share API keys, use read-only keys when possible
3. **Portfolio Monitoring**: Regularly check your portfolio for significant changes
4. **Transaction Review**: Review transaction history for accuracy
5. **Backup API Keys**: Consider having backup API keys for critical exchanges

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review transaction logs for error details
3. Verify API key permissions and connectivity
4. Contact support with specific error messages

## Future Enhancements

- [ ] Additional exchange support (Coinbase, Kraken, etc.)
- [ ] Advanced order types (stop-loss, take-profit)
- [ ] Portfolio analytics and charts
- [ ] Automated trading strategies
- [ ] Mobile app integration
- [ ] Real-time price alerts
