<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Trading Bot') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('trading-bots.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                                
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Bot Name</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="exchange" class="block text-sm font-medium text-gray-700">Exchange</label>
                                    <select name="exchange" id="exchange" required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select Exchange</option>
                                        <option value="kucoin" {{ old('exchange') === 'kucoin' ? 'selected' : '' }}>KuCoin</option>
                                        <option value="binance" {{ old('exchange') === 'binance' ? 'selected' : '' }}>Binance</option>
                                    </select>
                                    @error('exchange')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="symbol" class="block text-sm font-medium text-gray-700">Trading Pair</label>
                                    <input type="text" name="symbol" id="symbol" value="{{ old('symbol') }}" placeholder="BTC-USDT" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('symbol')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- API Key Selection -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">API Key Selection</h3>
                                
                                <div>
                                    <label for="api_key_id" class="block text-sm font-medium text-gray-700">Select API Key</label>
                                    <select name="api_key_id" id="api_key_id" required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select an API Key</option>
                                        @foreach(auth()->user()->apiKeys()->where('is_active', true)->get() as $apiKey)
                                            @if($apiKey->hasPermission('trade'))
                                                <option value="{{ $apiKey->id }}" {{ old('api_key_id') == $apiKey->id ? 'selected' : '' }}>
                                                    {{ $apiKey->name }} ({{ ucfirst($apiKey->exchange) }})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('api_key_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    
                                    @if(auth()->user()->apiKeys()->where('is_active', true)->whereJsonContains('permissions', 'trade')->count() == 0)
                                        <p class="mt-2 text-sm text-red-600">
                                            No active API keys with trading permissions found. 
                                            <a href="{{ route('api-keys.create') }}" class="text-blue-600 hover:text-blue-800">Add an API key</a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Trading Configuration -->
                        <div class="mt-8 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Trading Configuration</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="risk_percentage" class="block text-sm font-medium text-gray-700">Risk Percentage (%)</label>
                                    <input type="number" name="risk_percentage" id="risk_percentage" value="{{ old('risk_percentage', 2) }}" 
                                           step="0.1" min="0.1" max="10" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-sm text-gray-500">Percentage of balance to risk per trade</p>
                                    @error('risk_percentage')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="max_position_size" class="block text-sm font-medium text-gray-700">Max Position Size</label>
                                    <input type="number" name="max_position_size" id="max_position_size" value="{{ old('max_position_size', 1000) }}" 
                                           step="0.001" min="0.001" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-sm text-gray-500">Maximum position size in base currency</p>
                                    @error('max_position_size')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Timeframes</label>
                                    <div class="mt-2 space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="timeframes[]" value="1h" {{ in_array('1h', old('timeframes', ['1h', '4h', '1d'])) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-700">1 Hour</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="timeframes[]" value="4h" {{ in_array('4h', old('timeframes', ['1h', '4h', '1d'])) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-700">4 Hours</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="timeframes[]" value="1d" {{ in_array('1d', old('timeframes', ['1h', '4h', '1d'])) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-700">1 Day</span>
                                        </label>
                                    </div>
                                    @error('timeframes')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <label for="is_active" class="ml-2 text-sm text-gray-700">Activate bot immediately</label>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-4">
                            <a href="{{ route('trading-bots.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Create Trading Bot
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
