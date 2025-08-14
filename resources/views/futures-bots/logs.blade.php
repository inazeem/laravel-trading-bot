<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $futuresBot->name }} - Futures Execution Logs
            </h2>
            <div class="flex space-x-3">
                <form method="POST" action="{{ route('futures-bots.clear-logs', $futuresBot) }}" class="inline" onsubmit="return confirm('Are you sure you want to clear all logs for this futures trading bot? This action cannot be undone.')">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-200 ease-in-out">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear Logs
                    </button>
                </form>
                <a href="{{ route('futures-bots.show', $futuresBot) }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Bot
                </a>
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

            <!-- Bot Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Exchange</h4>
                            <p class="text-lg font-semibold text-gray-900">{{ ucfirst($futuresBot->exchange) }}</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Symbol</h4>
                            <p class="text-lg font-semibold text-gray-900">{{ $futuresBot->symbol }}</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Leverage</h4>
                            <p class="text-lg font-semibold text-gray-900">{{ $futuresBot->leverage }}x</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Status</h4>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $futuresBot->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $futuresBot->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Total Logs</h4>
                            <p class="text-lg font-semibold text-gray-900">{{ $logs->total() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Last Run Summary -->
            @if(!empty($summary))
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Last Run Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Start Time</h4>
                            <p class="text-sm text-gray-900">{{ $summary['start_time']->format('M d, Y H:i:s') }}</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">End Time</h4>
                            <p class="text-sm text-gray-900">{{ $summary['end_time'] ? $summary['end_time']->format('M d, Y H:i:s') : 'Running...' }}</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Duration</h4>
                            <p class="text-sm text-gray-900">{{ $summary['duration'] ? $summary['duration'] . 's' : 'N/A' }}</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Total Logs</h4>
                            <p class="text-sm text-gray-900">{{ $summary['total_logs'] }}</p>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Errors</h4>
                            <p class="text-sm {{ $summary['errors'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $summary['errors'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Logs Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Futures Execution Logs</h3>
                        <div class="flex space-x-2">
                            <select id="levelFilter" class="border border-gray-300 rounded px-3 py-1 text-sm">
                                <option value="">All Levels</option>
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                                <option value="debug">Debug</option>
                            </select>
                            <select id="categoryFilter" class="border border-gray-300 rounded px-3 py-1 text-sm">
                                <option value="">All Categories</option>
                                <option value="execution">Execution</option>
                                <option value="config">Config</option>
                                <option value="price">Price</option>
                                <option value="analysis">Analysis</option>
                                <option value="signals">Signals</option>
                                <option value="candles">Candles</option>
                                <option value="error">Error</option>
                                <option value="general">General</option>
                            </select>
                        </div>
                    </div>
                    
                    @if($logs->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($logs as $log)
                                        <tr class="log-row" data-level="{{ $log->level }}" data-category="{{ $log->category }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->logged_at->format('M d, Y H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $log->level === 'error' ? 'bg-red-100 text-red-800' : 
                                                       ($log->level === 'warning' ? 'bg-yellow-100 text-yellow-800' : 
                                                       ($log->level === 'debug' ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800')) }}">
                                                    {{ ucfirst($log->level) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ ucfirst($log->category ?? 'general') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <div class="font-mono text-xs">{{ $log->message }}</div>
                                                @if($log->context)
                                                    <details class="mt-2">
                                                        <summary class="text-xs text-gray-500 cursor-pointer">Context</summary>
                                                        <pre class="text-xs text-gray-600 mt-1 bg-gray-50 p-2 rounded">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                                                    </details>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $logs->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No logs</h3>
                            <p class="mt-1 text-sm text-gray-500">This futures trading bot hasn't generated any logs yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple client-side filtering
        document.getElementById('levelFilter').addEventListener('change', filterLogs);
        document.getElementById('categoryFilter').addEventListener('change', filterLogs);

        function filterLogs() {
            const levelFilter = document.getElementById('levelFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const rows = document.querySelectorAll('.log-row');

            rows.forEach(row => {
                const level = row.dataset.level;
                const category = row.dataset.category;
                
                const levelMatch = !levelFilter || level === levelFilter;
                const categoryMatch = !categoryFilter || category === categoryFilter;
                
                row.style.display = levelMatch && categoryMatch ? '' : 'none';
            });
        }
    </script>
</x-app-layout>
