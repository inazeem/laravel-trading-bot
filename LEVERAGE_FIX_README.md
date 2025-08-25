# Binance Futures Leverage Fix

## Problem Description

The trading bot was configured with 20x leverage in the settings, but positions were always opening with 5x leverage on Binance. This was happening because the bot was not properly setting the leverage on the Binance Futures API before placing orders.

## Root Cause

In Binance Futures API, leverage must be set separately before placing orders. The leverage setting is not part of the order placement parameters. The bot was passing the leverage parameter to the order placement method but not actually setting it on the exchange.

## Solution Implemented

### 1. Added Leverage Setting Method

Added `setBinanceFuturesLeverage()` method in `ExchangeService.php`:

```php
private function setBinanceFuturesLeverage($symbol, $leverage, $marginType = 'isolated')
{
    // Uses Binance API endpoint: /fapi/v1/leverage
    // Sets leverage for the specific symbol before placing orders
}
```

### 2. Added Margin Type Setting Method

Added `setBinanceFuturesMarginType()` method in `ExchangeService.php`:

```php
private function setBinanceFuturesMarginType($symbol, $marginType)
{
    // Uses Binance API endpoint: /fapi/v1/marginType
    // Sets margin type (isolated/cross) for the specific symbol
}
```

### 3. Modified Order Placement

Updated `placeBinanceFuturesOrder()` method to:

1. Set margin type first
2. Set leverage 
3. Wait for settings to take effect
4. Place the order

```php
// Set leverage and margin type before placing order
$marginTypeSet = $this->setBinanceFuturesMarginType($binanceSymbol, $marginType);
$leverageSet = $this->setBinanceFuturesLeverage($binanceSymbol, $leverage, $marginType);

// Wait a moment for settings to take effect
sleep(1);

// Then place the order...
```

## Binance API Endpoints Used

### Set Leverage
- **Endpoint**: `POST /fapi/v1/leverage`
- **Parameters**: 
  - `symbol`: Trading pair (e.g., BTCUSDT)
  - `leverage`: Leverage value (1-125)
  - `timestamp`: Current timestamp
  - `signature`: HMAC signature

### Set Margin Type
- **Endpoint**: `POST /fapi/v1/marginType`
- **Parameters**:
  - `symbol`: Trading pair (e.g., BTCUSDT)
  - `marginType`: ISOLATED or CROSSED
  - `timestamp`: Current timestamp
  - `signature`: HMAC signature

## Testing

Created `test_leverage_setting.php` to verify the leverage setting functionality:

```bash
php test_leverage_setting.php
```

This script will:
1. Find an active Binance API key
2. Test setting leverage to 20x
3. Test setting margin type to isolated
4. Log the results

## Verification

To verify the fix is working:

1. **Check Logs**: Look for these log messages:
   ```
   ✅ Leverage set successfully for BTCUSDT: 20x
   ✅ Margin type set successfully for BTCUSDT: isolated
   ```

2. **Check Binance**: After placing an order, verify in Binance that:
   - The position shows 20x leverage (not 5x)
   - The margin type is set to isolated

3. **Monitor Orders**: The bot will now log leverage setting attempts before each order placement.

## Important Notes

1. **API Permissions**: Your Binance API key must have futures trading permissions enabled.

2. **Rate Limits**: Binance has rate limits for leverage/margin type changes. The bot includes a 1-second delay between setting leverage and placing orders.

3. **Error Handling**: If leverage setting fails, the bot will log a warning but continue with order placement. This ensures the bot doesn't stop completely if there are temporary API issues.

4. **Symbol Format**: The bot automatically converts symbol format (e.g., BTC-USDT → BTCUSDT) for Binance API compatibility.

## Future Improvements

1. **Caching**: Consider caching leverage settings to avoid setting them repeatedly for the same symbol.

2. **Validation**: Add validation to ensure leverage is within acceptable ranges for each symbol.

3. **Retry Logic**: Implement retry logic for failed leverage setting attempts.

4. **Monitoring**: Add monitoring to track when leverage settings fail and alert administrators.
