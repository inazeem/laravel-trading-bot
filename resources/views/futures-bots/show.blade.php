<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Futures Trading Bot Details') }}
            </h2>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('futures-bots.edit', $futuresBot) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Edit Bot
                </a>
                @if($futuresBot->is_active)
                    <form method="POST" action="{{ route('futures-bots.run', $futuresBot) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                            <i class="fas fa-play mr-2"></i>Run Bot
                        </button>
                    </form>
                    
                    <!-- Manual Trade Buttons -->
                    <form method="POST" action="{{ route('futures-bots.manual-trade', $futuresBot) }}" class="inline">
                        @csrf
                        <input type="hidden" name="direction" value="long">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200"
                            onclick="return confirm('Place manual LONG trade? This will ignore signals and calculate SL/TP automatically.')">
                            <i class="fas fa-arrow-up mr-2"></i>Manual Long
                        </button>
                    </form>
                    <form method="POST" action="{{ route('futures-bots.manual-trade', $futuresBot) }}" class="inline">
                        @csrf
                        <input type="hidden" name="direction" value="short">
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200"
                            onclick="return confirm('Place manual SHORT trade? This will ignore signals and calculate SL/TP automatically.')">
                            <i class="fas fa-arrow-down mr-2"></i>Manual Short
                        </button>
                    </form>
                    @if($stats['open_trades'] > 0)
                        <form method="POST" action="{{ route('futures-bots.close-trades', $futuresBot) }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200"
                                onclick="return confirm('Close all open trades immediately? This action cannot be undone.')">
                                <i class="fas fa-times-circle mr-2"></i>Close All Trades
                            </button>
                        </form>
                    @endif
                @endif
                <form method="POST" action="{{ route('futures-bots.destroy', $futuresBot) }}" class="inline" 
                    onsubmit="return confirm('Are you sure you want to delete this futures trading bot? This action cannot be undone and will delete all associated trades, signals, and logs.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-trash mr-2"></i>Delete Bot
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Bot Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ $futuresBot->name }}</h1>
                            <p class="text-gray-600">{{ $futuresBot->symbol }} on {{ ucfirst($futuresBot->exchange) }}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            @if($futuresBot->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                    Inactive
                                </span>
                            @endif
                            
                            <form method="POST" action="{{ route('futures-bots.toggle', $futuresBot) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-blue-600 hover:text-blue-800">
                                    {{ $futuresBot->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Configuration Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Leverage</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ $futuresBot->leverage }}x</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Risk Per Trade</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ $futuresBot->risk_percentage }}%</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Margin Type</h3>
                            <p class="text-2xl font-bold text-gray-900 capitalize">{{ $futuresBot->margin_type }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Position Side</h3>
                            <p class="text-2xl font-bold text-gray-900 capitalize">{{ $futuresBot->position_side }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Trading Strategy</h3>
                            @if($futuresBot->strategies->count() > 0)
                                @foreach($futuresBot->strategies as $strategy)
                                    <div class="mb-1">
                                        <p class="text-sm font-bold text-gray-900">{{ $strategy->name }}</p>
                                        <p class="text-xs text-gray-600">Priority: {{ $strategy->pivot->priority }}</p>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-600">No Strategy</p>
                                <p class="text-xs text-gray-500">Manual Trading</p>
                            @endif
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Total PnL</h3>
                            <p class="text-2xl font-bold {{ $stats['total_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($stats['total_pnl'], 2) }}
                            </p>
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Unrealized PnL</h3>
                            <p class="text-2xl font-bold {{ $stats['unrealized_pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($stats['unrealized_pnl'], 2) }}
                            </p>
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Win Rate</h3>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['win_rate'], 1) }}%</p>
                        </div>
                    </div>

                    <!-- Timeframes -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Analysis Timeframes</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($futuresBot->timeframes as $timeframe)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ $timeframe }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Risk Management -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Risk Management</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Stop Loss:</span>
                                    <span class="font-medium">{{ $futuresBot->stop_loss_percentage }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Take Profit:</span>
                                    <span class="font-medium">{{ $futuresBot->take_profit_percentage }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Max Position Size:</span>
                                    <span class="font-medium">{{ $futuresBot->max_position_size }}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Statistics</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Trades:</span>
                                    <span class="font-medium">{{ $stats['total_trades'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Open Trades:</span>
                                    <span class="font-medium">{{ $stats['open_trades'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Closed Trades:</span>
                                    <span class="font-medium">{{ $stats['closed_trades'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trade Countdown Timer -->
            @if($currentOpenTrade)
                <x-trade-countdown :trade="$currentOpenTrade" />
            @endif

            <!-- Recent Activity Tabs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6">
                        <a href="#trades" class="tab-link border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600" data-tab="trades">
                            Recent Trades
                        </a>
                        <a href="#signals" class="tab-link border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="signals">
                            Recent Signals
                        </a>
                        <a href="{{ route('futures-bots.trades', $futuresBot) }}" class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            All Trades
                        </a>
                        <a href="{{ route('futures-bots.signals', $futuresBot) }}" class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            All Signals
                        </a>
                        <a href="{{ route('futures-bots.logs', $futuresBot) }}" class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            View Logs
                        </a>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Recent Trades -->
                    <div id="trades">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Trades</h3>
                        @if($recentTrades->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Side</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry Price</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exit Price</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PnL</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($recentTrades as $trade)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        {{ $trade->side === 'long' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ ucfirst($trade->side) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $trade->quantity }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($trade->entry_price, 2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $trade->exit_price ? '$' . number_format($trade->exit_price, 2) : '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <span class="{{ $trade->realized_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        ${{ number_format($trade->realized_pnl, 2) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        {{ $trade->status === 'open' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                                        {{ ucfirst($trade->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $trade->created_at->format('M d, Y H:i') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">No trades found for this bot.</p>
                        @endif
                    </div>

                    <!-- Recent Signals -->
                    <div id="signals" class="hidden" style="display: none;">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Signals</h3>
                        @if($recentSignals->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timeframe</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Direction</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Signal Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Strength</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Executed</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($recentSignals as $signal)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $signal->timeframe }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        {{ $signal->direction === 'long' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ ucfirst($signal->direction) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $signal->signal_type }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($signal->strength * 100, 1) }}%</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($signal->price, 2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        {{ $signal->executed ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                        {{ $signal->executed ? 'Yes' : 'No' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $signal->created_at->format('M d, Y H:i') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">No signals found for this bot.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced tab switching with better error handling
        document.addEventListener('DOMContentLoaded', function() {
            const tabLinks = document.querySelectorAll('.tab-link');
            const sections = document.querySelectorAll('#trades, #signals');

            console.log('Futures bot detail page loaded');
            console.log('Found tab links:', tabLinks.length);
            console.log('Found sections:', sections.length);

            // Fallback: if no JavaScript, show all sections
            if (tabLinks.length === 0) {
                console.log('No tab links found, showing all sections');
                sections.forEach(section => {
                    section.classList.remove('hidden');
                    section.style.display = 'block';
                });
                return;
            }

            tabLinks.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetTab = this.getAttribute('data-tab');
                    console.log('Tab clicked:', targetTab);
                    
                    // Remove active classes from all tab links
                    tabLinks.forEach(t => {
                        t.classList.remove('border-blue-500', 'text-blue-600');
                        t.classList.add('border-transparent', 'text-gray-500');
                    });
                    
                    // Add active class to clicked tab
                    this.classList.remove('border-transparent', 'text-gray-500');
                    this.classList.add('border-blue-500', 'text-blue-600');
                    
                    // Show/hide sections
                    sections.forEach(section => {
                        if (section.id === targetTab) {
                            section.classList.remove('hidden');
                            section.style.display = 'block';
                            console.log('Showing section:', section.id);
                        } else {
                            section.classList.add('hidden');
                            section.style.display = 'none';
                            console.log('Hiding section:', section.id);
                        }
                    });
                });
            });

            // Initialize: show trades tab by default
            const tradesSection = document.getElementById('trades');
            const signalsSection = document.getElementById('signals');
            
            if (tradesSection) {
                tradesSection.classList.remove('hidden');
                tradesSection.style.display = 'block';
                console.log('Initialized: showing trades section');
            }
            
            if (signalsSection) {
                signalsSection.classList.add('hidden');
                signalsSection.style.display = 'none';
                console.log('Initialized: hiding signals section');
            }

            // Real-time countdown timer
            const countdownElement = document.querySelector('[data-countdown]');
            if (countdownElement) {
                const tradeOpenedAt = new Date(countdownElement.dataset.tradeOpenedAt);
                const maxDurationHours = parseInt(countdownElement.dataset.maxDurationHours) || 2;
                
                // Calculate when the trade should close
                const tradeShouldCloseAt = new Date(tradeOpenedAt.getTime() + (maxDurationHours * 60 * 60 * 1000));

                function updateCountdown() {
                    const now = new Date();
                    const remaining = tradeShouldCloseAt - now;

                    if (remaining <= 0) {
                        countdownElement.textContent = '00:00';
                        countdownElement.classList.add('text-red-600', 'animate-pulse');
                        return;
                    }

                    const hours = Math.floor(remaining / (1000 * 60 * 60));
                    const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

                    countdownElement.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                    // Update color based on time remaining
                    const timeLeftMinutes = remaining / (1000 * 60);
                    if (timeLeftMinutes <= 30) {
                        countdownElement.classList.remove('text-green-600', 'text-orange-600');
                        countdownElement.classList.add('text-red-600', 'animate-pulse');
                    } else if (timeLeftMinutes <= 60) {
                        countdownElement.classList.remove('text-green-600', 'text-red-600', 'animate-pulse');
                        countdownElement.classList.add('text-orange-600');
                    } else {
                        countdownElement.classList.remove('text-orange-600', 'text-red-600', 'animate-pulse');
                        countdownElement.classList.add('text-green-600');
                    }
                }

                // Update immediately and then every second
                updateCountdown();
                setInterval(updateCountdown, 1000);
            }
        });
    </script>
</x-app-layout>
