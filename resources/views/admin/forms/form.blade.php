@extends('layouts.admin')

@section('title', $form ? 'Edit Form' : 'Create Form')
@section('heading', $form ? 'Edit Form' : 'Create Form')

@section('content')
<form method="POST" action="{{ $form ? route('admin.forms.update', $form) : route('admin.forms.store') }}" enctype="multipart/form-data"
      x-data="formBuilder()" @submit.prevent="submitForm($el)">
    @csrf
    @if($form) @method('PUT') @endif
    <input type="hidden" name="timezone" :value="Intl.DateTimeFormat().resolvedOptions().timeZone">

    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Left: Field builder --}}
        <div class="flex-1 min-w-0">
            <div class="bg-surface border border-surface-lighter rounded-xl p-6 mb-6">
                <h2 class="text-lg font-semibold text-white mb-4">Fields</h2>

                <div x-ref="fieldList" class="space-y-3 mb-4">
                    <template x-for="(field, index) in fields" :key="field._key">
                        <div class="bg-surface-light border border-surface-lighter rounded-xl p-4" :data-index="index">
                            <div class="flex items-center gap-3">
                                <span class="cursor-grab text-white/30 hover:text-white/60 drag-handle" title="Drag to reorder">&#9776;</span>
                                <span class="text-xs text-brand-400 font-mono uppercase" x-text="field.type.replace('_', ' ')"></span>
                                <input type="text" x-model="field.label" :name="'fields['+index+'][label]'" placeholder="Field label"
                                    class="flex-1 rounded-lg border-0 py-1.5 px-3 bg-surface text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                <label class="flex items-center gap-1 text-xs text-white/50">
                                    <input type="checkbox" x-model="field.required" class="rounded border-surface-lighter bg-surface text-brand-500 focus:ring-brand-500 h-3.5 w-3.5">
                                    Required
                                </label>
                                <button type="button" @click="toggleExpand(index)" class="text-white/30 hover:text-white p-1">
                                    <svg class="w-4 h-4 transition-transform" :class="field._expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <button type="button" @click="removeField(index)" class="text-red-400/50 hover:text-red-400 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- Hidden inputs --}}
                            <input type="hidden" :name="'fields['+index+'][type]'" :value="field.type">
                            <input type="hidden" :name="'fields['+index+'][id]'" :value="field.id || ''">
                            <input type="hidden" :name="'fields['+index+'][required]'" :value="field.required ? '1' : '0'">
                            <input type="hidden" :name="'fields['+index+'][description]'" :value="field.description || ''">
                            <input type="hidden" :name="'fields['+index+'][options]'" :value="JSON.stringify(field.options || null)">

                            {{-- Expanded options --}}
                            <div x-show="field._expanded" x-transition class="mt-4 space-y-3 border-t border-surface-lighter pt-4">
                                <div>
                                    <label class="block text-xs text-white/50 mb-1">Description / Help text</label>
                                    <input type="text" x-model="field.description" placeholder="Optional description for respondents"
                                        class="w-full rounded-lg border-0 py-1.5 px-3 bg-surface text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                </div>

                                {{-- Text field: data type --}}
                                <template x-if="field.type === 'text'">
                                    <div>
                                        <label class="block text-xs text-white/50 mb-2">Data Type</label>
                                        <div class="flex gap-2 mb-3">
                                            <button type="button" @click="if(!field.options) field.options = {}; field.options.datatype = 'text'"
                                                :class="(!field.options?.datatype || field.options?.datatype === 'text') ? 'bg-brand-500/20 border-brand-500/40 text-brand-300' : 'bg-surface border-surface-lighter text-white/50'"
                                                class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors">Text</button>
                                            <button type="button" @click="if(!field.options) field.options = {}; field.options.datatype = 'number'"
                                                :class="field.options?.datatype === 'number' ? 'bg-brand-500/20 border-brand-500/40 text-brand-300' : 'bg-surface border-surface-lighter text-white/50'"
                                                class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors">Number</button>
                                        </div>
                                        <template x-if="field.options?.datatype === 'number'">
                                            <div class="grid grid-cols-3 gap-2">
                                                <div>
                                                    <label class="block text-xs text-white/40 mb-1">Min</label>
                                                    <input type="number" x-model="field.options.min" placeholder="—"
                                                        class="w-full rounded-lg border-0 py-1.5 px-3 bg-surface text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-white/40 mb-1">Max</label>
                                                    <input type="number" x-model="field.options.max" placeholder="—"
                                                        class="w-full rounded-lg border-0 py-1.5 px-3 bg-surface text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-white/40 mb-1">Step</label>
                                                    <input type="number" x-model="field.options.step" placeholder="1" step="any"
                                                        class="w-full rounded-lg border-0 py-1.5 px-3 bg-surface text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Select / Multi-select options --}}
                                <template x-if="field.type === 'select' || field.type === 'multi_select'">
                                    <div>
                                        <label class="block text-xs text-white/50 mb-2">Options</label>
                                        <template x-for="(opt, oi) in (field.options || [])" :key="oi">
                                            <div class="flex gap-2 mb-2">
                                                <input type="text" x-model="field.options[oi]" placeholder="Option value"
                                                    class="flex-1 rounded-lg border-0 py-1.5 px-3 bg-surface text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                                <button type="button" @click="field.options.splice(oi, 1)" class="text-red-400/50 hover:text-red-400 px-2 text-sm">&times;</button>
                                            </div>
                                        </template>
                                        <button type="button" @click="if(!field.options) field.options = []; field.options.push('')"
                                            class="text-xs text-brand-400 hover:text-brand-300">+ Add option</button>
                                    </div>
                                </template>

                                {{-- Date slots --}}
                                <template x-if="field.type === 'date_slots'">
                                    @include('admin.forms.partials.date-slot-builder')
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Add field buttons --}}
                <div class="border-t border-surface-lighter pt-4">
                    <p class="text-xs text-white/40 mb-3">Add a field</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        @foreach($fieldTypes as $type)
                        <button type="button" @click="addField('{{ $type->value }}')"
                            class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-surface-lighter bg-surface-light hover:bg-brand-500/10 hover:border-brand-500/30 text-white/60 hover:text-brand-400 transition-all text-xs font-medium">
                            <span class="text-lg">{!! $type->icon() !!}</span>
                            <span>{{ $type->label() }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Form settings --}}
        <div class="lg:w-80 shrink-0">
            <div class="bg-surface border border-surface-lighter rounded-xl p-6 space-y-5 lg:sticky lg:top-6">
                <h2 class="text-lg font-semibold text-white">Settings</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title', $form?->title) }}" required
                        class="w-full rounded-lg border-0 py-2 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm"
                        @input="autoSlug($event)">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">URL Slug</label>
                    <div class="flex items-center">
                        <span class="text-xs text-white/30 mr-1">/f/</span>
                        <input type="text" name="slug" value="{{ old('slug', $form?->slug) }}" x-ref="slugInput"
                            class="flex-1 rounded-lg border-0 py-2 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm font-mono text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full rounded-lg border-0 py-2 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">{{ old('description', $form?->description) }}</textarea>
                    <p class="text-xs text-white/30 mt-1">Supports basic Markdown: **bold**, *italic*, [links](url), lists</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Language</label>
                    <select name="language"
                        class="w-full rounded-lg border-0 py-2 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                        @foreach(\App\FormTranslations::availableLanguages() as $code => $label)
                            <option value="{{ $code }}" {{ old('language', $form?->language ?? 'en') === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-white/30 mt-1">Controls the language of all UI text on the public form.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Header Image</label>
                    @if($form?->header_image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/uploads/headers/' . $form->header_image) }}" class="w-full h-20 object-cover rounded-lg" alt="">
                            <label class="flex items-center gap-2 mt-1 text-xs text-white/50">
                                <input type="checkbox" name="remove_header_image" value="1" class="rounded border-surface-lighter bg-surface text-brand-500 focus:ring-brand-500 h-3.5 w-3.5">
                                Remove image
                            </label>
                        </div>
                    @endif
                    <input type="file" name="header_image" accept="image/*"
                        class="w-full text-sm text-gray-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-surface-lighter file:text-gray-300 hover:file:bg-surface-light">
                </div>

                <div class="border-t border-surface-lighter pt-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Auto-deactivate at</label>
                    <input type="datetime-local" name="active_until"
                        value="{{ old('active_until', $form?->active_until ? $form->active_until->format('Y-m-d\TH:i') : '') }}"
                        class="w-full rounded-lg border-0 py-2 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                    <p class="text-xs text-white/30 mt-1">Leave empty for no expiry. Time is in your local timezone.</p>
                </div>

                <div class="flex items-center justify-between border-t border-surface-lighter pt-4">
                    <div>
                        <p class="text-sm font-medium text-white">Allow respondents to edit</p>
                        <p class="text-xs text-white/40 mt-0.5">Gives each respondent a personal edit URL</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="allow_edit" value="0">
                        <input type="checkbox" name="allow_edit" value="1" {{ old('allow_edit', $form?->allow_edit) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-surface-lighter rounded-full peer peer-checked:bg-brand-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-white">Active</p>
                        <p class="text-xs text-white/40 mt-0.5">Form is accepting responses</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $form ? $form->is_active : true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-surface-lighter rounded-full peer peer-checked:bg-brand-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                </div>

                <div class="border-t border-surface-lighter pt-4 flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-black hover:bg-brand-400 transition-colors">
                        {{ $form ? 'Update Form' : 'Create Form' }}
                    </button>
                    <a href="{{ $form ? route('admin.forms.show', $form) : route('admin.forms.index') }}"
                        class="rounded-lg bg-surface-lighter px-4 py-2.5 text-sm text-gray-300 hover:bg-surface-light transition-colors">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function formBuilder() {
    return {
        fields: @json($existingFields),
        _counter: {{ count($existingFields) }},

        init() {
            this.$nextTick(() => {
                this.initSortable();
            });
        },

        initSortable() {
            if (this.$refs.fieldList && window.Sortable) {
                Sortable.create(this.$refs.fieldList, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: (evt) => {
                        const item = this.fields.splice(evt.oldIndex, 1)[0];
                        this.fields.splice(evt.newIndex, 0, item);
                    }
                });
            }
        },

        addField(type) {
            this._counter++;
            const field = {
                id: null,
                type: type,
                label: '',
                description: '',
                options: (type === 'select' || type === 'multi_select')
                    ? ['']
                    : (type === 'date_slots' ? { multi_select: true, slots: [] } : null),
                required: false,
                _key: 'new_' + this._counter,
                _expanded: true,
            };
            this.fields.push(field);
        },

        removeField(index) {
            this.fields.splice(index, 1);
        },

        toggleExpand(index) {
            this.fields[index]._expanded = !this.fields[index]._expanded;
        },

        autoSlug(event) {
            if (!this.$refs.slugInput.value || !{{ $form ? 'true' : 'false' }}) {
                this.$refs.slugInput.value = event.target.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .substring(0, 255);
            }
        },

        submitForm(el) {
            // Drop client-only bookkeeping and any half-filled slot rows.
            this.fields.forEach(f => {
                if (f.type === 'date_slots' && f.options && Array.isArray(f.options.slots)) {
                    f.options.slots = f.options.slots
                        .filter(s => s.date && s.start_time && s.end_time)
                        .map(({ date, start_time, end_time }) => ({ date, start_time, end_time }));
                }
            });

            this.$nextTick(() => el.submit());
        }
    };
}

function dateSlotBuilder(field) {
    const pad = (n) => String(n).padStart(2, '0');
    const toMinutes = (t) => {
        const [h, m] = String(t).split(':').map(Number);
        return h * 60 + m;
    };
    const toTime = (mins) => `${pad(Math.floor(mins / 60) % 24)}:${pad(mins % 60)}`;
    const isoDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    return {
        field,
        days: [],
        viewYear: 0,
        viewMonth: 0,
        pattern: { start: '09:00', duration: 60, repeat: 4, gap: 0 },
        pasteMode: false,
        pasteText: '',
        pasteErrors: [],
        dragFrom: null,
        _uid: 0,

        init() {
            // Tolerate the legacy flat-array shape on forms saved before the
            // options migration ran.
            if (!this.field.options || Array.isArray(this.field.options)) {
                const legacy = Array.isArray(this.field.options) ? this.field.options : [];
                this.field.options = {
                    multi_select: legacy.length ? !!legacy[0].multi_select : true,
                    slots: legacy.map(s => ({
                        date: s.date || '',
                        start_time: s.start_time || '',
                        end_time: s.end_time || '',
                    })),
                };
            }
            if (!Array.isArray(this.field.options.slots)) {
                this.field.options.slots = [];
            }
            this.field.options.slots.forEach(s => this.tag(s));

            const first = this.slots.map(s => s.date).filter(Boolean).sort()[0];
            const anchor = first ? new Date(first + 'T00:00:00') : new Date();
            this.viewYear = anchor.getFullYear();
            this.viewMonth = anchor.getMonth();
        },

        // Stable :key for x-for — slot times are edited in place, so they
        // cannot serve as identity.
        tag(slot) {
            if (!slot._id) slot._id = 'slot_' + (++this._uid);
            return slot;
        },

        get slots() { return this.field.options.slots; },

        get monthLabel() {
            return new Date(this.viewYear, this.viewMonth, 1)
                .toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        },

        shiftMonth(delta) {
            const d = new Date(this.viewYear, this.viewMonth + delta, 1);
            this.viewYear = d.getFullYear();
            this.viewMonth = d.getMonth();
        },

        get calendarCells() {
            const first = new Date(this.viewYear, this.viewMonth, 1);
            // Monday-first grid.
            const lead = (first.getDay() + 6) % 7;
            const start = new Date(this.viewYear, this.viewMonth, 1 - lead);

            return Array.from({ length: 42 }, (_, i) => {
                const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                return {
                    date: isoDate(d),
                    day: d.getDate(),
                    inMonth: d.getMonth() === this.viewMonth,
                };
            });
        },

        isSelected(date) { return this.days.includes(date); },
        hasSlots(date) { return this.slots.some(s => s.date === date); },

        toggleDay(date) {
            const i = this.days.indexOf(date);
            if (i > -1) this.days.splice(i, 1);
            else this.days.push(date);
        },

        selectRange(from, to) {
            const [a, b] = from <= to ? [from, to] : [to, from];
            const cursor = new Date(a + 'T00:00:00');
            const end = new Date(b + 'T00:00:00');
            const range = [];
            while (cursor <= end) {
                range.push(isoDate(cursor));
                cursor.setDate(cursor.getDate() + 1);
            }
            this.days = [...new Set([...this.days, ...range])];
        },

        get previewSlots() {
            const count = Math.max(0, Math.min(24, Number(this.pattern.repeat) || 0));
            const duration = Math.max(1, Number(this.pattern.duration) || 0);
            const gap = Math.max(0, Number(this.pattern.gap) || 0);
            if (!this.pattern.start) return [];

            let cursor = toMinutes(this.pattern.start);
            const out = [];
            for (let i = 0; i < count; i++) {
                const end = cursor + duration;
                if (end > 24 * 60) break; // never spill past midnight
                out.push({ start_time: toTime(cursor), end_time: toTime(end) });
                cursor = end + gap;
            }
            return out;
        },

        applyPattern() {
            const generated = this.previewSlots;
            if (!generated.length || !this.days.length) return;

            // Regenerating a day replaces whatever was there.
            const untouched = this.slots.filter(s => !this.days.includes(s.date));
            const fresh = this.days.flatMap(date =>
                generated.map(g => this.tag({ date, ...g }))
            );

            this.field.options.slots = this.sort([...untouched, ...fresh]);
            this.days = [];
        },

        sort(slots) {
            return slots.sort((a, b) =>
                (a.date + a.start_time).localeCompare(b.date + b.start_time));
        },

        get groupedSlots() {
            const byDate = {};
            for (const slot of this.sort(this.slots)) {
                (byDate[slot.date] ??= []).push(slot);
            }
            return Object.keys(byDate).sort().map(date => ({ date, slots: byDate[date] }));
        },

        formatDay(date) {
            if (!date) return 'No date';
            return new Date(date + 'T00:00:00').toLocaleDateString(undefined, {
                weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
            });
        },

        addSlotTo(date) {
            const last = this.slots.filter(s => s.date === date).pop();
            const start = last?.end_time || this.pattern.start || '09:00';
            const end = toTime(Math.min(24 * 60, toMinutes(start) + (Number(this.pattern.duration) || 60)));
            this.field.options.slots = this.sort([...this.slots, this.tag({ date, start_time: start, end_time: end })]);
        },

        removeSlot(slot) {
            const i = this.slots.indexOf(slot);
            if (i > -1) this.slots.splice(i, 1);
        },

        removeDay(date) {
            this.field.options.slots = this.slots.filter(s => s.date !== date);
        },

        applyPaste(replace) {
            const parsed = [];
            const errors = [];

            this.pasteText.split('\n').forEach((raw, idx) => {
                const line = raw.trim();
                if (!line) return;

                const head = line.match(/^(\d{4}-\d{2}-\d{2})\s+(.+)$/);
                if (!head) {
                    errors.push(`Line ${idx + 1}: expected "YYYY-MM-DD <times>"`);
                    return;
                }

                const date = head[1];
                head[2].split(',').forEach(token => {
                    const t = token.trim();
                    if (!t) return;

                    const m = t.match(/^(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})(?:\s*\/\s*(\d+))?$/);
                    if (!m) {
                        errors.push(`Line ${idx + 1}: cannot read "${t}"`);
                        return;
                    }

                    const from = toMinutes(m[1]);
                    const to = toMinutes(m[2]);
                    if (to <= from) {
                        errors.push(`Line ${idx + 1}: "${t}" ends before it starts`);
                        return;
                    }

                    const chunk = m[3] ? Number(m[3]) : (to - from);
                    if (chunk <= 0) {
                        errors.push(`Line ${idx + 1}: "${t}" has an invalid split`);
                        return;
                    }

                    for (let s = from; s + chunk <= to; s += chunk) {
                        parsed.push(this.tag({ date, start_time: toTime(s), end_time: toTime(s + chunk) }));
                    }
                });
            });

            this.pasteErrors = errors;
            if (!parsed.length) return;

            const base = replace ? [] : this.slots;
            const seen = new Set(base.map(s => `${s.date}_${s.start_time}_${s.end_time}`));
            const merged = [...base];

            for (const slot of parsed) {
                const key = `${slot.date}_${slot.start_time}_${slot.end_time}`;
                if (seen.has(key)) continue;
                seen.add(key);
                merged.push(slot);
            }

            this.field.options.slots = this.sort(merged);
            if (!errors.length) {
                this.pasteText = '';
                this.pasteMode = false;
            }
        },
    };
}
</script>
@endsection
