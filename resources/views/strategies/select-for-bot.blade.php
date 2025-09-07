<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Select Strategy for {{ $bot->name }}
            </h2>
            <a href="{{ route($botType === 'futures' ? 'futures-bots.show' : 'trading-bots.show', $bot) }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Bot
            </a>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Bot Information -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Bot Information</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $bot->name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type</label>
                                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ $botType }} Bot</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Symbol</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $bot->symbol }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Exchange</label>
                                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ $bot->exchange }}</p>
                                </div>

                                @if($bot->timeframes)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Timeframes</label>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach($bot->timeframes as $timeframe)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ $timeframe }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Current Strategies -->
                    @if($currentStrategies->count() > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                            <div class="p-6 text-gray-900">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Current Strategies</h3>
                                
                                <div class="space-y-3">
                                    @foreach($currentStrategies as $strategy)
                                        <div class="border border-gray-200 rounded-lg p-3">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $strategy->name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        Priority: {{ $strategy->pivot->priority }}
                                                    </div>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <button onclick="detachStrategy({{ $strategy->id }})" 
                                                            class="text-red-600 hover:text-red-800 text-xs">
                                                        Detach
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Available Strategies -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Available Strategies</h3>
                            
                            @if($strategies->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @foreach($strategies as $strategy)
                                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-md font-semibold text-gray-900">{{ $strategy->name }}</h4>
                                                <div class="flex items-center space-x-2">
                                                    @if($strategy->is_system)
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            System
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            Custom
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <p class="text-gray-600 text-sm mb-3">{{ $strategy->description }}</p>

                                            <div class="space-y-2 mb-4">
                                                <div class="flex items-center text-sm text-gray-500">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                    </svg>
                                                    <span class="font-medium">Type:</span>
                                                    <span class="ml-1 capitalize">{{ str_replace('_', ' ', $strategy->type) }}</span>
                                                </div>

                                                @if($strategy->supported_timeframes)
                                                    <div class="flex items-center text-sm text-gray-500">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <span class="font-medium">Timeframes:</span>
                                                        <span class="ml-1">{{ implode(', ', $strategy->supported_timeframes) }}</span>
                                                    </div>
                                                @endif

                                                <div class="flex items-center text-sm text-gray-500">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                    <span class="font-medium">Parameters:</span>
                                                    <span class="ml-1">{{ $strategy->parameters->count() }}</span>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between">
                                                <a href="{{ route('strategies.show', $strategy) }}" 
                                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                    View Details
                                                </a>

                                                <button onclick="attachStrategy({{ $strategy->id }}, '{{ $strategy->name }}')" 
                                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm">
                                                    Attach
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No strategies available</h3>
                                    <p class="mt-1 text-sm text-gray-500">No strategies are available for {{ $botType }} bots.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attach Strategy Modal -->
    <div id="attach-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Attach Strategy</h3>
                <p class="text-sm text-gray-500 mb-4">Configure the strategy parameters for this bot.</p>
                
                <form id="attach-form" method="POST" action="{{ route('strategies.attach-to-bot') }}">
                    @csrf
                    <input type="hidden" name="bot_id" value="{{ $bot->id }}">
                    <input type="hidden" name="bot_type" value="{{ $botType }}">
                    <input type="hidden" name="strategy_id" id="strategy_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select name="priority" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $i === 1 ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div id="parameters-section" class="mb-4">
                        <!-- Parameters will be loaded here -->
                    </div>

                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" onclick="closeAttachModal()" 
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Attach Strategy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detach Strategy Form -->
    <form id="detach-form" method="POST" action="{{ route('strategies.detach-from-bot') }}" style="display: none;">
        @csrf
        <input type="hidden" name="bot_id" value="{{ $bot->id }}">
        <input type="hidden" name="bot_type" value="{{ $botType }}">
        <input type="hidden" name="strategy_id" id="detach_strategy_id">
    </form>

    <script>
        function attachStrategy(strategyId, strategyName) {
            document.getElementById('strategy_id').value = strategyId;
            document.getElementById('attach-modal').classList.remove('hidden');
            
            // Load strategy parameters (simplified for now)
            document.getElementById('parameters-section').innerHTML = `
                <div class="text-sm text-gray-600">
                    <p>Strategy: <strong>${strategyName}</strong></p>
                    <p class="mt-2">You can configure custom parameters after attaching the strategy.</p>
                </div>
            `;
        }

        function closeAttachModal() {
            document.getElementById('attach-modal').classList.add('hidden');
        }

        function detachStrategy(strategyId) {
            if (confirm('Are you sure you want to detach this strategy from the bot?')) {
                document.getElementById('detach_strategy_id').value = strategyId;
                document.getElementById('detach-form').submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('attach-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAttachModal();
            }
        });
    </script>
</x-app-layout>
