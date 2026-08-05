@php $stacked = $stacked ?? false; @endphp

@if(!$h['start_time'])
    <span class="text-xs text-white/70">All day</span>

@elseif(!$h['end_time'])
    <span x-data x-text="formatTime('{{ $h['start_time'] }}')" class="text-xs text-white/70">{{ $h['start_time'] }}</span>

@elseif($stacked)
    {{-- column head: two short lines beat one that forces the column wider --}}
    <span class="block leading-tight" x-data>
        <span class="block text-xs text-white/70"
              x-text="formatTime('{{ $h['start_time'] }}')">{{ $h['start_time'] }}</span>
        <span class="block text-[10px] text-white/35"
              x-text="formatTime('{{ $h['end_time'] }}')">{{ $h['end_time'] }}</span>
    </span>

@else
    <span x-data x-text="formatTime('{{ $h['start_time'] }}') + '–' + formatTime('{{ $h['end_time'] }}')">
        {{ $h['start_time'] }}&ndash;{{ $h['end_time'] }}
    </span>
@endif
