<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit API Key') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('api-keys.update', $apiKey) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                                
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">API Key Name</label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $apiKey->name) }}" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="exchange" class="block text-sm font-medium text-gray-700">Exchange</label>
                                    <select name="exchange" id="exchange" required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select Exchange</option>
                                        <option value="kucoin" {{ old('exchange', $apiKey->exchange) === 'kucoin' ? 'selected' : '' }}>KuCoin</option>
                                        <option value="binance" {{ old('exchange', $apiKey->exchange) === 'binance' ? 'selected' : '' }}>Binance</option>
                                    </select>
                                    @error('exchange')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- API Credentials -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">API Credentials</h3>
                                
                                <div>
                                    <label for="api_key" class="block text-sm font-medium text-gray-700">API Key</label>
                                    <input type="text" name="api_key" id="api_key" value="{{ old('api_key') }}" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-sm text-gray-500">Enter your API key from the exchange</p>
                                    @error('api_key')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="api_secret" class="block text-sm font-medium text-gray-700">API Secret</label>
                                    <input type="password" name="api_secret" id="api_secret" value="{{ old('api_secret') }}" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-sm text-gray-500">Enter your API secret from the exchange</p>
                                    @error('api_secret')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div id="passphrase_field" style="display: {{ old('exchange', $apiKey->exchange) === 'kucoin' ? 'block' : 'none' }};">
                                    <label for="passphrase" class="block text-sm font-medium text-gray-700">Passphrase (KuCoin Only)</label>
                                    <input type="password" name="passphrase" id="passphrase" value="{{ old('passphrase') }}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-sm text-gray-500">Required for KuCoin API keys</p>
                                    @error('passphrase')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Permissions -->
                        <div class="mt-8 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Permissions</h3>
                            <p class="text-sm text-gray-600">Select the permissions you want to grant to this API key:</p>
                            
                            <div class="space-y-3">
                                @php
                                    $selectedPermissions = old('permissions', $apiKey->permissions ?? ['read']);
                                @endphp
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[]" value="read" {{ in_array('read', $selectedPermissions) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Read (View account information, balances, orders)</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[]" value="trade" {{ in_array('trade', $selectedPermissions) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Trade (Place and cancel orders) <span class="text-red-600">*Required for trading bots</span></span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[]" value="transfer" {{ in_array('transfer', $selectedPermissions) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Transfer (Withdraw funds) <span class="text-orange-600">*Use with caution</span></span>
                                </label>
                            </div>
                            @error('permissions')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Additional Settings -->
                        <div class="mt-8 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Additional Settings</h3>
                            
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <textarea name="notes" id="notes" rows="3" placeholder="Add any notes about this API key..."
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('notes', $apiKey->notes) }}</textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $apiKey->is_active) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <label for="is_active" class="ml-2 text-sm text-gray-700">Activate this API key immediately</label>
                            </div>
                        </div>

                        <!-- Security Notice -->
                        <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Security Notice</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li>Your API credentials are encrypted and stored securely</li>
                                            <li>Only grant the minimum permissions necessary</li>
                                            <li>Never share your API credentials with anyone</li>
                                            <li>Consider using IP restrictions on your exchange API keys</li>
                                            <li>Regularly rotate your API keys for security</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-4">
                            <a href="{{ route('api-keys.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Update API Key
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('exchange').addEventListener('change', function() {
            const passphraseField = document.getElementById('passphrase_field');
            if (this.value === 'kucoin') {
                passphraseField.style.display = 'block';
            } else {
                passphraseField.style.display = 'none';
            }
        });

        // Trigger on page load if exchange is already selected
        if (document.getElementById('exchange').value === 'kucoin') {
            document.getElementById('passphrase_field').style.display = 'block';
        }
    </script>
</x-app-layout>
