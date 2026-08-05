{{-- Availability grid. Orientation is chosen by the caller: whichever of
     days/times is smaller becomes the columns, so the grid stays inside the
     card instead of scrolling sideways. --}}
@php
    $header = function (array $h, bool $stacked) {
        if ($h['kind'] === 'date') {
            return view('public.fields.partials.slot-header-date', ['h' => $h])->render();
        }
        return view('public.fields.partials.slot-header-time', ['h' => $h, 'stacked' => $stacked])->render();
    };
@endphp

{{-- Cells keep a 44px minimum rather than shrinking, so wide polls scroll
     sideways. The edge fades exist so that scrollability is visible — without
     them a cut-off column just looks like a broken layout. --}}
<div class="relative"
     x-data="{ atEnd: true,
               sync() { const e = $refs.scroller;
                        this.atEnd = e.scrollLeft + e.clientWidth >= e.scrollWidth - 1; } }"
     x-init="sync(); $nextTick(() => sync())"
     @resize.window="sync()">

    <div class="pointer-events-none absolute inset-y-0 right-0 w-6 z-10 bg-gradient-to-l from-surface to-transparent transition-opacity"
         x-cloak :class="atEnd ? 'opacity-0' : 'opacity-100'"></div>

<div class="overflow-x-auto -mx-1 px-1 pb-1" x-ref="scroller" @scroll="sync()">
    <div class="slot-grid" style="--slot-cols: {{ $colHeaders->count() }};">

        <div class="slot-rowhead"></div>
        @foreach($colHeaders as $col)
            <div class="px-0.5 pb-1 text-center min-w-0">{!! $header($col, true) !!}</div>
        @endforeach

        @foreach($rowHeaders as $ri => $row)
            <div class="slot-rowhead flex items-center justify-end pr-2 text-right text-[11px] text-white/50 whitespace-nowrap">
                {!! $header($row, true) !!}
            </div>

            @foreach($colHeaders as $ci => $col)
                @php $cell = $grid[$ri][$ci]; @endphp

                @if($cell['id'] === null)
                    {{-- this day does not offer this time --}}
                    <div class="h-11 rounded-lg border border-dashed border-white/5" aria-hidden="true"></div>
                @else
                    <button type="button"
                        @pointerdown.prevent="startPaint('{{ $cell['id'] }}'); $el.focus()"
                        @pointerenter="paintOver('{{ $cell['id'] }}')"
                        @keydown.enter.prevent="cycle('{{ $cell['id'] }}')"
                        @keydown.space.prevent="cycle('{{ $cell['id'] }}')"
                        :class="{
                            'bg-brand-500/25 border-brand-500 text-brand-100': state('{{ $cell['id'] }}') === 'yes',
                            'bg-amber-500/20 border-amber-500/70 text-amber-100': state('{{ $cell['id'] }}') === 'maybe',
                            'bg-white/5 border-white/10 text-white/35 hover:border-white/30': state('{{ $cell['id'] }}') === 'no'
                        }"
                        class="h-11 min-w-0 rounded-lg border transition-colors flex items-center justify-center touch-manipulation select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                        :aria-pressed="state('{{ $cell['id'] }}') !== 'no'"
                        :aria-label="'{{ $cell['aria'] }} ' + labelFor(state('{{ $cell['id'] }}'))">

                        <svg x-show="state('{{ $cell['id'] }}') === 'yes'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-show="state('{{ $cell['id'] }}') === 'maybe'" class="text-[10px] font-semibold uppercase tracking-wider">?</span>
                        <span x-show="state('{{ $cell['id'] }}') === 'no'" class="w-1.5 h-1.5 rounded-full bg-white/20"></span>
                    </button>
                @endif
            @endforeach
        @endforeach
    </div>
</div>
</div>
