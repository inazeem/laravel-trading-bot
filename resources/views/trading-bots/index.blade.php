<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Trading Bots') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('api-keys.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-200 ease-in-out">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    API Keys
                </a>
                <a href="{{ route('trading-bots.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-200 ease-in-out transform hover:scale-105">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create New Bot
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

            <!-- View Toggle -->
            <div class="mb-4 flex justify-between items-center">
                <div class="flex space-x-2">
                    <button id="tableViewBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">
                        <i class="fas fa-table mr-1"></i> Table View
                    </button>
                    <button id="cardViewBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium">
                        <i class="fas fa-th-large mr-1"></i> Card View
                    </button>
                </div>
                <div class="text-sm text-gray-600">
                    {{ $bots->total() }} bot(s) found
                </div>
            </div>

            <!-- Table View -->
            <div id="tableView" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($bots->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exchange</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Holdings</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">USDT Balance</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trades</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Signals</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Run</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($bots as $bot)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $bot->name }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $bot->exchange === 'kucoin' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ ucfirst($bot->exchange) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $bot->symbol }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $bot->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $bot->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <div class="font-medium">{{ number_format($bot->asset_quantity, 6) }} {{ explode('-', $bot->symbol)[0] }}</div>
                                                    @if($bot->asset_average_price > 0)
                                                        <div class="text-xs text-gray-500">Avg: ${{ number_format($bot->asset_average_price, 4) }}</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <div class="font-medium">${{ number_format($bot->usdt_balance, 2) }}</div>
                                                    <div class="text-xs text-gray-500">Available</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $bot->trades_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $bot->signals_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $bot->last_run_at ? $bot->last_run_at->diffForHumans() : 'Never' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('trading-bots.show', $bot) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                                    <a href="{{ route('trading-bots.edit', $bot) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                                    <a href="{{ route('trading-bots.logs', $bot) }}" class="text-green-600 hover:text-green-900">Logs</a>
                                                    
                                                    @if($bot->is_active)
                                                        <form method="POST" action="{{ route('trading-bots.run', $bot) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-xs">Start</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('trading-bots.toggle-status', $bot) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-xs">Stop</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('trading-bots.toggle-status', $bot) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-xs">Start</button>
                                                        </form>
                                                    @endif
                                                    
                                                    <form method="POST" action="{{ route('trading-bots.destroy', $bot) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-xs">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            {{ $bots->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="mb-6">
                                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No trading bots found</h3>
                            <p class="text-gray-500 mb-6">Get started by creating your first trading bot to automate your cryptocurrency trading strategy.</p>
                            <div class="space-y-3">
                                <a href="{{ route('trading-bots.create') }}" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-200 ease-in-out transform hover:scale-105">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Create Your First Bot
                                </a>
                                <div class="text-sm text-gray-500">
                                    <p>Don't have API keys yet? <a href="{{ route('api-keys.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Add API keys first</a></p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card View -->
            <div id="cardView" class="hidden">
                @if($bots->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($bots as $bot)
                            <x-spot-bot-card :bot="$bot" />
                        @endforeach
                    </div>
                    
                    <div class="mt-6">
                        {{ $bots->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="mb-6">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No trading bots found</h3>
                        <p class="text-gray-500 mb-6">Get started by creating your first trading bot to automate your cryptocurrency trading strategy.</p>
                        <div class="space-y-3">
                            <a href="{{ route('trading-bots.create') }}" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-200 ease-in-out transform hover:scale-105">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create Your First Bot
                            </a>
                            <div class="text-sm text-gray-500">
                                <p>Don't have API keys yet? <a href="{{ route('api-keys.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Add API keys first</a></p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // View toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tableViewBtn = document.getElementById('tableViewBtn');
            const cardViewBtn = document.getElementById('cardViewBtn');
            const tableView = document.getElementById('tableView');
            const cardView = document.getElementById('cardView');

            function showTableView() {
                tableView.classList.remove('hidden');
                cardView.classList.add('hidden');
                tableViewBtn.classList.remove('bg-gray-200', 'text-gray-700');
                tableViewBtn.classList.add('bg-blue-600', 'text-white');
                cardViewBtn.classList.remove('bg-blue-600', 'text-white');
                cardViewBtn.classList.add('bg-gray-200', 'text-gray-700');
            }

            function showCardView() {
                tableView.classList.add('hidden');
                cardView.classList.remove('hidden');
                cardViewBtn.classList.remove('bg-gray-200', 'text-gray-700');
                cardViewBtn.classList.add('bg-blue-600', 'text-white');
                tableViewBtn.classList.remove('bg-blue-600', 'text-white');
                tableViewBtn.classList.add('bg-gray-200', 'text-gray-700');
            }

            tableViewBtn.addEventListener('click', showTableView);
            cardViewBtn.addEventListener('click', showCardView);
        });
    </script>
</x-app-layout>
