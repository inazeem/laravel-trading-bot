<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('⚡ Create Scalping Bot') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">


        <form action="{{ route('scalping-bots.store') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Basic Configuration -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">🔧 Basic Configuration</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Bot Name</label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="My Scalping Bot">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="api_key_id" class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                        <select name="api_key_id" 
                                id="api_key_id" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select API Key</option>
                            @foreach($apiKeys as $apiKey)
                                <option value="{{ $apiKey->id }}" {{ old('api_key_id') == $apiKey->id ? 'selected' : '' }}>
                                    {{ $apiKey->name }} ({{ ucfirst($apiKey->exchange) }})
                                </option>
                            @endforeach
                        </select>
                        @error('api_key_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="exchange" class="block text-sm font-medium text-gray-700 mb-2">Exchange</label>
                        <select name="exchange" 
                                id="exchange" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($exchanges as $exchange)
                                <option value="{{ $exchange }}" {{ old('exchange', 'binance') == $exchange ? 'selected' : '' }}>
                                    {{ ucfirst($exchange) }}
                                </option>
                            @endforeach
                        </select>
                        @error('exchange')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="symbol" class="block text-sm font-medium text-gray-700 mb-2">Trading Symbol</label>
                        <input type="text" 
                               name="symbol" 
                               id="symbol" 
                               value="{{ old('symbol') }}" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="BTCUSDT">
                        @error('symbol')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Scalping Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">⚡ Scalping Settings</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="max_trades_per_hour" class="block text-sm font-medium text-gray-700 mb-2">Max Trades/Hour</label>
                        <input type="number" 
                               name="max_trades_per_hour" 
                               id="max_trades_per_hour" 
                               value="{{ old('max_trades_per_hour', 12) }}" 
                               min="1" 
                               max="20" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('max_trades_per_hour')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cooldown_seconds" class="block text-sm font-medium text-gray-700 mb-2">Cooldown (Seconds)</label>
                        <input type="number" 
                               name="cooldown_seconds" 
                               id="cooldown_seconds" 
                               value="{{ old('cooldown_seconds', 30) }}" 
                               min="15" 
                               max="300" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('cooldown_seconds')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="max_concurrent_positions" class="block text-sm font-medium text-gray-700 mb-2">Max Concurrent Positions</label>
                        <input type="number" 
                               name="max_concurrent_positions" 
                               id="max_concurrent_positions" 
                               value="{{ old('max_concurrent_positions', 2) }}" 
                               min="1" 
                               max="5" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('max_concurrent_positions')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Timeframes -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">📊 Analysis Timeframes</h2>
                <p class="text-gray-600 mb-4">Select timeframes for scalping analysis (minimum 2 required)</p>
                
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach($timeframes as $timeframe)
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="timeframes[]" 
                                   value="{{ $timeframe }}"
                                   {{ in_array($timeframe, old('timeframes', $defaultTimeframes)) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">{{ $timeframe }}</span>
                        </label>
                    @endforeach
                </div>
                @error('timeframes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Risk Management -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">🛡️ Risk Management</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="risk_percentage" class="block text-sm font-medium text-gray-700 mb-2">Risk per Trade (%)</label>
                        <input type="number" 
                               name="risk_percentage" 
                               id="risk_percentage" 
                               value="{{ old('risk_percentage', 1.0) }}" 
                               step="0.1" 
                               min="0.1" 
                               max="5" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('risk_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="max_position_size" class="block text-sm font-medium text-gray-700 mb-2">Max Position Size</label>
                        <input type="number" 
                               name="max_position_size" 
                               id="max_position_size" 
                               value="{{ old('max_position_size', 0.005) }}" 
                               step="0.001" 
                               min="0.001" 
                               max="1" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('max_position_size')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="min_order_value" class="block text-sm font-medium text-gray-700 mb-2">Min Order Value (USDT)</label>
                        <input type="number" 
                               name="min_order_value" 
                               id="min_order_value" 
                               value="{{ old('min_order_value', 10) }}" 
                               step="1" 
                               min="5" 
                               max="1000" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('min_order_value')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="max_spread_percentage" class="block text-sm font-medium text-gray-700 mb-2">Max Spread (%)</label>
                        <input type="number" 
                               name="max_spread_percentage" 
                               id="max_spread_percentage" 
                               value="{{ old('max_spread_percentage', 0.1) }}" 
                               step="0.01" 
                               min="0.05" 
                               max="0.5" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('max_spread_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="stop_loss_percentage" class="block text-sm font-medium text-gray-700 mb-2">Stop Loss (%)</label>
                        <input type="number" 
                               name="stop_loss_percentage" 
                               id="stop_loss_percentage" 
                               value="{{ old('stop_loss_percentage', 1.5) }}" 
                               step="0.1" 
                               min="0.5" 
                               max="5" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('stop_loss_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="take_profit_percentage" class="block text-sm font-medium text-gray-700 mb-2">Take Profit (%)</label>
                        <input type="number" 
                               name="take_profit_percentage" 
                               id="take_profit_percentage" 
                               value="{{ old('take_profit_percentage', 2.5) }}" 
                               step="0.1" 
                               min="1" 
                               max="10" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('take_profit_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="min_risk_reward_ratio" class="block text-sm font-medium text-gray-700 mb-2">Min Risk:Reward</label>
                        <input type="number" 
                               name="min_risk_reward_ratio" 
                               id="min_risk_reward_ratio" 
                               value="{{ old('min_risk_reward_ratio', 1.2) }}" 
                               step="0.1" 
                               min="1" 
                               max="5" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('min_risk_reward_ratio')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Order Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">📋 Order Settings</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="order_type" class="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
                        <select name="order_type" 
                                id="order_type" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="market" {{ old('order_type', 'market') == 'market' ? 'selected' : '' }}>Market (Instant)</option>
                            <option value="limit" {{ old('order_type') == 'limit' ? 'selected' : '' }}>Limit (Better Price)</option>
                        </select>
                        @error('order_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="limit_order_settings" style="display: {{ old('order_type') == 'limit' ? 'block' : 'none' }};">
                        <label for="limit_order_buffer" class="block text-sm font-medium text-gray-700 mb-2">Limit Order Buffer (%)</label>
                        <input type="number" 
                               name="limit_order_buffer" 
                               id="limit_order_buffer" 
                               value="{{ old('limit_order_buffer', 0.1) }}" 
                               step="0.01" 
                               min="0.01" 
                               max="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('limit_order_buffer')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Buffer for better entry prices</p>
                    </div>
                </div>
            </div>

            <!-- Leverage Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">📈 Leverage Settings</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="leverage" class="block text-sm font-medium text-gray-700 mb-2">Leverage</label>
                        <input type="number" 
                               name="leverage" 
                               id="leverage" 
                               value="{{ old('leverage', 10) }}" 
                               min="1" 
                               max="125" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('leverage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="margin_type" class="block text-sm font-medium text-gray-700 mb-2">Margin Type</label>
                        <select name="margin_type" 
                                id="margin_type" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="isolated" {{ old('margin_type', 'isolated') == 'isolated' ? 'selected' : '' }}>Isolated</option>
                            <option value="cross" {{ old('margin_type') == 'cross' ? 'selected' : '' }}>Cross</option>
                        </select>
                        @error('margin_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="position_side" class="block text-sm font-medium text-gray-700 mb-2">Position Side</label>
                        <select name="position_side" 
                                id="position_side" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="both" {{ old('position_side', 'both') == 'both' ? 'selected' : '' }}>Both (Long & Short)</option>
                            <option value="long" {{ old('position_side') == 'long' ? 'selected' : '' }}>Long Only</option>
                            <option value="short" {{ old('position_side') == 'short' ? 'selected' : '' }}>Short Only</option>
                        </select>
                        @error('position_side')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Advanced Features -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">🚀 Advanced Features</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Trailing Stop Settings -->
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_trailing_stop" 
                                   id="enable_trailing_stop"
                                   {{ old('enable_trailing_stop') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="enable_trailing_stop" class="ml-2 text-sm text-gray-700">Enable Trailing Stop</label>
                        </div>
                        
                        <div id="trailing_stop_settings" style="display: {{ old('enable_trailing_stop') ? 'block' : 'none' }};">
                            <label for="trailing_distance" class="block text-sm font-medium text-gray-700 mb-2">Trailing Distance (%)</label>
                            <input type="number" 
                                   name="trailing_distance" 
                                   id="trailing_distance" 
                                   value="{{ old('trailing_distance', 0.8) }}" 
                                   step="0.1" 
                                   min="0.3" 
                                   max="2" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Breakeven Settings -->
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_breakeven" 
                                   id="enable_breakeven"
                                   {{ old('enable_breakeven') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="enable_breakeven" class="ml-2 text-sm text-gray-700">Enable Breakeven</label>
                        </div>
                        
                        <div id="breakeven_settings" style="display: {{ old('enable_breakeven') ? 'block' : 'none' }};">
                            <label for="breakeven_trigger" class="block text-sm font-medium text-gray-700 mb-2">Breakeven Trigger (%)</label>
                            <input type="number" 
                                   name="breakeven_trigger" 
                                   id="breakeven_trigger" 
                                   value="{{ old('breakeven_trigger', 1.0) }}" 
                                   step="0.1" 
                                   min="0.5" 
                                   max="3" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Scalping Features -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Scalping Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_momentum_scalping" 
                                   id="enable_momentum_scalping"
                                   {{ old('enable_momentum_scalping', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="enable_momentum_scalping" class="ml-2 text-sm text-gray-700">Momentum Scalping (RSI)</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_price_action_scalping" 
                                   id="enable_price_action_scalping"
                                   {{ old('enable_price_action_scalping', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="enable_price_action_scalping" class="ml-2 text-sm text-gray-700">Price Action Scalping</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_smart_money_scalping" 
                                   id="enable_smart_money_scalping"
                                   {{ old('enable_smart_money_scalping', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="enable_smart_money_scalping" class="ml-2 text-sm text-gray-700">Smart Money Scalping</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_quick_exit" 
                                   id="enable_quick_exit"
                                   {{ old('enable_quick_exit', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="enable_quick_exit" class="ml-2 text-sm text-gray-700">Quick Exit on Reversal</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_bitcoin_correlation" 
                                   id="enable_bitcoin_correlation"
                                   {{ old('enable_bitcoin_correlation') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="enable_bitcoin_correlation" class="ml-2 text-sm text-gray-700">Bitcoin Correlation Filter</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_volatility_filter" 
                                   id="enable_volatility_filter"
                                   {{ old('enable_volatility_filter', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="enable_volatility_filter" class="ml-2 text-sm text-gray-700">Volatility Filter</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('scalping-bots.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                    Create Scalping Bot
                </button>
            </div>
        </form>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle trailing stop settings
        const trailingStopCheckbox = document.getElementById('enable_trailing_stop');
        const trailingStopSettings = document.getElementById('trailing_stop_settings');
        
        trailingStopCheckbox.addEventListener('change', function() {
            trailingStopSettings.style.display = this.checked ? 'block' : 'none';
        });
        
        // Toggle breakeven settings
        const breakevenCheckbox = document.getElementById('enable_breakeven');
        const breakevenSettings = document.getElementById('breakeven_settings');
        
        breakevenCheckbox.addEventListener('change', function() {
            breakevenSettings.style.display = this.checked ? 'block' : 'none';
        });
        
        // Toggle limit order settings
        const orderTypeSelect = document.getElementById('order_type');
        const limitOrderSettings = document.getElementById('limit_order_settings');
        
        orderTypeSelect.addEventListener('change', function() {
            limitOrderSettings.style.display = this.value === 'limit' ? 'block' : 'none';
        });
    });
    </script>
    @endpush
</x-app-layout>
