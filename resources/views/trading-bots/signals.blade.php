<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Trading Bot Signals') }}
            </h2>
            <a href="{{ route('trading-bots.show', $tradingBot) }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to Bot
            </a>
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

            <!-- Bot Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $tradingBot->name }}</h1>
                            <p class="text-gray-600">{{ $tradingBot->symbol }} on {{ ucfirst($tradingBot->exchange) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Signals</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $signals->total() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signal Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-chart-line text-green-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Executed Signals</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signals->where('is_executed', true)->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-long-arrow-alt-up text-blue-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Buy Signals</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signals->where('type', 'buy')->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-long-arrow-alt-down text-red-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Sell Signals</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signals->where('type', 'sell')->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-percentage text-yellow-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Success Rate</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $signals->count() > 0 ? number_format(($signals->where('is_executed', true)->count() / $signals->count()) * 100, 1) : 0 }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signals Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">All Signals</h3>
                        <div class="text-sm text-gray-500">
                            Showing {{ $signals->firstItem() ?? 0 }} to {{ $signals->lastItem() ?? 0 }} of {{ $signals->total() }} signals
                        </div>
                    </div>
                    
                    @if($signals->count() > 0)
                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timeframe</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Signal Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Strength</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stop Loss</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Take Profit</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($signals as $signal)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $signal->timeframe }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $signal->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($signal->type) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $signal->signal_type ?? 'N/A' }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ is_numeric($signal->strength) ? min(100, $signal->strength * 100) : 50 }}%"></div>
                                                    </div>
                                                    <span class="text-xs">{{ is_numeric($signal->strength) ? number_format($signal->strength * 100, 1) : ucfirst($signal->strength) }}%</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($signal->price, 2) }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $signal->stop_loss ? '$' . number_format($signal->stop_loss, 2) : '-' }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $signal->take_profit ? '$' . number_format($signal->take_profit, 2) : '-' }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $signal->is_executed ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $signal->is_executed ? 'Executed' : 'Pending' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $signal->created_at->format('M d, H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="md:hidden space-y-4">
                            @foreach($signals as $signal)
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                {{ $signal->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($signal->type) }}
                                            </span>
                                            <span class="text-sm text-gray-500">{{ $signal->timeframe }}</span>
                                        </div>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $signal->is_executed ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $signal->is_executed ? 'Executed' : 'Pending' }}
                                        </span>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        @if($signal->signal_type)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Signal Type:</span>
                                            <span class="text-sm font-medium">{{ $signal->signal_type }}</span>
                                        </div>
                                        @endif
                                        
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Strength:</span>
                                            <div class="flex items-center">
                                                <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ is_numeric($signal->strength) ? min(100, $signal->strength * 100) : 50 }}%"></div>
                                                </div>
                                                <span class="text-xs">{{ is_numeric($signal->strength) ? number_format($signal->strength * 100, 1) : ucfirst($signal->strength) }}%</span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Price:</span>
                                            <span class="text-sm font-medium">${{ number_format($signal->price, 2) }}</span>
                                        </div>
                                        
                                        @if($signal->stop_loss)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Stop Loss:</span>
                                            <span class="text-sm font-medium">${{ number_format($signal->stop_loss, 2) }}</span>
                                        </div>
                                        @endif
                                        
                                        @if($signal->take_profit)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Take Profit:</span>
                                            <span class="text-sm font-medium">${{ number_format($signal->take_profit, 2) }}</span>
                                        </div>
                                        @endif
                                        
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Date:</span>
                                            <span class="text-sm text-gray-500">{{ $signal->created_at->format('M d, Y H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6 flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                Showing {{ $signals->firstItem() ?? 0 }} to {{ $signals->lastItem() ?? 0 }} of {{ $signals->total() }} signals
                            </div>
                            <div>
                                {{ $signals->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-gray-400 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No signals found</h3>
                            <p class="text-gray-500">This bot hasn't generated any signals yet. Signals will appear here once the bot starts running.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Ensure progress bars don't overflow */
        .bg-blue-600 {
            max-width: 100% !important;
        }
        
        /* Prevent horizontal scroll on table */
        .overflow-x-auto {
            max-width: 100%;
        }
        
        /* Ensure table cells don't expand too much */
        .whitespace-nowrap {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    <script>
        // Add some interactivity to the signals page
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.classList.add('bg-gray-50');
                });
                row.addEventListener('mouseleave', function() {
                    this.classList.remove('bg-gray-50');
                });
            });

            // Add click to expand mobile cards (optional)
            const mobileCards = document.querySelectorAll('.md\\:hidden .bg-gray-50');
            mobileCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.classList.toggle('ring-2');
                    this.classList.toggle('ring-blue-500');
                });
            });

            // Fix any progress bars that might still be too wide
            const progressBars = document.querySelectorAll('.bg-blue-600');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                if (width && parseFloat(width) > 100) {
                    bar.style.width = '100%';
                }
            });
        });
    </script>
</x-app-layout>
