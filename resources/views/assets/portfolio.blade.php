<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Portfolio') }}
            </h2>
            <div class="flex space-x-4">
                <a href="{{ route('assets.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Trade Assets
                </a>
                <a href="{{ route('assets.transactions') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Transaction History
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Portfolio Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Portfolio Value</dt>
                                    <dd class="text-lg font-medium text-gray-900">${{ number_format($totalPortfolioValue, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Invested</dt>
                                    <dd class="text-lg font-medium text-gray-900">${{ number_format($totalInvested, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 {{ $totalProfitLoss >= 0 ? 'text-green-500' : 'text-red-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total P&L</dt>
                                    <dd class="text-lg font-medium {{ $totalProfitLoss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $totalProfitLoss >= 0 ? '+' : '' }}${{ number_format($totalProfitLoss, 2) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Assets (>$10)</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $holdings->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($holdings->count() > 0)
                <!-- Assets Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Assets Worth More Than $10</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Asset
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantity
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Avg Buy Price
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Current Price
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Current Value
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Invested
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            P&L
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            P&L %
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($holdings as $holding)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                            <span class="text-sm font-medium text-blue-800">
                                                                {{ substr($holding->asset->symbol, 0, 2) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $holding->asset->formatted_symbol }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ $holding->asset->name }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $holding->formatted_quantity }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $holding->formatted_average_buy_price }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $holding->asset->formatted_price }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $holding->formatted_current_value }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $holding->formatted_total_invested }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $holding->profit_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $holding->formatted_profit_loss }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $holding->profit_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $holding->formatted_profit_loss_percentage }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button onclick="openBuyModal('{{ $holding->asset->id }}', '{{ $holding->asset->symbol }}', '{{ $holding->asset->current_price }}')" 
                                                            class="text-green-600 hover:text-green-900 bg-green-100 hover:bg-green-200 px-2 py-1 rounded text-xs">
                                                        Buy More
                                                    </button>
                                                    <button onclick="openSellModal('{{ $holding->asset->id }}', '{{ $holding->asset->symbol }}', '{{ $holding->asset->current_price }}', '{{ $holding->quantity }}')" 
                                                            class="text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 px-2 py-1 rounded text-xs">
                                                        Sell
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No assets worth more than $10</h3>
                        <p class="mt-1 text-sm text-gray-500">Start trading to build your portfolio!</p>
                        <div class="mt-6">
                            <a href="{{ route('assets.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Start Trading
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Buy Modal -->
    <div id="buyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Buy Asset</h3>
                <form id="buyForm" method="POST" action="{{ route('assets.buy') }}">
                    @csrf
                    <input type="hidden" id="buyAssetId" name="asset_id">
                    
                    <div class="mb-4">
                        <label for="buyApiKeyId" class="block text-sm font-medium text-gray-700 mb-2">Exchange API Key</label>
                        <select id="buyApiKeyId" name="api_key_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select API Key</option>
                            @foreach(auth()->user()->apiKeys()->where('is_active', true)->get() as $apiKey)
                                <option value="{{ $apiKey->id }}">{{ ucfirst($apiKey->exchange) }} - {{ $apiKey->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asset</label>
                        <input type="text" id="buyAssetSymbol" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="mb-4">
                        <label for="buyQuantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" id="buyQuantity" name="quantity" step="0.00000001" min="0.00000001" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label for="buyPricePerUnit" class="block text-sm font-medium text-gray-700 mb-2">Price per Unit ($)</label>
                        <input type="number" id="buyPricePerUnit" name="price_per_unit" step="0.01" min="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label for="buyTotalAmount" class="block text-sm font-medium text-gray-700 mb-2">Total Amount ($)</label>
                        <input type="text" id="buyTotalAmount" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="mb-4">
                        <label for="buyNotes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea id="buyNotes" name="notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeBuyModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                            Buy Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sell Modal -->
    <div id="sellModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Sell Asset</h3>
                <form id="sellForm" method="POST" action="{{ route('assets.sell') }}">
                    @csrf
                    <input type="hidden" id="sellAssetId" name="asset_id">
                    
                    <div class="mb-4">
                        <label for="sellApiKeyId" class="block text-sm font-medium text-gray-700 mb-2">Exchange API Key</label>
                        <select id="sellApiKeyId" name="api_key_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select API Key</option>
                            @foreach(auth()->user()->apiKeys()->where('is_active', true)->get() as $apiKey)
                                <option value="{{ $apiKey->id }}">{{ ucfirst($apiKey->exchange) }} - {{ $apiKey->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asset</label>
                        <input type="text" id="sellAssetSymbol" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Available Quantity</label>
                        <input type="text" id="sellAvailableQuantity" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="mb-4">
                        <label for="sellQuantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity to Sell</label>
                        <input type="number" id="sellQuantity" name="quantity" step="0.00000001" min="0.00000001" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label for="sellPricePerUnit" class="block text-sm font-medium text-gray-700 mb-2">Price per Unit ($)</label>
                        <input type="number" id="sellPricePerUnit" name="price_per_unit" step="0.01" min="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label for="sellTotalAmount" class="block text-sm font-medium text-gray-700 mb-2">Total Amount ($)</label>
                        <input type="text" id="sellTotalAmount" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="mb-4">
                        <label for="sellNotes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea id="sellNotes" name="notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeSellModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                            Sell Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openBuyModal(assetId, symbol, currentPrice) {
            document.getElementById('buyAssetId').value = assetId;
            document.getElementById('buyAssetSymbol').value = symbol;
            document.getElementById('buyPricePerUnit').value = currentPrice;
            document.getElementById('buyModal').classList.remove('hidden');
            
            // Calculate total amount
            calculateBuyTotal();
        }

        function closeBuyModal() {
            document.getElementById('buyModal').classList.add('hidden');
            document.getElementById('buyForm').reset();
        }

        function openSellModal(assetId, symbol, currentPrice, availableQuantity) {
            document.getElementById('sellAssetId').value = assetId;
            document.getElementById('sellAssetSymbol').value = symbol;
            document.getElementById('sellAvailableQuantity').value = availableQuantity;
            document.getElementById('sellPricePerUnit').value = currentPrice;
            document.getElementById('sellQuantity').max = availableQuantity;
            document.getElementById('sellModal').classList.remove('hidden');
            
            // Calculate total amount
            calculateSellTotal();
        }

        function closeSellModal() {
            document.getElementById('sellModal').classList.add('hidden');
            document.getElementById('sellForm').reset();
        }

        function calculateBuyTotal() {
            const quantity = document.getElementById('buyQuantity').value;
            const pricePerUnit = document.getElementById('buyPricePerUnit').value;
            const total = quantity * pricePerUnit;
            document.getElementById('buyTotalAmount').value = total.toFixed(2);
        }

        function calculateSellTotal() {
            const quantity = document.getElementById('sellQuantity').value;
            const pricePerUnit = document.getElementById('sellPricePerUnit').value;
            const total = quantity * pricePerUnit;
            document.getElementById('sellTotalAmount').value = total.toFixed(2);
        }

        // Event listeners for real-time calculation
        document.getElementById('buyQuantity').addEventListener('input', calculateBuyTotal);
        document.getElementById('buyPricePerUnit').addEventListener('input', calculateBuyTotal);
        document.getElementById('sellQuantity').addEventListener('input', calculateSellTotal);
        document.getElementById('sellPricePerUnit').addEventListener('input', calculateSellTotal);

        // Close modals when clicking outside
        window.onclick = function(event) {
            const buyModal = document.getElementById('buyModal');
            const sellModal = document.getElementById('sellModal');
            
            if (event.target === buyModal) {
                closeBuyModal();
            }
            if (event.target === sellModal) {
                closeSellModal();
            }
        }
    </script>
</x-app-layout>
