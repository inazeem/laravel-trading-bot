@props(['bot'])

<div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">{{ $bot->name }}</h3>
                <p class="text-gray-600">{{ $bot->symbol }} on {{ ucfirst($bot->exchange) }}</p>
            </div>
            <div class="flex items-center">
                @if($bot->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>
                        Inactive
                    </span>
                @endif
            </div>
        </div>

        <!-- Asset Holdings and Balance -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-blue-50 p-3 rounded-lg">
                <div class="flex items-center mb-1">
                    <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-800">Asset Holdings</span>
                </div>
                <div class="text-lg font-bold text-blue-900">
                    {{ number_format($bot->asset_quantity, 6) }} {{ explode('-', $bot->symbol)[0] }}
                </div>
                @if($bot->asset_average_price > 0)
                    <div class="text-xs text-blue-600">
                        Avg: ${{ number_format($bot->asset_average_price, 4) }}
                    </div>
                @else
                    <div class="text-xs text-blue-600">No holdings</div>
                @endif
            </div>

            <div class="bg-green-50 p-3 rounded-lg">
                <div class="flex items-center mb-1">
                    <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    <span class="text-sm font-medium text-green-800">USDT Balance</span>
                </div>
                <div class="text-lg font-bold text-green-900">
                    ${{ number_format($bot->usdt_balance, 2) }}
                </div>
                <div class="text-xs text-green-600">Available for trading</div>
            </div>
        </div>

        <!-- Trading Stats -->
        <div class="border-t border-gray-200 pt-4 mb-4">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-2xl font-bold text-blue-600">{{ $bot->trades_count }}</p>
                    <p class="text-xs text-gray-500">Total Trades</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-purple-600">{{ $bot->signals_count }}</p>
                    <p class="text-xs text-gray-500">Signals</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-600">{{ $bot->risk_percentage }}%</p>
                    <p class="text-xs text-gray-500">Risk</p>
                </div>
            </div>
        </div>

        <!-- Timeframes -->
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($bot->timeframes as $timeframe)
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $timeframe }}
                </span>
            @endforeach
        </div>

        <!-- Last Run Info -->
        <div class="bg-gray-50 p-3 rounded-lg mb-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Last Run:</span>
                <span class="font-medium text-gray-900">
                    {{ $bot->last_run_at ? $bot->last_run_at->diffForHumans() : 'Never' }}
                </span>
            </div>
            @if($bot->last_trade_at)
                <div class="flex items-center justify-between text-sm mt-1">
                    <span class="text-gray-600">Last Trade:</span>
                    <span class="font-medium text-gray-900">
                        {{ $bot->last_trade_at->diffForHumans() }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-2">
            <a href="{{ route('trading-bots.show', $bot) }}" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded text-center transition duration-200">
                <i class="fas fa-eye mr-1"></i> View
            </a>
            <a href="{{ route('trading-bots.logs', $bot) }}" class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-medium py-2 px-4 rounded text-center transition duration-200">
                <i class="fas fa-list mr-1"></i> Logs
            </a>
            
            @if($bot->is_active)
                <form method="POST" action="{{ route('trading-bots.run', $bot) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        <i class="fas fa-play mr-1"></i> Run
                    </button>
                </form>
            @endif
        </div>

        <!-- Refresh Assets Button -->
        <div class="mt-2">
            <form method="POST" action="{{ route('trading-bots.refresh-assets', $bot) }}" class="w-full">
                @csrf
                <button type="submit" class="w-full bg-purple-100 hover:bg-purple-200 text-purple-800 font-medium py-2 px-4 rounded transition duration-200">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh Assets
                </button>
            </form>
        </div>

        <div class="flex space-x-2 mt-2">
            <a href="{{ route('trading-bots.edit', $bot) }}" class="flex-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-medium py-2 px-4 rounded text-center transition duration-200">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            
            @if($bot->is_active)
                <form method="POST" action="{{ route('trading-bots.toggle-status', $bot) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        <i class="fas fa-stop mr-1"></i> Stop
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('trading-bots.toggle-status', $bot) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                        <i class="fas fa-play mr-1"></i> Start
                    </button>
                </form>
            @endif
        </div>

        <!-- Enhanced Features Status -->
        <div class="mt-4 p-3 bg-gradient-to-r from-blue-50 to-green-50 border border-blue-200 rounded">
            <div class="flex items-center mb-2">
                <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-medium text-blue-800">Enhanced Features Active</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="flex items-center">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                    <span class="text-gray-600">70%+ Signal Strength</span>
                </div>
                <div class="flex items-center">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                    <span class="text-gray-600">10% Position Sizing</span>
                </div>
                <div class="flex items-center">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                    <span class="text-gray-600">3h Cooldown</span>
                </div>
                <div class="flex items-center">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                    <span class="text-gray-600">Asset Sync</span>
                </div>
            </div>
        </div>
    </div>
</div>
