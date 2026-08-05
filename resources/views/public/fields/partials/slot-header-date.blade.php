{{-- Rendered in both axes, so it must stay compact enough for a column head. --}}
<span class="block leading-tight" x-data>
    <span class="block text-[10px] uppercase tracking-wider text-white/40"
          x-text="new Date('{{ $h['iso'] }}T00:00:00').toLocaleDateString(undefined, {weekday: 'short'})">{{ $h['top'] }}</span>
    <span class="block text-xs font-medium text-white/80"
          x-text="new Date('{{ $h['iso'] }}T00:00:00').toLocaleDateString(undefined, {day: 'numeric', month: 'short'})">{{ $h['bottom'] }}</span>
</span>
