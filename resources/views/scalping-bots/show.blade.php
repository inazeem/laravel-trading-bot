<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                ⚡ {{ $scalpingBot->name }}
            </h2>
            <div class="flex space-x-2">
                @if($scalpingBot->is_active)
                    <form action="{{ route('scalping-bots.run', $scalpingBot) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                            <i class="fas fa-bolt mr-2"></i>Run Now
                        </button>
                    </form>
                @endif
                
                <form action="{{ route('scalping-bots.toggle', $scalpingBot) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="{{ $scalpingBot->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas {{ $scalpingBot->is_active ? 'fa-pause' : 'fa-play' }} mr-2"></i>
                        {{ $scalpingBot->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">
                
                <!-- Bot Status Overview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <!-- Status -->
                            <div class="text-center">
                                <div class="text-2xl font-bold {{ $scalpingBot->is_active ? 'text-green-600' : 'text-gray-600' }}">
                                    {{ $scalpingBot->is_active ? 'Active' : 'Inactive' }}
                                </div>
                                <div class="text-sm text-gray-500">Status</div>
                                <div class="text-xs text-gray-400 mt-1">{{ ucfirst($scalpingBot->status) }}</div>
                            </div>

                            <!-- Performance -->
                            <div class="text-center">
                                <div class="text-2xl font-bold {{ $scalpingBot->total_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($scalpingBot->total_pnl, 2) }}
                                </div>
                                <div class="text-sm text-gray-500">Total PnL</div>
                                <div class="text-xs text-gray-400 mt-1">{{ $scalpingBot->total_trades }} trades</div>
                            </div>

                            <!-- Win Rate -->
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ number_format($scalpingBot->win_rate, 1) }}%
                                </div>
                                <div class="text-sm text-gray-500">Win Rate</div>
                                <div class="text-xs text-gray-400 mt-1">{{ $scalpingBot->winning_trades }} wins</div>
                            </div>

                            <!-- Today's Performance -->
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">
                                    {{ $todaysStats['trades'] }}
                                </div>
                                <div class="text-sm text-gray-500">Today's Trades</div>
                                <div class="text-xs {{ $todaysStats['pnl'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                                    ${{ number_format($todaysStats['pnl'], 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Trading Settings -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">⚙️ Trading Settings</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-gray-500">Symbol:</span>
                                    <div class="font-medium">{{ $scalpingBot->symbol }}</div>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Exchange:</span>
                                    <div class="font-medium">{{ ucfirst($scalpingBot->exchange) }}</div>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Leverage:</span>
                                    <div class="font-medium">{{ $scalpingBot->leverage }}x</div>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Position Side:</span>
                                    <div class="font-medium">{{ ucfirst($scalpingBot->position_side) }}</div>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Stop Loss:</span>
                                    <div class="font-medium">{{ $scalpingBot->stop_loss_percentage }}%</div>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Take Profit:</span>
                                    <div class="font-medium">{{ $scalpingBot->take_profit_percentage }}%</div>
                                </div>
                            </div>
                            
                            <div>
                                <span class="text-sm text-gray-500">Timeframes:</span>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    @foreach($scalpingBot->timeframes as $timeframe)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">{{ $timeframe }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scalping Settings -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">⚡ Scalping Settings</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-gray-500">Max Trades/Hour:</span>
                                    <div class="font-medium">{{ $scalpingBot->max_trades_per_hour }}</div>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Cooldown:</span>
                                    <div class="font-medium">{{ $scalpingBot->cooldown_seconds }}s</div>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Max Positions:</span>
                                    <div class="font-medium">{{ $scalpingBot->max_concurrent_positions }}</div>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Max Spread:</span>
                                    <div class="font-medium">{{ $scalpingBot->max_spread_percentage }}%</div>
                                </div>
                            </div>
                            
                            <div>
                                <span class="text-sm text-gray-500">This Hour:</span>
                                <div class="font-medium">
                                    {{ $thisHourStats['trades'] }}/{{ $scalpingBot->max_trades_per_hour }} trades
                                    <span class="text-xs text-gray-500">({{ $thisHourStats['remaining_trades'] }} remaining)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <a href="#trades" class="tab-link border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600" data-tab="trades">
                                Recent Trades ({{ $recentTrades->count() }})
                            </a>
                            <a href="#signals" class="tab-link border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="signals">
                                Recent Signals ({{ $recentSignals->count() }})
                            </a>
                            <a href="{{ route('scalping-bots.trades', $scalpingBot) }}" class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                All Trades
                            </a>
                            <a href="{{ route('scalping-bots.signals', $scalpingBot) }}" class="border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                All Signals
                            </a>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Trades Tab -->
                        <div class="tab-content" id="trades">
                            @if($recentTrades->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Side</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entry</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exit</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PnL</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($recentTrades as $trade)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 py-1 text-xs rounded {{ $trade->isLong() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                            {{ strtoupper($trade->side) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm">${{ number_format($trade->entry_price, 4) }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                        {{ $trade->exit_price ? '$' . number_format($trade->exit_price, 4) : '-' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="text-sm {{ $trade->net_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            ${{ number_format($trade->net_pnl, 2) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $trade->trade_duration }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 py-1 text-xs rounded {{ $trade->isOpen() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                                            {{ ucfirst($trade->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <div class="text-gray-400 text-lg">📊</div>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No trades yet</h3>
                                    <p class="mt-1 text-sm text-gray-500">Trades will appear here once the bot starts trading.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Signals Tab -->
                        <div class="tab-content hidden" id="signals">
                            @if($recentSignals->count() > 0)
                                <div class="space-y-4">
                                    @foreach($recentSignals as $signal)
                                        <div class="border rounded-lg p-4">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-2 py-1 text-xs rounded {{ $signal->direction == 'long' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                            {{ strtoupper($signal->direction) }}
                                                        </span>
                                                        <span class="text-sm font-medium">{{ $signal->signal_type }}</span>
                                                        <span class="text-xs text-gray-500">{{ $signal->timeframe }}</span>
                                                    </div>
                                                    <div class="text-sm text-gray-600 mt-1">{{ $signal->entry_reason }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm font-medium">Strength: {{ number_format($signal->strength * 100, 1) }}%</div>
                                                    <div class="text-xs text-gray-500">{{ $signal->created_at->diffForHumans() }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <div class="text-gray-400 text-lg">📡</div>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No signals yet</h3>
                                    <p class="mt-1 text-sm text-gray-500">Signals will appear here once the bot starts analyzing the market.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">🎮 Quick Actions</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-wrap gap-4">
                            <a href="{{ route('scalping-bots.edit', $scalpingBot) }}" 
                               class="bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                                <i class="fas fa-edit mr-2"></i>Edit Settings
                            </a>
                            
                            @if($scalpingBot->openTrades->count() > 0)
                                <form action="{{ route('scalping-bots.close-all-positions', $scalpingBot) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to close all {{ $scalpingBot->openTrades->count() }} open positions?')">
                                    @csrf
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                                        <i class="fas fa-times-circle mr-2"></i>Close All Positions ({{ $scalpingBot->openTrades->count() }})
                                    </button>
                                </form>
                            @endif
                            
                            <form action="{{ route('scalping-bots.learn', $scalpingBot) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                                    <i class="fas fa-brain mr-2"></i>Analyze Performance
                                </button>
                            </form>
                            
                            <form action="{{ route('scalping-bots.reset-learning', $scalpingBot) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Are you sure you want to reset all learning data?')">
                                @csrf
                                <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                                    <i class="fas fa-refresh mr-2"></i>Reset Learning
                                </button>
                            </form>
                            
                            @if($scalpingBot->openTrades->count() == 0)
                                <form action="{{ route('scalping-bots.destroy', $scalpingBot) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this scalping bot? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                                        <i class="fas fa-trash mr-2"></i>Delete Bot
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active state from all tabs
                tabLinks.forEach(l => {
                    l.classList.remove('border-blue-500', 'text-blue-600');
                    l.classList.add('border-transparent', 'text-gray-500');
                });
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Activate clicked tab
                this.classList.remove('border-transparent', 'text-gray-500');
                this.classList.add('border-blue-500', 'text-blue-600');
                
                // Show corresponding content
                const targetTab = this.getAttribute('data-tab');
                document.getElementById(targetTab).classList.remove('hidden');
            });
        });
    });
    </script>
    @endpush
</x-app-layout>
