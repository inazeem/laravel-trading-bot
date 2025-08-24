@props(['trade'])

@if($trade && $trade->status === 'open')
    @php
        $maxDuration = config('micro_trading.signal_settings.max_trade_duration_hours', 2);
        $tradeOpenedAt = $trade->opened_at;
        $now = now();
        
        // Calculate when the trade should close
        $tradeShouldCloseAt = $tradeOpenedAt->copy()->addHours($maxDuration);
        $timeLeft = max(0, $tradeShouldCloseAt->diffInMinutes($now, false));
        
        $hours = floor($timeLeft / 60);
        $minutes = $timeLeft % 60;
        
        // Color coding based on time remaining
        if ($timeLeft <= 30) { // Last 30 minutes
            $colorClass = 'text-red-600 bg-red-100 border-red-300';
            $urgencyClass = 'animate-pulse';
        } elseif ($timeLeft <= 60) { // Last hour
            $colorClass = 'text-orange-600 bg-orange-100 border-orange-300';
            $urgencyClass = '';
        } else {
            $colorClass = 'text-green-600 bg-green-100 border-green-300';
            $urgencyClass = '';
        }
    @endphp

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Current Trade Timer</h3>
                                            <p class="text-sm text-gray-600">
                            Trade opened: {{ $trade->opened_at->format('M d, Y H:i:s') }}
                        </p>
                        <p class="text-sm text-gray-600">
                            Closes at: {{ $tradeShouldCloseAt->format('M d, Y H:i:s') }}
                        </p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-4 py-2 rounded-lg border-2 {{ $colorClass }} {{ $urgencyClass }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-lg font-bold" 
                              data-countdown
                              data-trade-opened-at="{{ $trade->opened_at->toISOString() }}"
                              data-max-duration-hours="{{ $maxDuration }}">
                            @if($timeLeft > 0)
                                {{ sprintf('%02d:%02d', $hours, $minutes) }}
                            @else
                                00:00
                            @endif
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($timeLeft > 0)
                            Time remaining
                        @else
                            Trade will close soon
                        @endif
                    </p>
                </div>
            </div>
            
                                    <!-- Progress bar -->
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Trade Progress</span>
                                <span>{{ number_format((($maxDuration * 60 - $timeLeft) / ($maxDuration * 60)) * 100, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                @php
                                    $progressPercentage = min(100, (($maxDuration * 60 - $timeLeft) / ($maxDuration * 60)) * 100);
                                    $progressColor = $timeLeft <= 30 ? 'bg-red-500' : ($timeLeft <= 60 ? 'bg-orange-500' : 'bg-green-500');
                                @endphp
                                <div class="h-2 rounded-full {{ $progressColor }} transition-all duration-300" 
                                     style="width: {{ $progressPercentage }}%"></div>
                            </div>
                        </div>
            
            <!-- Trade details -->
            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Side:</span>
                    <span class="font-medium {{ $trade->side === 'long' ? 'text-green-600' : 'text-red-600' }}">
                        {{ ucfirst($trade->side) }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500">Quantity:</span>
                    <span class="font-medium">{{ $trade->quantity }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Entry Price:</span>
                    <span class="font-medium">${{ number_format($trade->entry_price, 4) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Unrealized PnL:</span>
                    <span class="font-medium {{ $trade->unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ${{ number_format($trade->unrealized_pnl, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endif
