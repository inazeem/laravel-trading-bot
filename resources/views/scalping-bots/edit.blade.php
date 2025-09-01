<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Scalping Bot') }}: {{ $scalpingBot->name }}
            </h2>
            <a href="{{ route('scalping-bots.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if ($errors->any())
                        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('scalping-bots.update', $scalpingBot) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Basic Configuration -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-cog mr-2"></i>Basic Configuration
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Bot Name</label>
                                    <input type="text" name="name" id="name" 
                                           value="{{ old('name', $scalpingBot->name) }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="api_key_id" class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                    <select name="api_key_id" id="api_key_id" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required>
                                        @foreach($apiKeys as $apiKey)
                                            <option value="{{ $apiKey->id }}" 
                                                    {{ old('api_key_id', $scalpingBot->api_key_id) == $apiKey->id ? 'selected' : '' }}>
                                                {{ $apiKey->name }} ({{ ucfirst($apiKey->exchange) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="exchange" class="block text-sm font-medium text-gray-700 mb-2">Exchange</label>
                                    <select name="exchange" id="exchange" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required>
                                        @foreach($exchanges as $exchange)
                                            <option value="{{ $exchange }}" 
                                                    {{ old('exchange', $scalpingBot->exchange) == $exchange ? 'selected' : '' }}>
                                                {{ ucfirst($exchange) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="symbol" class="block text-sm font-medium text-gray-700 mb-2">Trading Pair</label>
                                    <input type="text" name="symbol" id="symbol" 
                                           value="{{ old('symbol', $scalpingBot->symbol) }}" 
                                           placeholder="e.g., BTCUSDT"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>
                            </div>
                        </div>

                        <!-- Risk Management -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-shield-alt mr-2"></i>Risk Management
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="risk_percentage" class="block text-sm font-medium text-gray-700 mb-2">Risk per Trade (%)</label>
                                    <input type="number" name="risk_percentage" id="risk_percentage" 
                                           value="{{ old('risk_percentage', $scalpingBot->risk_percentage) }}" 
                                           step="0.1" min="0.1" max="5"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="max_position_size" class="block text-sm font-medium text-gray-700 mb-2">Max Position Size</label>
                                    <input type="number" name="max_position_size" id="max_position_size" 
                                           value="{{ old('max_position_size', $scalpingBot->max_position_size) }}" 
                                           step="0.001" min="0.001" max="1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="min_order_value" class="block text-sm font-medium text-gray-700 mb-2">Min Order Value ($)</label>
                                    <input type="number" name="min_order_value" id="min_order_value" 
                                           value="{{ old('min_order_value', $scalpingBot->min_order_value) }}" 
                                           step="1" min="5" max="1000"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="leverage" class="block text-sm font-medium text-gray-700 mb-2">Leverage</label>
                                    <input type="number" name="leverage" id="leverage" 
                                           value="{{ old('leverage', $scalpingBot->leverage) }}" 
                                           min="1" max="125"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="stop_loss_percentage" class="block text-sm font-medium text-gray-700 mb-2">Stop Loss (%)</label>
                                    <input type="number" name="stop_loss_percentage" id="stop_loss_percentage" 
                                           value="{{ old('stop_loss_percentage', $scalpingBot->stop_loss_percentage) }}" 
                                           step="0.1" min="0.5" max="10"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="take_profit_percentage" class="block text-sm font-medium text-gray-700 mb-2">Take Profit (%)</label>
                                    <input type="number" name="take_profit_percentage" id="take_profit_percentage" 
                                           value="{{ old('take_profit_percentage', $scalpingBot->take_profit_percentage) }}" 
                                           step="0.1" min="0.5" max="20"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>
                            </div>
                        </div>

                        <!-- Order Settings -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-shopping-cart mr-2"></i>Order Settings
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="order_type" class="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
                                    <select name="order_type" id="order_type" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required>
                                        <option value="market" {{ old('order_type', $scalpingBot->order_type) == 'market' ? 'selected' : '' }}>Market</option>
                                        <option value="limit" {{ old('order_type', $scalpingBot->order_type) == 'limit' ? 'selected' : '' }}>Limit</option>
                                    </select>
                                </div>

                                <div id="limit_order_settings" style="{{ old('order_type', $scalpingBot->order_type) == 'limit' ? 'display: block;' : 'display: none;' }}">
                                    <label for="limit_order_buffer" class="block text-sm font-medium text-gray-700 mb-2">Limit Order Buffer (%)</label>
                                    <input type="number" name="limit_order_buffer" id="limit_order_buffer" 
                                           value="{{ old('limit_order_buffer', $scalpingBot->limit_order_buffer) }}" 
                                           step="0.01" min="0.01" max="1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">How far from current price to place limit orders</p>
                                </div>
                            </div>
                        </div>

                        <!-- Timeframes -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-clock mr-2"></i>Timeframes for Analysis
                            </h3>
                            
                            <div class="grid grid-cols-3 md:grid-cols-5 gap-4">
                                @foreach($timeframes as $timeframe)
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" name="timeframes[]" value="{{ $timeframe }}" 
                                               {{ in_array($timeframe, old('timeframes', $scalpingBot->timeframes ?? [])) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 focus:border-blue-300 focus:ring focus:ring-blue-200">
                                        <span class="text-sm text-gray-700">{{ $timeframe }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Select multiple timeframes for confluence analysis</p>
                        </div>

                        <!-- Strategy Features -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-brain mr-2"></i>Strategy Features
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="flex items-center space-x-3 cursor-pointer p-3 bg-white rounded border hover:bg-gray-50">
                                    <input type="checkbox" name="enable_trailing_stop" value="1" 
                                           {{ old('enable_trailing_stop', $scalpingBot->enable_trailing_stop) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Trailing Stop</span>
                                        <p class="text-xs text-gray-500">Automatically adjust stop loss as price moves favorably</p>
                                    </div>
                                </label>

                                <label class="flex items-center space-x-3 cursor-pointer p-3 bg-white rounded border hover:bg-gray-50">
                                    <input type="checkbox" name="enable_breakeven" value="1" 
                                           {{ old('enable_breakeven', $scalpingBot->enable_breakeven) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Breakeven</span>
                                        <p class="text-xs text-gray-500">Move stop loss to breakeven when in profit</p>
                                    </div>
                                </label>

                                <label class="flex items-center space-x-3 cursor-pointer p-3 bg-white rounded border hover:bg-gray-50">
                                    <input type="checkbox" name="enable_momentum_scalping" value="1" 
                                           {{ old('enable_momentum_scalping', $scalpingBot->enable_momentum_scalping) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Momentum Scalping</span>
                                        <p class="text-xs text-gray-500">Trade momentum breakouts and continuations</p>
                                    </div>
                                </label>

                                <label class="flex items-center space-x-3 cursor-pointer p-3 bg-white rounded border hover:bg-gray-50">
                                    <input type="checkbox" name="enable_smart_money_scalping" value="1" 
                                           {{ old('enable_smart_money_scalping', $scalpingBot->enable_smart_money_scalping) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Smart Money Concepts</span>
                                        <p class="text-xs text-gray-500">Use SMC signals (BOS, CHoCH, Order Blocks)</p>
                                    </div>
                                </label>

                                <label class="flex items-center space-x-3 cursor-pointer p-3 bg-white rounded border hover:bg-gray-50">
                                    <input type="checkbox" name="enable_quick_exit" value="1" 
                                           {{ old('enable_quick_exit', $scalpingBot->enable_quick_exit) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Quick Exit</span>
                                        <p class="text-xs text-gray-500">Fast exit on reversal signals</p>
                                    </div>
                                </label>

                                <label class="flex items-center space-x-3 cursor-pointer p-3 bg-white rounded border hover:bg-gray-50">
                                    <input type="checkbox" name="enable_volatility_filter" value="1" 
                                           {{ old('enable_volatility_filter', $scalpingBot->enable_volatility_filter) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Volatility Filter</span>
                                        <p class="text-xs text-gray-500">Only trade during optimal volatility conditions</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('scalping-bots.index') }}" 
                               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                                <i class="fas fa-save mr-2"></i>Update Scalping Bot
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle limit order settings
            const orderTypeSelect = document.getElementById('order_type');
            const limitOrderSettings = document.getElementById('limit_order_settings');
            
            orderTypeSelect.addEventListener('change', function() {
                limitOrderSettings.style.display = this.value === 'limit' ? 'block' : 'none';
            });
        });
    </script>
</x-app-layout>
