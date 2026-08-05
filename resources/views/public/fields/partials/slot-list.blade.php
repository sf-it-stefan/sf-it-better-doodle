{{-- Stacked layout: one full-width tap target per slot, grouped by day. --}}
<div class="space-y-4">
    @foreach($groupedSlots as $date => $dateSlots)
        <div>
            <p class="text-xs text-white/50 font-medium uppercase tracking-wider mb-2"
               x-data x-text="new Date('{{ $date }}T00:00:00').toLocaleDateString(undefined, {weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'})">
                {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
            </p>
            <div class="space-y-2">
                @foreach($dateSlots as $slot)
                    @php $slotId = \App\Models\FormField::dateSlotId($slot); @endphp
                    <button type="button"
                        @click="cycle('{{ $slotId }}')"
                        :class="{
                            'bg-brand-500/20 border-brand-500 text-brand-200': state('{{ $slotId }}') === 'yes',
                            'bg-amber-500/15 border-amber-500/70 text-amber-200': state('{{ $slotId }}') === 'maybe',
                            'bg-white/5 border-white/10 text-white/60 hover:border-white/30': state('{{ $slotId }}') === 'no'
                        }"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border transition-all text-left"
                        :aria-label="'{{ $slot['start_time'] ?? '' }} ' + labelFor(state('{{ $slotId }}'))">

                        <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-all"
                              :class="{
                                  'border-brand-400 bg-brand-400': state('{{ $slotId }}') === 'yes',
                                  'border-amber-400': state('{{ $slotId }}') === 'maybe',
                                  'border-white/25': state('{{ $slotId }}') === 'no'
                              }">
                            <svg x-show="state('{{ $slotId }}') === 'yes'" class="w-3 h-3 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 12 12">
                                <path stroke-linecap="round" d="M10 3L5 8.5 2 5.5"/>
                            </svg>
                            <span x-show="state('{{ $slotId }}') === 'maybe'" class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        </span>

                        <span class="text-sm font-medium flex-1">
                            @if(!empty($slot['start_time']) && !empty($slot['end_time']))
                                <span x-data x-text="formatTime('{{ $slot['start_time'] }}') + ' – ' + formatTime('{{ $slot['end_time'] }}')">
                                    {{ $slot['start_time'] }} &ndash; {{ $slot['end_time'] }}
                                </span>
                            @elseif(!empty($slot['start_time']))
                                <span x-data x-text="formatTime('{{ $slot['start_time'] }}')">{{ $slot['start_time'] }}</span>
                            @else
                                All day
                            @endif
                        </span>

                        @if($isMulti)
                            <span class="text-[10px] uppercase tracking-wider shrink-0"
                                  :class="{
                                      'text-brand-300': state('{{ $slotId }}') === 'yes',
                                      'text-amber-300': state('{{ $slotId }}') === 'maybe',
                                      'text-white/25': state('{{ $slotId }}') === 'no'
                                  }"
                                  x-text="labelFor(state('{{ $slotId }}'))"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
