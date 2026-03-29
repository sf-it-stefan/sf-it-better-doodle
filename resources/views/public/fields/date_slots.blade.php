@php
    $slots = $field->options ?? [];
    $isMulti = !empty($slots[0]['multi_select']);
    $selectedValues = is_array($value) ? $value : ($value ? [$value] : []);

    // Group slots by date
    $groupedSlots = collect($slots)->groupBy('date');
@endphp

<div x-data="dateSlotPicker(@js($selectedValues), @js($isMulti))">
    <fieldset>
        <legend class="block text-sm font-medium text-white mb-1">
            {{ $field->label }}
            @if($field->required) <span class="text-red-400">*</span> @endif
        </legend>
        @if($field->description)
            <p class="text-xs text-white/40 mb-2">{{ $field->description }}</p>
        @endif
        @if($isMulti)
            <p class="text-xs text-white/30 mb-3">{{ $t['select_all_that_apply'] }}</p>
        @else
            <p class="text-xs text-white/30 mb-3">{{ $t['select_one'] }}</p>
        @endif

        <div class="space-y-4">
            @foreach($groupedSlots as $date => $dateSlots)
                <div>
                    <p class="text-xs text-white/50 font-medium uppercase tracking-wider mb-2"
                       x-data x-text="new Date('{{ $date }}T00:00:00').toLocaleDateString(undefined, {weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'})">
                        {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                    </p>
                    <div class="space-y-2">
                        @foreach($dateSlots as $slot)
                            @php
                                $slotId = $date . '_' . ($slot['start_time'] ?? 'allday') . '_' . ($slot['end_time'] ?? '');
                            @endphp
                            <button type="button"
                                @click="toggle('{{ $slotId }}')"
                                :class="selected.includes('{{ $slotId }}')
                                    ? 'bg-brand-500/20 border-brand-500 text-brand-300'
                                    : 'bg-white/5 border-white/10 text-white/60 hover:border-white/30'"
                                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border transition-all text-left"
                                role="checkbox"
                                :aria-checked="selected.includes('{{ $slotId }}')">
                                <span class="w-5 h-5 rounded border-2 flex items-center justify-center shrink-0 transition-all"
                                    :class="selected.includes('{{ $slotId }}') ? 'border-brand-400 bg-brand-400' : 'border-white/30'">
                                    <svg x-show="selected.includes('{{ $slotId }}')" class="w-3 h-3 text-black" fill="currentColor" viewBox="0 0 12 12">
                                        <path d="M10 3L5 8.5 2 5.5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-medium">
                                    @if(!empty($slot['start_time']) && !empty($slot['end_time']))
                                        <span x-data x-text="formatTime('{{ $slot['start_time'] }}') + ' \u2013 ' + formatTime('{{ $slot['end_time'] }}')">
                                            {{ $slot['start_time'] }} &ndash; {{ $slot['end_time'] }}
                                        </span>
                                    @elseif(!empty($slot['start_time']))
                                        <span x-data x-text="formatTime('{{ $slot['start_time'] }}')">{{ $slot['start_time'] }}</span>
                                    @else
                                        All day
                                    @endif
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Hidden input for form submission --}}
        <template x-for="s in selected" :key="s">
            <input type="hidden" name="field_{{ $field->id }}[]" :value="s">
        </template>
    </fieldset>
</div>

<script>
function dateSlotPicker(initial, isMulti) {
    return {
        selected: initial || [],
        isMulti: isMulti,
        toggle(id) {
            if (this.isMulti) {
                const idx = this.selected.indexOf(id);
                if (idx > -1) {
                    this.selected.splice(idx, 1);
                } else {
                    this.selected.push(id);
                }
            } else {
                this.selected = this.selected.includes(id) ? [] : [id];
            }
        }
    };
}

function formatTime(time) {
    if (!time) return '';
    const [h, m] = time.split(':');
    const d = new Date();
    d.setHours(parseInt(h), parseInt(m));
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}
</script>
