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

    <div class="border-t border-gray-200 pt-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Trade Timer</span>
            <span class="text-xs text-gray-500">{{ $trade->symbol }}</span>
        </div>
        
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm text-gray-600">Time Left:</span>
            </div>
            <div class="inline-flex items-center px-2 py-1 rounded border {{ $colorClass }} {{ $urgencyClass }}">
                <span class="text-sm font-bold" 
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
        </div>
        
        <!-- Mini progress bar -->
        <div class="mt-2">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Progress</span>
                <span>{{ number_format((($maxDuration * 60 - $timeLeft) / ($maxDuration * 60)) * 100, 0) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1">
                @php
                    $progressPercentage = min(100, (($maxDuration * 60 - $timeLeft) / ($maxDuration * 60)) * 100);
                    $progressColor = $timeLeft <= 30 ? 'bg-red-500' : ($timeLeft <= 60 ? 'bg-orange-500' : 'bg-green-500');
                @endphp
                <div class="h-1 rounded-full {{ $progressColor }} transition-all duration-300" 
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>
        
        <!-- Trade details -->
        <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
            <div class="flex justify-between">
                <span class="text-gray-500">Side:</span>
                <span class="font-medium {{ $trade->side === 'long' ? 'text-green-600' : 'text-red-600' }}">
                    {{ ucfirst($trade->side) }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Entry:</span>
                <span class="font-medium">${{ number_format($trade->entry_price, 3) }}</span>
            </div>
        </div>
    </div>
@endif
