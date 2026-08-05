@php
    use App\Models\FormField;

    $slots = $field->dateSlots();
    $isMulti = $field->dateSlotsMultiSelect();

    // Legacy entries stored a flat list of chosen ids; new ones store states.
    $initial = [];
    if (is_array($value)) {
        if (array_is_list($value)) {
            foreach ($value as $id) {
                $initial[(string) $id] = 'yes';
            }
        } else {
            $initial = $value;
        }
    } elseif ($value) {
        $initial[(string) $value] = 'yes';
    }

    $fieldId = $field->id;

    $slotLabels = [
        'yes' => $t['slot_available'],
        'maybe' => $t['slot_maybe'],
        'no' => $t['slot_unavailable'],
    ];

    $groupedSlots = collect($slots)->groupBy('date');

    // Distinct time ranges across all days become the grid's rows; days become
    // its columns. A day that skips a row simply leaves that cell blank.
    $dates = $groupedSlots->keys()->sort()->values();
    $timeRows = collect($slots)
        ->map(fn ($s) => [
            'key' => ($s['start_time'] ?? '') . '-' . ($s['end_time'] ?? ''),
            'start_time' => $s['start_time'] ?? '',
            'end_time' => $s['end_time'] ?? '',
        ])
        ->unique('key')
        ->sortBy(fn ($r) => $r['start_time'] . $r['end_time'])
        ->values();

    $cells = [];
    foreach ($slots as $slot) {
        $key = ($slot['start_time'] ?? '') . '-' . ($slot['end_time'] ?? '');
        $cells[$key][$slot['date'] ?? ''] = FormField::dateSlotId($slot);
    }

    // A stacked list stays friendlier for a handful of slots; the grid earns its
    // density only once there are several days or several times per day.
    $useGrid = $dates->count() > 2 || $timeRows->count() > 3;

    $dateHeaders = $dates->map(fn ($d) => [
        'kind' => 'date',
        'iso' => $d,
        'top' => \Carbon\Carbon::parse($d)->format('D'),
        'bottom' => \Carbon\Carbon::parse($d)->format('j M'),
        'aria' => \Carbon\Carbon::parse($d)->format('D j M'),
    ]);

    $timeHeaders = $timeRows->map(fn ($r) => [
        'kind' => 'time',
        'key' => $r['key'],
        'start_time' => $r['start_time'],
        'end_time' => $r['end_time'],
        'aria' => $r['start_time'],
    ]);

    // Days go across only while they are the smaller axis. A two-week poll with
    // four times a day would otherwise need 14 columns and horizontal scrolling;
    // flipped, it is 4 columns and scrolls vertically like the rest of the page.
    $transpose = $dates->count() > $timeRows->count();
    $rowHeaders = $transpose ? $dateHeaders : $timeHeaders;
    $colHeaders = $transpose ? $timeHeaders : $dateHeaders;

    $grid = [];
    foreach ($rowHeaders as $ri => $row) {
        foreach ($colHeaders as $ci => $col) {
            $timeKey = $transpose ? $col['key'] : $row['key'];
            $dateIso = $transpose ? $row['iso'] : $col['iso'];

            $grid[$ri][$ci] = [
                'id' => $cells[$timeKey][$dateIso] ?? null,
                'aria' => $transpose
                    ? $row['aria'] . ' ' . $col['aria']
                    : $col['aria'] . ' ' . $row['aria'],
            ];
        }
    }
@endphp

{{-- (object) matters: an empty PHP array serialises to [], and Alpine cannot
     track string keys added to an Array. --}}
<div x-data="dateSlotPicker(@js((object) $initial), @js($isMulti), @js($slotLabels))"
     @pointerup.window="endPaint()">
    {{-- min-w-0 undoes the browser's default `min-width: min-content` on
         fieldset, which otherwise stops the grid's scroll container from
         shrinking — the overflowing columns then get clipped by the card
         instead of becoming scrollable. --}}
    <fieldset class="min-w-0">
        <legend class="block text-sm font-medium text-white mb-1">
            {{ $field->label }}
            @if($field->required) <span class="text-red-400">*</span> @endif
        </legend>
        @if($field->description)
            <p class="text-xs text-white/40 mb-2">{{ $field->description }}</p>
        @endif
        @if($isMulti)
            <p class="text-xs text-white/30 mb-3">
                {{ $useGrid ? $t['slot_drag_hint'] : $t['slot_cycle_hint'] }}
            </p>
        @else
            <p class="text-xs text-white/30 mb-3">{{ $t['select_one'] }}</p>
        @endif

        @if($useGrid)
            @include('public.fields.partials.slot-grid')
        @else
            @include('public.fields.partials.slot-list')
        @endif

        @if($isMulti)
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-[11px] text-white/40">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm bg-brand-500"></span>{{ $t['slot_available'] }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm bg-amber-500/70"></span>{{ $t['slot_maybe'] }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm bg-white/10 border border-white/15"></span>{{ $t['slot_unavailable'] }}
                </span>
            </div>
        @endif

        {{-- One input per slot, so "no" is recorded rather than merely absent.
             x-for needs Object.entries here — it does not iterate plain objects. --}}
        <template x-for="[id, state] in Object.entries(states)" :key="id">
            <input type="hidden" :name="`field_{{ $fieldId }}[${id}]`" :value="state">
        </template>
    </fieldset>
</div>

<script>
function dateSlotPicker(initial, isMulti, labels) {
    return {
        states: initial || {},
        isMulti: isMulti,
        labels: labels,
        painting: null,

        state(id) {
            return this.states[id] || 'no';
        },

        labelFor(state) {
            return this.labels[state] ?? state;
        },

        nextState(state) {
            return { no: 'yes', yes: 'maybe', maybe: 'no' }[state];
        },

        set(id, state) {
            if (!this.isMulti) {
                Object.keys(this.states).forEach(k => this.states[k] = 'no');
            }
            this.states[id] = state;
        },

        cycle(id) {
            if (!this.isMulti) {
                const wasChosen = this.state(id) === 'yes';
                Object.keys(this.states).forEach(k => this.states[k] = 'no');
                this.states[id] = wasChosen ? 'no' : 'yes';
                return;
            }
            this.states[id] = this.nextState(this.state(id));
        },

        // Drag-paint: the first cell decides the state, the rest of the drag
        // copies it. Mouse only — touch drags scroll the page instead.
        startPaint(id) {
            this.cycle(id);
            this.painting = this.isMulti ? this.state(id) : null;
        },

        paintOver(id) {
            if (this.painting === null) return;
            this.set(id, this.painting);
        },

        endPaint() {
            this.painting = null;
        },
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
