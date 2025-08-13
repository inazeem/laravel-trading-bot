<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $asset->name }} ({{ $asset->formatted_symbol }})
            </h2>
            <div class="flex space-x-4">
                <a href="{{ route('assets.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Back to Trading
                </a>
                <a href="{{ route('assets.portfolio') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    My Portfolio
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

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Asset Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="h-20 w-20 mx-auto rounded-full bg-blue-100 flex items-center justify-center mb-4">
                                <span class="text-2xl font-bold text-blue-800">
                                    {{ substr($asset->symbol, 0, 2) }}
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $asset->name }}</h3>
                            <p class="text-gray-600">{{ $asset->formatted_symbol }}</p>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 mt-2">
                                {{ ucfirst($asset->type) }}
                            </span>
                        </div>
                        
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Current Price</h4>
                            <p class="text-3xl font-bold text-gray-900">{{ $asset->formatted_price }}</p>
                        </div>
                        
                        <div class="text-center">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Your Holdings</h4>
                            @if($userHolding && $userHolding->quantity > 0)
                                <p class="text-2xl font-bold text-gray-900">{{ $userHolding->formatted_quantity }}</p>
                                <p class="text-sm text-gray-600">Value: {{ $userHolding->formatted_current_value }}</p>
                            @else
                                <p class="text-lg text-gray-500">No holdings</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trading Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Trade {{ $asset->formatted_symbol }}</h3>
                    <div class="flex space-x-4">
                        <button onclick="openBuyModal('{{ $asset->id }}', '{{ $asset->symbol }}', '{{ $asset->current_price }}')" 
                                class="flex-1 bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg">
                            Buy {{ $asset->formatted_symbol }}
                        </button>
                        @if($userHolding && $userHolding->quantity > 0)
                            <button onclick="openSellModal('{{ $asset->id }}', '{{ $asset->symbol }}', '{{ $asset->current_price }}', '{{ $userHolding->quantity }}')" 
                                    class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg">
                                Sell {{ $asset->formatted_symbol }}
                            </button>
                        @else
                            <button disabled class="flex-1 bg-gray-300 text-gray-500 font-bold py-3 px-6 rounded-lg cursor-not-allowed">
                                Sell {{ $asset->formatted_symbol }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- User Holdings Details -->
            @if($userHolding && $userHolding->quantity > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Your {{ $asset->formatted_symbol }} Holdings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="text-center">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Quantity</h4>
                                <p class="text-xl font-bold text-gray-900">{{ $userHolding->formatted_quantity }}</p>
                            </div>
                            <div class="text-center">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Average Buy Price</h4>
                                <p class="text-xl font-bold text-gray-900">{{ $userHolding->formatted_average_buy_price }}</p>
                            </div>
                            <div class="text-center">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Total Invested</h4>
                                <p class="text-xl font-bold text-gray-900">{{ $userHolding->formatted_total_invested }}</p>
                            </div>
                            <div class="text-center">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Current Value</h4>
                                <p class="text-xl font-bold text-gray-900">{{ $userHolding->formatted_current_value }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="text-center">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Profit/Loss</h4>
                                <p class="text-xl font-bold {{ $userHolding->profit_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $userHolding->formatted_profit_loss }}
                                </p>
                            </div>
                            <div class="text-center">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Profit/Loss %</h4>
                                <p class="text-xl font-bold {{ $userHolding->profit_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $userHolding->formatted_profit_loss_percentage }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recent Transactions -->
            @if($recentTransactions->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent {{ $asset->formatted_symbol }} Transactions</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantity
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Price per Unit
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentTransactions as $transaction)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $transaction->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $transaction->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($transaction->type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $transaction->formatted_quantity }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $transaction->formatted_price_per_unit }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $transaction->formatted_total_amount }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
                <h3 class="text-lg font-medium text-gray-900 mb-4">Buy {{ $asset->formatted_symbol }}</h3>
                <form id="buyForm" method="POST" action="{{ route('assets.buy') }}">
                    @csrf
                    <input type="hidden" id="buyAssetId" name="asset_id" value="{{ $asset->id }}">
                    
                    <div class="mb-4">
                        <label for="buyApiKeyId" class="block text-sm font-medium text-gray-700 mb-2">Exchange API Key</label>
                        <select id="buyApiKeyId" name="api_key_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select API Key</option>
                            @foreach($apiKeys as $apiKey)
                                <option value="{{ $apiKey->id }}">{{ ucfirst($apiKey->exchange) }} - {{ $apiKey->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asset</label>
                        <input type="text" id="buyAssetSymbol" value="{{ $asset->symbol }}" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="mb-4">
                        <label for="buyQuantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" id="buyQuantity" name="quantity" step="0.00000001" min="0.00000001" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label for="buyPricePerUnit" class="block text-sm font-medium text-gray-700 mb-2">Price per Unit ($)</label>
                        <input type="number" id="buyPricePerUnit" name="price_per_unit" step="0.01" min="0.01" required 
                               value="{{ $asset->current_price }}"
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
                            Buy {{ $asset->formatted_symbol }}
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
                <h3 class="text-lg font-medium text-gray-900 mb-4">Sell {{ $asset->formatted_symbol }}</h3>
                <form id="sellForm" method="POST" action="{{ route('assets.sell') }}">
                    @csrf
                    <input type="hidden" id="sellAssetId" name="asset_id" value="{{ $asset->id }}">
                    
                    <div class="mb-4">
                        <label for="sellApiKeyId" class="block text-sm font-medium text-gray-700 mb-2">Exchange API Key</label>
                        <select id="sellApiKeyId" name="api_key_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select API Key</option>
                            @foreach($apiKeys as $apiKey)
                                <option value="{{ $apiKey->id }}">{{ ucfirst($apiKey->exchange) }} - {{ $apiKey->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asset</label>
                        <input type="text" id="sellAssetSymbol" value="{{ $asset->symbol }}" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Available Quantity</label>
                        <input type="text" id="sellAvailableQuantity" value="{{ $userHolding ? $userHolding->quantity : 0 }}" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>

                    <div class="mb-4">
                        <label for="sellQuantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity to Sell</label>
                        <input type="number" id="sellQuantity" name="quantity" step="0.00000001" min="0.00000001" required 
                               max="{{ $userHolding ? $userHolding->quantity : 0 }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label for="sellPricePerUnit" class="block text-sm font-medium text-gray-700 mb-2">Price per Unit ($)</label>
                        <input type="number" id="sellPricePerUnit" name="price_per_unit" step="0.01" min="0.01" required 
                               value="{{ $asset->current_price }}"
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
                            Sell {{ $asset->formatted_symbol }}
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
            document.getElementById('buyAssetId').value = '{{ $asset->id }}';
            document.getElementById('buyAssetSymbol').value = '{{ $asset->symbol }}';
            document.getElementById('buyPricePerUnit').value = '{{ $asset->current_price }}';
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
            document.getElementById('sellAssetId').value = '{{ $asset->id }}';
            document.getElementById('sellAssetSymbol').value = '{{ $asset->symbol }}';
            document.getElementById('sellAvailableQuantity').value = '{{ $userHolding ? $userHolding->quantity : 0 }}';
            document.getElementById('sellPricePerUnit').value = '{{ $asset->current_price }}';
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
