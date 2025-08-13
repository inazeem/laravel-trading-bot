@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Futures Trading Bots</h1>
        <a href="{{ route('futures-bots.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
            <i class="fas fa-plus mr-2"></i>Create New Bot
        </a>
    </div>

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

    @if($bots->isEmpty())
        <div class="text-center py-12">
            <div class="text-gray-500 text-lg mb-4">
                <i class="fas fa-robot text-6xl mb-4"></i>
                <p>No futures trading bots created yet.</p>
            </div>
            <a href="{{ route('futures-bots.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                Create Your First Futures Bot
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($bots as $bot)
                <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">{{ $bot->name }}</h3>
                                <p class="text-gray-600">{{ $bot->symbol }} on {{ ucfirst($bot->exchange) }}</p>
                            </div>
                            <div class="flex items-center">
                                @if($bot->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Leverage</p>
                                <p class="font-semibold">{{ $bot->leverage }}x</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Risk</p>
                                <p class="font-semibold">{{ $bot->risk_percentage }}%</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Margin</p>
                                <p class="font-semibold capitalize">{{ $bot->margin_type }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Side</p>
                                <p class="font-semibold capitalize">{{ $bot->position_side }}</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4 mb-4">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <p class="text-2xl font-bold {{ $bot->total_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ${{ number_format($bot->total_pnl, 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500">Total PnL</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold {{ $bot->unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ${{ number_format($bot->unrealized_pnl, 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500">Unrealized</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ number_format($bot->win_rate, 1) }}%
                                    </p>
                                    <p class="text-xs text-gray-500">Win Rate</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach($bot->timeframes as $timeframe)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $timeframe }}
                                </span>
                            @endforeach
                        </div>

                        <div class="flex space-x-2">
                            <a href="{{ route('futures-bots.show', $bot) }}" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded text-center transition duration-200">
                                <i class="fas fa-eye mr-1"></i> View
                            </a>
                            
                            @if($bot->is_active)
                                <form method="POST" action="{{ route('futures-bots.run', $bot) }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition duration-200">
                                        <i class="fas fa-play mr-1"></i> Run
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="flex space-x-2 mt-2">
                            <form method="POST" action="{{ route('futures-bots.toggle', $bot) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full {{ $bot->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white font-medium py-2 px-4 rounded transition duration-200">
                                    <i class="fas {{ $bot->is_active ? 'fa-pause' : 'fa-play' }} mr-1"></i>
                                    {{ $bot->is_active ? 'Pause' : 'Activate' }}
                                </button>
                            </form>
                            
                            <a href="{{ route('futures-bots.edit', $bot) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-center transition duration-200">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                        </div>

                        @if($bot->openTrades()->count() > 0)
                            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                    <span class="text-sm text-yellow-800">
                                        {{ $bot->openTrades()->count() }} open position(s)
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
