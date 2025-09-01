<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Scalping Bot Trades') }}
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
                <a href="{{ route('scalping-bots.signals', $scalpingBot) }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-signal mr-2"></i>View Signals
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

            <!-- Trade Statistics -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Trade Statistics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        @php
                            $totalTrades = $trades->total();
                            $openTrades = $trades->where('status', 'open')->count();
                            $closedTrades = $trades->where('status', 'closed')->count();
                            $winningTrades = $trades->where('status', 'closed')->where('net_pnl', '>', 0)->count();
                            $losingTrades = $trades->where('status', 'closed')->where('net_pnl', '<', 0)->count();
                            $winRate = $closedTrades > 0 ? round(($winningTrades / $closedTrades) * 100, 1) : 0;
                            $totalPnL = $trades->where('status', 'closed')->sum('net_pnl');
                            $avgTradeDuration = $trades->where('status', 'closed')->where('trade_duration_seconds', '>', 0)->avg('trade_duration_seconds');
                        @endphp

                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $totalTrades }}</div>
                            <div class="text-sm text-gray-600">Total Trades</div>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $openTrades }}</div>
                            <div class="text-sm text-gray-600">Open Trades</div>
                        </div>

                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">{{ $winRate }}%</div>
                            <div class="text-sm text-gray-600">Win Rate</div>
                        </div>

                        <div class="bg-{{ $totalPnL >= 0 ? 'green' : 'red' }}-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-{{ $totalPnL >= 0 ? 'green' : 'red' }}-600">
                                ${{ number_format($totalPnL, 2) }}
                            </div>
                            <div class="text-sm text-gray-600">Total P&L</div>
                        </div>
                    </div>

                    @if($avgTradeDuration)
                        <div class="mt-4 text-sm text-gray-600">
                            <strong>Average Trade Duration:</strong> 
                            {{ floor($avgTradeDuration / 60) }}m {{ $avgTradeDuration % 60 }}s
                        </div>
                    @endif
                </div>
            </div>

            <!-- Trades Table -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Trade History</h3>
                        
                        <!-- Filter Controls -->
                        <div class="flex space-x-2">
                            <select class="border border-gray-300 rounded px-3 py-1 text-sm" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                            <select class="border border-gray-300 rounded px-3 py-1 text-sm" id="sideFilter">
                                <option value="">All Sides</option>
                                <option value="long">Long</option>
                                <option value="short">Short</option>
                            </select>
                        </div>
                    </div>

                    @if($trades->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Trade Info
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Prices
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Signal
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Performance
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($trades as $trade)
                                        <tr class="trade-row" 
                                            data-status="{{ $trade->status }}" 
                                            data-side="{{ $trade->side }}">
                                            <!-- Trade Info -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full flex items-center justify-center text-white text-sm font-bold
                                                            {{ $trade->side === 'long' ? 'bg-green-500' : 'bg-red-500' }}">
                                                            {{ strtoupper(substr($trade->side, 0, 1)) }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $trade->side === 'long' ? '↗️' : '↘️' }} {{ strtoupper($trade->side) }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            Qty: {{ number_format($trade->quantity, 4) }}
                                                        </div>
                                                        @if($trade->leverage > 1)
                                                            <div class="text-xs text-purple-600">
                                                                {{ $trade->leverage }}x Leverage
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Prices -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="space-y-1">
                                                    <div><strong>Entry:</strong> ${{ number_format($trade->entry_price, 4) }}</div>
                                                    @if($trade->exit_price)
                                                        <div><strong>Exit:</strong> ${{ number_format($trade->exit_price, 4) }}</div>
                                                    @endif
                                                    <div class="text-xs text-gray-500">
                                                        SL: ${{ number_format($trade->stop_loss, 4) }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        TP: ${{ number_format($trade->take_profit, 4) }}
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Signal -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="space-y-1">
                                                    <div class="font-medium">{{ ucfirst(str_replace('_', ' ', $trade->signal_type)) }}</div>
                                                    <div class="text-xs">
                                                        <span class="text-gray-600">Strength:</span>
                                                        <span class="font-medium">{{ number_format($trade->signal_strength, 2) }}</span>
                                                    </div>
                                                    <div class="text-xs">
                                                        <span class="text-gray-600">Score:</span>
                                                        <span class="font-medium">{{ number_format($trade->scalping_score, 2) }}</span>
                                                    </div>
                                                    @if($trade->confluence)
                                                        <div class="text-xs">
                                                            <span class="text-gray-600">Confluence:</span>
                                                            <span class="font-medium">{{ number_format($trade->confluence, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($trade->primary_timeframe)
                                                        <div class="text-xs text-blue-600">{{ $trade->primary_timeframe }}</div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Performance -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="space-y-1">
                                                    @if($trade->status === 'closed')
                                                        <div class="font-medium {{ $trade->net_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            ${{ number_format($trade->net_pnl, 2) }}
                                                        </div>
                                                        <div class="text-xs {{ $trade->pnl_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ number_format($trade->pnl_percentage, 2) }}%
                                                        </div>
                                                    @else
                                                        <div class="font-medium {{ $trade->unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            ${{ number_format($trade->unrealized_pnl, 2) }}
                                                        </div>
                                                        <div class="text-xs text-gray-600">Unrealized</div>
                                                    @endif
                                                    
                                                    @if($trade->trade_duration_seconds)
                                                        <div class="text-xs text-gray-500">
                                                            {{ $trade->trade_duration }}
                                                        </div>
                                                    @endif
                                                    
                                                    @if($trade->max_favorable_excursion)
                                                        <div class="text-xs text-green-500">
                                                            Best: +{{ number_format($trade->max_favorable_excursion, 2) }}%
                                                        </div>
                                                    @endif
                                                    
                                                    @if($trade->max_adverse_excursion)
                                                        <div class="text-xs text-red-500">
                                                            Worst: {{ number_format($trade->max_adverse_excursion, 2) }}%
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Status -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="space-y-2">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        {{ $trade->status === 'open' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                                        {{ ucfirst($trade->status) }}
                                                    </span>
                                                    
                                                    @if($trade->was_trailing_stop_used)
                                                        <div class="text-xs text-blue-600">
                                                            <i class="fas fa-chart-line mr-1"></i>Trailing
                                                        </div>
                                                    @endif
                                                    
                                                    @if($trade->was_quick_exit)
                                                        <div class="text-xs text-orange-600">
                                                            <i class="fas fa-bolt mr-1"></i>Quick Exit
                                                        </div>
                                                    @endif
                                                    
                                                    @if($trade->exit_reason && $trade->status === 'closed')
                                                        <div class="text-xs text-gray-500">
                                                            {{ ucfirst(str_replace('_', ' ', $trade->exit_reason)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Actions -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    @if($trade->status === 'open')
                                                        <button class="text-red-600 hover:text-red-900 text-xs bg-red-100 hover:bg-red-200 px-2 py-1 rounded"
                                                                onclick="closePosition('{{ $trade->id }}')">
                                                            <i class="fas fa-times mr-1"></i>Close
                                                        </button>
                                                    @endif
                                                    
                                                    <button class="text-blue-600 hover:text-blue-900 text-xs bg-blue-100 hover:bg-blue-200 px-2 py-1 rounded"
                                                            onclick="viewTradeDetails('{{ $trade->id }}')">
                                                        <i class="fas fa-eye mr-1"></i>Details
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $trades->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-500 text-lg mb-2">
                                <i class="fas fa-chart-line fa-3x mb-4"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No trades yet</h3>
                            <p class="text-gray-500">
                                This scalping bot hasn't executed any trades yet. Make sure the bot is active and has proper signal generation.
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
        </div>
    </div>

    <!-- Trade Details Modal -->
    <div id="tradeDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Trade Details</h3>
                    <button onclick="closeTradeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>
                <div id="tradeDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTrades();
        });

        document.getElementById('sideFilter').addEventListener('change', function() {
            filterTrades();
        });

        function filterTrades() {
            const statusFilter = document.getElementById('statusFilter').value;
            const sideFilter = document.getElementById('sideFilter').value;
            const rows = document.querySelectorAll('.trade-row');

            rows.forEach(row => {
                const status = row.dataset.status;
                const side = row.dataset.side;
                
                let showRow = true;
                
                if (statusFilter && status !== statusFilter) {
                    showRow = false;
                }
                
                if (sideFilter && side !== sideFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        function closePosition(tradeId) {
            if (confirm('Are you sure you want to close this position?')) {
                // Implementation would go here - call API to close position
                console.log('Closing position:', tradeId);
                
                // For now, just show a message
                alert('Position close request sent. This would implement the actual closing logic.');
            }
        }

        function viewTradeDetails(tradeId) {
            // Show loading state
            document.getElementById('tradeDetailsContent').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            document.getElementById('tradeDetailsModal').classList.remove('hidden');
            
            // In a real implementation, fetch trade details via AJAX
            setTimeout(() => {
                document.getElementById('tradeDetailsContent').innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-gray-600">Detailed trade analysis for Trade #${tradeId} would be shown here.</p>
                        <p class="text-sm text-gray-500 mt-2">This would include:</p>
                        <ul class="text-sm text-gray-500 text-left mt-2 list-disc list-inside">
                            <li>Entry market conditions</li>
                            <li>Signal analysis breakdown</li>
                            <li>Exit analysis and optimization</li>
                            <li>Performance metrics</li>
                            <li>Learning insights</li>
                        </ul>
                    </div>
                `;
            }, 500);
        }

        function closeTradeModal() {
            document.getElementById('tradeDetailsModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('tradeDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTradeModal();
            }
        });
    </script>
    @endpush
</x-app-layout>
