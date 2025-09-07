<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $strategy->name }}
            </h2>
            <div class="flex space-x-2">
                @if(!$strategy->is_system && $strategy->created_by === Auth::id())
                    <a href="{{ route('strategies.edit', $strategy) }}" 
                       class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                        Edit Strategy
                    </a>
                @endif
                <a href="{{ route('strategies.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Strategies
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Strategy Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Strategy Information</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $strategy->description ?: 'No description provided' }}</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Type</label>
                                        <p class="mt-1 text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $strategy->type) }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Market Type</label>
                                        <p class="mt-1 text-sm text-gray-900 capitalize">{{ $strategy->market_type }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Status</label>
                                        <p class="mt-1">
                                            @if($strategy->is_active)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Inactive
                                                </span>
                                            @endif
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Category</label>
                                        <p class="mt-1">
                                            @if($strategy->is_system)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    System Strategy
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Custom Strategy
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                @if($strategy->supported_timeframes)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Supported Timeframes</label>
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            @foreach($strategy->supported_timeframes as $timeframe)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ $timeframe }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($strategy->required_indicators)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Required Indicators</label>
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            @foreach($strategy->required_indicators as $indicator)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ ucfirst(str_replace('_', ' ', $indicator)) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Strategy Parameters -->
                    @if($strategy->parameters->count() > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                            <div class="p-6 text-gray-900">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Strategy Parameters</h3>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parameter</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Range</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($strategy->parameters as $parameter)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900">{{ $parameter->parameter_name }}</div>
                                                            @if($parameter->description)
                                                                <div class="text-sm text-gray-500">{{ $parameter->description }}</div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ ucfirst($parameter->parameter_type) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        @if($parameter->default_value !== null)
                                                            @if(is_array($parameter->default_value))
                                                                {{ json_encode($parameter->default_value) }}
                                                            @else
                                                                {{ $parameter->default_value }}
                                                            @endif
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        @if($parameter->min_value !== null || $parameter->max_value !== null)
                                                            {{ $parameter->min_value ?? '∞' }} - {{ $parameter->max_value ?? '∞' }}
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @if($parameter->is_required)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                Required
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                                Optional
                                                            </span>
                                                        @endif
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

                <!-- Sidebar -->
                <div class="space-y-6">
                    
                    <!-- Attach to Bot -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Attach to Bot</h3>
                            
                            <div class="space-y-3">
                                <a href="{{ route('strategies.select-for-bot', ['bot_type' => 'futures']) }}" 
                                   class="block w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center">
                                    Attach to Futures Bot
                                </a>
                                
                                <a href="{{ route('strategies.select-for-bot', ['bot_type' => 'spot']) }}" 
                                   class="block w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-center">
                                    Attach to Spot Bot
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Bots Using This Strategy -->
                    @if($botStrategies->count() > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Bots Using This Strategy</h3>
                                
                                <div class="space-y-3">
                                    @foreach($botStrategies as $botStrategy)
                                        <div class="border border-gray-200 rounded-lg p-3">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $botStrategy->bot->name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ class_basename($botStrategy->bot_type) }} • Priority: {{ $botStrategy->priority }}
                                                    </div>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <a href="{{ route($botStrategy->bot_type === 'App\Models\FuturesTradingBot' ? 'futures-bots.show' : 'trading-bots.show', $botStrategy->bot) }}" 
                                                       class="text-blue-600 hover:text-blue-800 text-xs">
                                                        View
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Strategy Actions -->
                    @if(!$strategy->is_system && $strategy->created_by === Auth::id())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                                
                                <div class="space-y-3">
                                    <a href="{{ route('strategies.edit', $strategy) }}" 
                                       class="block w-full bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-center">
                                        Edit Strategy
                                    </a>
                                    
                                    <form method="POST" action="{{ route('strategies.destroy', $strategy) }}" 
                                          onsubmit="return confirm('Are you sure you want to delete this strategy? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="block w-full bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                            Delete Strategy
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
