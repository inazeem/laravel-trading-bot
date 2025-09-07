<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Trading Strategy') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('strategies.store') }}">
                        @csrf

                        <!-- Strategy Basic Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="name" :value="__('Strategy Name')" />
                                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="type" :value="__('Strategy Type')" />
                                    <select id="type" name="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="">Select Strategy Type</option>
                                        @foreach($typeOptions as $value => $label)
                                            <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="market_type" :value="__('Market Type')" />
                                    <select id="market_type" name="market_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        @foreach($marketTypeOptions as $value => $label)
                                            <option value="{{ $value }}" {{ old('market_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('market_type')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Supported Timeframes -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Supported Timeframes</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                @foreach($timeframeOptions as $timeframe)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="supported_timeframes[]" value="{{ $timeframe }}" 
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                               {{ in_array($timeframe, old('supported_timeframes', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ $timeframe }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('supported_timeframes')" class="mt-2" />
                        </div>

                        <!-- Required Indicators -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Required Indicators</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                @foreach($indicatorOptions as $indicator)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="required_indicators[]" value="{{ $indicator }}" 
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                               {{ in_array($indicator, old('required_indicators', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $indicator)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('required_indicators')" class="mt-2" />
                        </div>

                        <!-- Strategy Parameters -->
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Strategy Parameters</h3>
                                <button type="button" id="add-parameter" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Add Parameter
                                </button>
                            </div>

                            <div id="parameters-container">
                                <!-- Parameters will be added here dynamically -->
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('strategies.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Create Strategy') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Parameter Template (Hidden) -->
    <template id="parameter-template">
        <div class="parameter-item border border-gray-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-md font-medium text-gray-900">Parameter</h4>
                <button type="button" class="remove-parameter text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label :value="__('Parameter Name')" />
                    <x-text-input type="text" name="parameters[INDEX][parameter_name]" class="block mt-1 w-full" required />
                </div>

                <div>
                    <x-input-label :value="__('Parameter Type')" />
                    <select name="parameters[INDEX][parameter_type]" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        @foreach($parameterTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <x-input-label :value="__('Description')" />
                <textarea name="parameters[INDEX][description]" rows="2" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                    <x-input-label :value="__('Default Value')" />
                    <x-text-input type="text" name="parameters[INDEX][default_value]" class="block mt-1 w-full" />
                </div>

                <div>
                    <x-input-label :value="__('Min Value')" />
                    <x-text-input type="number" step="any" name="parameters[INDEX][min_value]" class="block mt-1 w-full" />
                </div>

                <div>
                    <x-input-label :value="__('Max Value')" />
                    <x-text-input type="number" step="any" name="parameters[INDEX][max_value]" class="block mt-1 w-full" />
                </div>
            </div>

            <div class="mt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="parameters[INDEX][is_required]" value="1" 
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Required Parameter</span>
                </label>
            </div>
        </div>
    </template>

    <script>
        let parameterIndex = 0;

        document.getElementById('add-parameter').addEventListener('click', function() {
            const template = document.getElementById('parameter-template');
            const container = document.getElementById('parameters-container');
            const clone = template.content.cloneNode(true);
            
            // Replace INDEX with actual index
            const html = clone.querySelector('.parameter-item').outerHTML.replace(/INDEX/g, parameterIndex);
            container.insertAdjacentHTML('beforeend', html);
            
            parameterIndex++;
        });

        // Remove parameter functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-parameter') || e.target.closest('.remove-parameter')) {
                e.target.closest('.parameter-item').remove();
            }
        });
    </script>
</x-app-layout>
