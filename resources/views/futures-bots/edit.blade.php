<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Futures Trading Bot') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center mb-8">
                        <a href="{{ route('futures-bots.index') }}" class="text-blue-600 hover:text-blue-800 mr-4">
                            <i class="fas fa-arrow-left"></i> Back to Bots
                        </a>
                        <h1 class="text-3xl font-bold text-gray-900">Edit Futures Trading Bot</h1>
                    </div>

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
                        <form method="POST" action="{{ route('futures-bots.update', $futuresBot) }}">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Basic Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Basic Information</h3>
                                    
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Bot Name</label>
                                        <input type="text" name="name" id="name" value="{{ old('name', $futuresBot->name) }}" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('name')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="api_key_id" class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                                        <select name="api_key_id" id="api_key_id" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @foreach($apiKeys as $apiKey)
                                                <option value="{{ $apiKey->id }}" {{ old('api_key_id', $futuresBot->api_key_id) == $apiKey->id ? 'selected' : '' }}>
                                                    {{ $apiKey->name }} ({{ ucfirst($apiKey->exchange) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('api_key_id')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="symbol" class="block text-sm font-medium text-gray-700 mb-1">Trading Pair</label>
                                        <input type="text" name="symbol" id="symbol" value="{{ old('symbol', $futuresBot->symbol) }}" placeholder="BTCUSDT" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('symbol')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Trading Configuration -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Trading Configuration</h3>
                                    
                                    <div>
                                        <label for="leverage" class="block text-sm font-medium text-gray-700 mb-1">Leverage</label>
                                        <select name="leverage" id="leverage" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @foreach($leverages as $leverage)
                                                <option value="{{ $leverage }}" {{ old('leverage', $futuresBot->leverage) == $leverage ? 'selected' : '' }}>
                                                    {{ $leverage }}x
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('leverage')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="margin_type" class="block text-sm font-medium text-gray-700 mb-1">Margin Type</label>
                                        <select name="margin_type" id="margin_type" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @foreach($marginTypes as $marginType)
                                                <option value="{{ $marginType }}" {{ old('margin_type', $futuresBot->margin_type) == $marginType ? 'selected' : '' }}>
                                                    {{ ucfirst($marginType) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('margin_type')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="position_side" class="block text-sm font-medium text-gray-700 mb-1">Position Side</label>
                                        <select name="position_side" id="position_side" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @foreach($positionSides as $positionSide)
                                                <option value="{{ $positionSide }}" {{ old('position_side', $futuresBot->position_side) == $positionSide ? 'selected' : '' }}>
                                                    {{ ucfirst($positionSide) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('position_side')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Risk Management -->
                            <div class="mt-8 space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Risk Management</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="risk_percentage" class="block text-sm font-medium text-gray-700 mb-1">Risk Per Trade (%)</label>
                                        <input type="number" name="risk_percentage" id="risk_percentage" value="{{ old('risk_percentage', $futuresBot->risk_percentage) }}" 
                                            step="0.1" min="0.1" max="10" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('risk_percentage')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="max_position_size" class="block text-sm font-medium text-gray-700 mb-1">Max Position Size</label>
                                        <input type="number" name="max_position_size" id="max_position_size" value="{{ old('max_position_size', $futuresBot->max_position_size) }}" 
                                            step="0.001" min="0.001" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('max_position_size')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="stop_loss_percentage" class="block text-sm font-medium text-gray-700 mb-1">Stop Loss (%)</label>
                                        <input type="number" name="stop_loss_percentage" id="stop_loss_percentage" value="{{ old('stop_loss_percentage', $futuresBot->stop_loss_percentage) }}" 
                                            step="0.1" min="0.1" max="10" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('stop_loss_percentage')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="take_profit_percentage" class="block text-sm font-medium text-gray-700 mb-1">Take Profit (%)</label>
                                    <input type="number" name="take_profit_percentage" id="take_profit_percentage" value="{{ old('take_profit_percentage', $futuresBot->take_profit_percentage) }}" 
                                        step="0.1" min="0.1" max="20" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('take_profit_percentage')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Timeframes -->
                            <div class="mt-8 space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Analysis Timeframes</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    @foreach($timeframes as $timeframe)
                                        <label class="flex items-center">
                                            <input type="checkbox" name="timeframes[]" value="{{ $timeframe }}" 
                                                {{ in_array($timeframe, old('timeframes', $futuresBot->timeframes)) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm font-medium text-gray-700">{{ $timeframe }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('timeframes')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Submit Buttons -->
                            <div class="mt-8 flex justify-end space-x-4">
                                <a href="{{ route('futures-bots.show', $futuresBot) }}" 
                                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition duration-200">
                                    Cancel
                                </a>
                                <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                                    Update Futures Bot
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
