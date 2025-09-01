<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('⚡ Scalping Trading Bots') }}
            </h2>
            <a href="{{ route('scalping-bots.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-plus mr-2"></i>Create Scalping Bot
            </a>
        </div>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">


    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-robot text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Bots</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $activeBots }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total PnL</p>
                    <p class="text-2xl font-semibold {{ $totalPnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ${{ number_format($totalPnl, 2) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-exchange-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Trades</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalTrades) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-percentage text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Avg Win Rate</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($avgWinRate, 1) }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bots List -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Your Scalping Bots</h2>
        </div>

        @if($bots->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bot</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recent Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Settings</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($bots as $bot)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-bolt text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $bot->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $bot->symbol }} • {{ ucfirst($bot->exchange) }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        @if($bot->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                                Inactive
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-500 mt-1">{{ ucfirst($bot->status) }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div class="font-medium {{ $bot->total_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            ${{ number_format($bot->total_pnl, 2) }}
                                        </div>
                                        <div class="text-gray-500">
                                            {{ $bot->total_trades }} trades • {{ number_format($bot->win_rate, 1) }}% win rate
                                        </div>
                                        @if($bot->profit_factor > 0)
                                            <div class="text-xs text-gray-400">
                                                PF: {{ number_format($bot->profit_factor, 2) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div>{{ $bot->openTrades->count() }} open positions</div>
                                        <div class="text-gray-500">
                                            @if($bot->last_run_at)
                                                Last run: {{ $bot->last_run_at->diffForHumans() }}
                                            @else
                                                Never run
                                            @endif
                                        </div>
                                        @if($bot->unrealized_pnl != 0)
                                            <div class="text-xs {{ $bot->unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                Unrealized: ${{ number_format($bot->unrealized_pnl, 2) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div>{{ $bot->leverage }}x leverage</div>
                                        <div class="text-gray-500">
                                            SL: {{ $bot->stop_loss_percentage }}% • TP: {{ $bot->take_profit_percentage }}%
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ implode(', ', $bot->timeframes) }}
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('scalping-bots.show', $bot) }}" 
                                           class="text-blue-600 hover:text-blue-900"
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('scalping-bots.edit', $bot) }}" 
                                           class="text-yellow-600 hover:text-yellow-900"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form action="{{ route('scalping-bots.toggle', $bot) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="{{ $bot->is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' }}"
                                                    title="{{ $bot->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="fas {{ $bot->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                        </form>

                                        @if($bot->is_active)
                                            <form action="{{ route('scalping-bots.run', $bot) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="text-purple-600 hover:text-purple-900"
                                                        title="Run Now">
                                                    <i class="fas fa-bolt"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" 
                                                    class="text-gray-600 hover:text-gray-900"
                                                    title="More Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="open" 
                                                 @click.away="open = false"
                                                 x-transition
                                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                                <div class="py-1">
                                                    <a href="{{ route('scalping-bots.trades', $bot) }}" 
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-chart-line mr-2"></i>View Trades
                                                    </a>
                                                    <a href="{{ route('scalping-bots.signals', $bot) }}" 
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-signal mr-2"></i>View Signals
                                                    </a>
                                                    @if($bot->openTrades->count() > 0)
                                                        <form action="{{ route('scalping-bots.close-all-positions', $bot) }}" 
                                                              method="POST" 
                                                              onsubmit="return confirm('Are you sure you want to close all positions?')">
                                                            @csrf
                                                            <button type="submit" 
                                                                    class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100">
                                                                <i class="fas fa-times-circle mr-2"></i>Close All Positions
                                                            </button>
                                                        </form>
                                                    @else
                                                        <div class="border-t border-gray-100"></div>
                                                        <form action="{{ route('scalping-bots.destroy', $bot) }}" 
                                                              method="POST" 
                                                              onsubmit="return confirm('Are you sure you want to delete this scalping bot? This action cannot be undone.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100">
                                                                <i class="fas fa-trash mr-2"></i>Delete Bot
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-robot text-4xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No scalping bots</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first scalping bot.</p>
                <div class="mt-6">
                    <a href="{{ route('scalping-bots.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Create Scalping Bot
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

            </div>
        </div>
    </div>


</x-app-layout>
