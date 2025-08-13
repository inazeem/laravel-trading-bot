<x-app-layout>
    <x-slot name="header">
                    <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Asset Trading') }}
                </h2>
                <div class="flex space-x-4">
                    @if($apiKeys->count() > 0)
                        <form method="POST" action="{{ route('assets.sync') }}" class="inline">
                            @csrf
                            <select name="api_key_id" class="mr-2 px-3 py-2 border border-gray-300 rounded-md">
                                @foreach($apiKeys as $apiKey)
                                    <option value="{{ $apiKey->id }}">{{ ucfirst($apiKey->exchange) }} - {{ $apiKey->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                Sync Assets
                            </button>
                        </form>
                        <form method="POST" action="{{ route('assets.update-prices') }}" class="inline">
                            @csrf
                            <select name="api_key_id" class="mr-2 px-3 py-2 border border-gray-300 rounded-md">
                                @foreach($apiKeys as $apiKey)
                                    <option value="{{ $apiKey->id }}">{{ ucfirst($apiKey->exchange) }} - {{ $apiKey->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
                                Update Prices
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('assets.portfolio') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        My Portfolio
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

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            @if($apiKeys->count() == 0)
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                    <strong>Warning:</strong> No active API keys found. Please add API keys for your exchange accounts to enable trading.
                    <a href="{{ route('api-keys.index') }}" class="underline ml-2">Manage API Keys</a>
                </div>
            @endif

            <!-- Available Assets Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Available Assets</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($assets as $asset)
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-semibold text-lg">{{ $asset->formatted_symbol }}</h4>
                                        <p class="text-gray-600 text-sm">{{ $asset->name }}</p>
                                    </div>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                        {{ ucfirst($asset->type) }}
                                    </span>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-2xl font-bold text-gray-900">{{ $asset->formatted_price }}</p>
                                </div>

                                @php
                                    $userHolding = $userHoldings->where('asset_id', $asset->id)->first();
                                @endphp

                                @if($userHolding && $userHolding->quantity > 0)
                                    <div class="mb-4 p-3 bg-gray-50 rounded">
                                        <p class="text-sm text-gray-600">Your Holdings:</p>
                                        <p class="font-semibold">{{ $userHolding->formatted_quantity }} {{ $asset->symbol }}</p>
                                        <p class="text-sm text-gray-600">Value: {{ $userHolding->formatted_current_value }}</p>
                                    </div>
                                @endif

                                <div class="flex space-x-2">
                                    <button onclick="openBuyModal('{{ $asset->id }}', '{{ $asset->symbol }}', '{{ $asset->current_price }}')" 
                                            class="flex-1 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                        Buy
                                    </button>
                                    @if($userHolding && $userHolding->quantity > 0)
                                        <button onclick="openSellModal('{{ $asset->id }}', '{{ $asset->symbol }}', '{{ $asset->current_price }}', '{{ $userHolding->quantity }}')" 
                                                class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm">
                                            Sell
                                        </button>
                                    @else
                                        <button disabled class="flex-1 bg-gray-300 text-gray-500 font-bold py-2 px-4 rounded text-sm cursor-not-allowed">
                                            Sell
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Assets</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $userHoldings->count() }}</dd>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Portfolio Value</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ auth()->user()->formatted_portfolio_value }}</dd>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Active Assets</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $assets->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                            @foreach($apiKeys as $apiKey)
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
                            @foreach($apiKeys as $apiKey)
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
