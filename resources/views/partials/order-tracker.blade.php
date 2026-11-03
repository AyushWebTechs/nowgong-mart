{{-- Vertical order timeline: every step with the time it was reached. Expects $order with statusHistory loaded. --}}
@php
    $steps = \App\Enums\OrderStatus::progression();
    $currentIndex = array_search($order->status, $steps, true);
    $times = $order->statusTimes();
@endphp

<ol class="relative space-y-0" aria-label="Order progress">
    @foreach ($steps as $index => $step)
        @php
            $done = $currentIndex !== false && $index < $currentIndex;
            $current = $index === $currentIndex;
            $reached = $done || $current;
        @endphp
        <li class="relative flex gap-3 pb-5 last:pb-0">
            @unless ($loop->last)
                <span class="absolute top-7 left-[13px] h-[calc(100%-1.75rem)] w-0.5 {{ $done ? 'bg-brand-500' : 'bg-gray-200' }}" aria-hidden="true"></span>
            @endunless
            <span class="relative grid size-7 shrink-0 place-items-center rounded-full text-xs font-bold {{ $reached ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-400 ring-1 ring-gray-200' }}">
                @if ($current && $order->status->isOpen())
                    <span class="absolute inset-0 animate-ping rounded-full bg-brand-500/40" aria-hidden="true"></span>
                @endif
                @if ($done || ($current && ! $order->status->isOpen()))
                    <x-icon name="check" class="size-4" stroke="3" />
                @else
                    {{ $index + 1 }}
                @endif
            </span>
            <div class="min-w-0 pt-0.5">
                <p class="text-sm {{ $reached ? 'font-bold text-gray-900' : 'text-gray-400' }}">{{ $step->label() }}</p>
                @if (isset($times[$step->value]))
                    <p class="text-xs text-gray-500">{{ $times[$step->value]->format('d M, h:i A') }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
