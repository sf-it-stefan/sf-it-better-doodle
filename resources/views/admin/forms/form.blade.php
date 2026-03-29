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
                                    <div>
                                        <label class="block text-xs text-white/50 mb-2">Date/Time Slots</label>
                                        <div class="space-y-3">
                                            <template x-for="(slot, si) in (field.options || [])" :key="si">
                                                <div class="flex gap-2 items-center bg-surface rounded-lg p-2">
                                                    <input type="date" x-model="field.options[si].date"
                                                        class="rounded-lg border-0 py-1.5 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                                    <input type="time" x-model="field.options[si].start_time" placeholder="Start"
                                                        class="rounded-lg border-0 py-1.5 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                                    <span class="text-white/30">&ndash;</span>
                                                    <input type="time" x-model="field.options[si].end_time" placeholder="End"
                                                        class="rounded-lg border-0 py-1.5 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                                    <button type="button" @click="field.options.splice(si, 1)" class="text-red-400/50 hover:text-red-400 px-2 text-sm">&times;</button>
                                                </div>
                                            </template>
                                        </div>
                                        <button type="button" @click="if(!field.options) field.options = []; field.options.push({date: '', start_time: '', end_time: ''})"
                                            class="mt-2 text-xs text-brand-400 hover:text-brand-300">+ Add date/time slot</button>

                                        <div class="mt-3">
                                            <label class="flex items-center gap-2 text-xs text-white/50">
                                                <input type="checkbox" x-model="field._multiSelect" class="rounded border-surface-lighter bg-surface text-brand-500 focus:ring-brand-500 h-3.5 w-3.5">
                                                Allow selecting multiple slots
                                            </label>
                                        </div>
                                    </div>
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
                options: (type === 'select' || type === 'multi_select') ? [''] : (type === 'date_slots' ? [{date: '', start_time: '', end_time: ''}] : null),
                required: false,
                _key: 'new_' + this._counter,
                _expanded: true,
                _multiSelect: type === 'date_slots' ? true : false,
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
            // For date_slots fields, inject multiSelect into options
            this.fields.forEach(f => {
                if (f.type === 'date_slots' && f.options) {
                    f.options = f.options.map(slot => ({
                        ...slot,
                        multi_select: f._multiSelect
                    }));
                }
            });

            this.$nextTick(() => el.submit());
        }
    };
}
</script>
@endsection
