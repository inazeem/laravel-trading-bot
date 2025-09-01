<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Scalping Bot Signals') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $scalpingBot->name }} - {{ $scalpingBot->symbol }}
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('scalping-bots.show', $scalpingBot) }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Bot
                </a>
                <a href="{{ route('scalping-bots.trades', $scalpingBot) }}" 
                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-chart-line mr-2"></i>View Trades
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Signal Statistics -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Signal Statistics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        @php
                            $totalSignals = $signals->total();
                            $tradedSignals = $signals->where('was_traded', true)->count();
                            $successfulSignals = $signals->where('was_successful', true)->count();
                            $avgQuality = $signals->where('signal_performance_score', '>', 0)->avg('signal_performance_score');
                            $recentSignals = $signals->where('created_at', '>=', now()->subHours(24))->count();
                        @endphp

                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $totalSignals }}</div>
                            <div class="text-sm text-gray-600">Total Signals</div>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $tradedSignals }}</div>
                            <div class="text-sm text-gray-600">Signals Traded</div>
                        </div>

                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ $successfulSignals > 0 && $signals->count() > 0 ? number_format(($successfulSignals / $signals->count()) * 100, 1) : 0 }}%
                            </div>
                            <div class="text-sm text-gray-600">Success Rate</div>
                        </div>

                        <div class="bg-orange-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-orange-600">
                                {{ $avgQuality ? number_format($avgQuality, 2) : 'N/A' }}
                            </div>
                            <div class="text-sm text-gray-600">Avg Quality</div>
                        </div>
                    </div>

                    @if($recentSignals > 0)
                        <div class="mt-4 text-sm text-gray-600">
                            <strong>Recent Activity:</strong> {{ $recentSignals }} signals in the last 24 hours
                        </div>
                    @endif
                </div>
            </div>

            <!-- Signals Table -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Signal History</h3>
                        
                        <!-- Filter Controls -->
                        <div class="flex space-x-2">
                            <select class="border border-gray-300 rounded px-3 py-1 text-sm" id="typeFilter">
                                <option value="">All Types</option>
                                <option value="engulfing">Engulfing</option>
                                <option value="bos">Break of Structure</option>
                                <option value="choch">Change of Character</option>
                                <option value="order_block">Order Block</option>
                                <option value="smart_money">Smart Money</option>
                                <option value="price_action_scalping">Price Action</option>
                                <option value="momentum_scalping">Momentum</option>
                            </select>
                            <select class="border border-gray-300 rounded px-3 py-1 text-sm" id="directionFilter">
                                <option value="">All Directions</option>
                                <option value="long">Long</option>
                                <option value="short">Short</option>
                            </select>
                            <select class="border border-gray-300 rounded px-3 py-1 text-sm" id="timeframeFilter">
                                <option value="">All Timeframes</option>
                                <option value="1m">1m</option>
                                <option value="5m">5m</option>
                                <option value="15m">15m</option>
                                <option value="30m">30m</option>
                                <option value="1h">1h</option>
                            </select>
                        </div>
                    </div>

                    @if($signals->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Signal Info
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quality
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Market Data
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Performance
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Time
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($signals as $signal)
                                        <tr class="signal-row" 
                                            data-type="{{ $signal->signal_type }}" 
                                            data-direction="{{ $signal->direction }}"
                                            data-timeframe="{{ $signal->timeframe }}">
                                            <!-- Signal Info -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full flex items-center justify-center text-white text-sm font-bold
                                                            {{ $signal->direction === 'long' ? 'bg-green-500' : 'bg-red-500' }}">
                                                            {{ $signal->direction === 'long' ? '↗️' : '↘️' }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ ucfirst(str_replace('_', ' ', $signal->signal_type)) }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ strtoupper($signal->direction) }} - {{ $signal->timeframe }}
                                                        </div>
                                                        @if($signal->urgency === 'high')
                                                            <div class="text-xs text-red-600 font-semibold">
                                                                <i class="fas fa-exclamation-triangle mr-1"></i>HIGH URGENCY
                                                            </div>
                                                        @elseif($signal->urgency === 'medium')
                                                            <div class="text-xs text-orange-600">
                                                                <i class="fas fa-clock mr-1"></i>Medium Urgency
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Quality -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="space-y-1">
                                                    <div>
                                                        <span class="text-gray-600">Strength:</span>
                                                        <span class="font-medium">{{ number_format($signal->strength, 2) }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600">Score:</span>
                                                        <span class="font-medium">{{ number_format($signal->scalping_score, 2) }}</span>
                                                    </div>
                                                    @if($signal->confluence)
                                                        <div>
                                                            <span class="text-gray-600">Confluence:</span>
                                                            <span class="font-medium">{{ number_format($signal->confluence, 1) }}</span>
                                                        </div>
                                                    @endif
                                                    
                                                    <!-- Quality indicator -->
                                                    @php
                                                        $quality = $signal->getOverallQuality();
                                                        $qualityColor = $quality >= 0.8 ? 'green' : ($quality >= 0.6 ? 'yellow' : 'red');
                                                        $qualityText = $quality >= 0.8 ? 'Excellent' : ($quality >= 0.6 ? 'Good' : 'Poor');
                                                    @endphp
                                                    <div class="text-xs">
                                                        <span class="px-2 py-1 rounded-full bg-{{ $qualityColor }}-100 text-{{ $qualityColor }}-800">
                                                            {{ $qualityText }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Market Data -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="space-y-1">
                                                    <div>
                                                        <span class="text-gray-600">Price:</span>
                                                        <span class="font-medium">${{ number_format($signal->price_at_signal, 4) }}</span>
                                                    </div>
                                                    @if($signal->rsi_at_signal)
                                                        <div>
                                                            <span class="text-gray-600">RSI:</span>
                                                            <span class="font-medium">{{ number_format($signal->rsi_at_signal, 1) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($signal->spread_at_signal)
                                                        <div>
                                                            <span class="text-gray-600">Spread:</span>
                                                            <span class="font-medium">{{ number_format($signal->spread_at_signal, 3) }}%</span>
                                                        </div>
                                                    @endif
                                                    @if($signal->volatility_at_signal)
                                                        <div>
                                                            <span class="text-gray-600">Volatility:</span>
                                                            <span class="font-medium">{{ number_format($signal->volatility_at_signal, 2) }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Performance -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="space-y-1">
                                                    @if($signal->max_price_move !== null)
                                                        <div class="font-medium {{ $signal->max_price_move >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $signal->max_price_move >= 0 ? '+' : '' }}{{ number_format($signal->max_price_move, 2) }}%
                                                        </div>
                                                        <div class="text-xs text-gray-600">Max Move</div>
                                                    @endif
                                                    
                                                    @if($signal->signal_performance_score !== null)
                                                        <div class="text-xs">
                                                            <span class="text-gray-600">Performance:</span>
                                                            <span class="font-medium">{{ number_format($signal->signal_performance_score, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($signal->signal_duration_minutes)
                                                        <div class="text-xs text-gray-600">
                                                            Duration: {{ $signal->signal_duration_minutes }}m
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Status -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="space-y-2">
                                                    @if($signal->was_traded)
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                            <i class="fas fa-check mr-1"></i>Traded
                                                        </span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                            <i class="fas fa-times mr-1"></i>Not Traded
                                                        </span>
                                                        @if($signal->not_traded_reason)
                                                            <div class="text-xs text-gray-500">
                                                                {{ ucfirst(str_replace('_', ' ', $signal->not_traded_reason)) }}
                                                            </div>
                                                        @endif
                                                    @endif
                                                    
                                                    @if($signal->was_successful === true)
                                                        <div class="text-xs text-green-600">
                                                            <i class="fas fa-thumbs-up mr-1"></i>Successful
                                                        </div>
                                                    @elseif($signal->was_successful === false)
                                                        <div class="text-xs text-red-600">
                                                            <i class="fas fa-thumbs-down mr-1"></i>Failed
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Time -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div>{{ $signal->created_at->format('M j, H:i') }}</div>
                                                <div class="text-xs">{{ $signal->created_at->diffForHumans() }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $signals->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-500 text-lg mb-2">
                                <i class="fas fa-signal fa-3x mb-4"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No signals yet</h3>
                            <p class="text-gray-500">
                                This scalping bot hasn't generated any signals yet. Make sure the bot is active and market conditions are suitable for signal generation.
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('scalping-bots.run', $scalpingBot) }}" 
                                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-play mr-2"></i>Run Bot Now
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Signal Analysis -->
            @if($signals->count() > 10)
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Signal Analysis</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Signal Type Performance -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-3">Signal Type Performance</h4>
                                @php
                                    $typePerformance = $signals->groupBy('signal_type')->map(function($typeSignals) {
                                        $successRate = $typeSignals->where('was_successful', true)->count() / $typeSignals->count() * 100;
                                        $avgScore = $typeSignals->avg('signal_performance_score');
                                        return [
                                            'count' => $typeSignals->count(),
                                            'success_rate' => $successRate,
                                            'avg_score' => $avgScore
                                        ];
                                    })->sortByDesc('success_rate');
                                @endphp
                                
                                @foreach($typePerformance->take(5) as $type => $performance)
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                        <span class="text-sm font-medium">{{ number_format($performance['success_rate'], 1) }}%</span>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Timeframe Performance -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-3">Timeframe Performance</h4>
                                @php
                                    $timeframePerformance = $signals->groupBy('timeframe')->map(function($tfSignals) {
                                        $successRate = $tfSignals->where('was_successful', true)->count() / $tfSignals->count() * 100;
                                        $avgStrength = $tfSignals->avg('strength');
                                        return [
                                            'count' => $tfSignals->count(),
                                            'success_rate' => $successRate,
                                            'avg_strength' => $avgStrength
                                        ];
                                    })->sortByDesc('success_rate');
                                @endphp
                                
                                @foreach($timeframePerformance as $tf => $performance)
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-700">{{ $tf }}</span>
                                        <span class="text-sm font-medium">{{ number_format($performance['success_rate'], 1) }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Filter functionality
        document.getElementById('typeFilter').addEventListener('change', function() {
            filterSignals();
        });

        document.getElementById('directionFilter').addEventListener('change', function() {
            filterSignals();
        });

        document.getElementById('timeframeFilter').addEventListener('change', function() {
            filterSignals();
        });

        function filterSignals() {
            const typeFilter = document.getElementById('typeFilter').value;
            const directionFilter = document.getElementById('directionFilter').value;
            const timeframeFilter = document.getElementById('timeframeFilter').value;
            const rows = document.querySelectorAll('.signal-row');

            rows.forEach(row => {
                const type = row.dataset.type;
                const direction = row.dataset.direction;
                const timeframe = row.dataset.timeframe;
                
                let showRow = true;
                
                if (typeFilter && type !== typeFilter) {
                    showRow = false;
                }
                
                if (directionFilter && direction !== directionFilter) {
                    showRow = false;
                }
                
                if (timeframeFilter && timeframe !== timeframeFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
    </script>
    @endpush
</x-app-layout>
