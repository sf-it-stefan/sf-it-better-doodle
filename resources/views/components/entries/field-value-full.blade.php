@props(['field', 'value', 'form', 'entry'])

@php
    use App\Enums\FieldType;
    $isEmpty = $value === null || $value === '' || (is_array($value) && count($value) === 0);
@endphp

@if($isEmpty && $field->type !== FieldType::Checkbox)
    <p class="text-white/25 italic text-sm">Not answered</p>

@elseif($field->type === FieldType::Textarea)
    <p class="text-gray-100 text-sm leading-relaxed whitespace-pre-line max-w-2xl">{{ $value }}</p>

@elseif($field->type === FieldType::Select)
    <span class="inline-block rounded-full bg-surface-lighter px-3 py-1 text-sm text-gray-100">{{ $value }}</span>

@elseif($field->type === FieldType::MultiSelect)
    <div class="flex flex-wrap gap-2">
        @foreach((array) $value as $v)
            <span class="inline-block rounded-full bg-surface-lighter px-3 py-1 text-sm text-gray-100">{{ $v }}</span>
        @endforeach
    </div>

@elseif($field->type === FieldType::DateSlots)
    @php $answers = $field->dateSlotAnswers($value); @endphp
    @if(empty($answers))
        <p class="text-white/25 italic text-sm">No availability given</p>
    @else
        <div class="flex flex-wrap gap-2">
            @foreach($answers as $answer)
                <span class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm border
                    {{ $answer['state'] === 'maybe'
                        ? 'bg-amber-500/15 border-amber-500/40 text-amber-200'
                        : 'bg-brand-500/15 border-brand-500/40 text-brand-200' }}">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $answer['label'] }}
                    @if($answer['state'] === 'maybe')
                        <span class="text-[10px] uppercase tracking-wider opacity-70">if need be</span>
                    @endif
                </span>
            @endforeach
        </div>
    @endif

@elseif($field->type === FieldType::Checkbox)
    <span class="inline-flex items-center gap-2 text-sm {{ $value ? 'text-brand-300' : 'text-white/40' }}">
        <span class="w-5 h-5 rounded border-2 flex items-center justify-center shrink-0 {{ $value ? 'border-brand-400 bg-brand-400' : 'border-white/25' }}">
            @if($value)
                <svg class="w-3 h-3 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 12 12">
                    <path stroke-linecap="round" d="M10 3L5 8.5 2 5.5"/>
                </svg>
            @endif
        </span>
        {{ $value ? 'Yes' : 'No' }}
    </span>

@elseif($field->type === FieldType::ImageUpload)
    <button type="button"
            x-data
            @click="$dispatch('lightbox-open', { src: '{{ asset('storage/' . $value) }}' })"
            class="block rounded-xl overflow-hidden ring-1 ring-surface-lighter hover:ring-brand-500 transition-all">
        <img src="{{ asset('storage/' . $value) }}" class="max-w-full sm:max-w-80 max-h-80 object-contain bg-surface-dark" alt="">
    </button>

@elseif($field->type === FieldType::FileUpload && is_array($value))
    <a href="{{ route('admin.forms.entries.download', [$form, $entry, $field->id]) }}"
       class="inline-flex items-center gap-2 rounded-lg bg-surface-lighter hover:bg-surface-light px-4 py-2 text-sm text-gray-100 transition-colors">
        <svg class="w-4 h-4 shrink-0 text-white/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        {{ $value['original_name'] ?? 'Download' }}
    </a>

@elseif($field->type === FieldType::SecretText)
    {{-- Stored in plain text; masking here guards against shoulder-surfing, not disclosure. --}}
    <div x-data="{ revealed: false }" class="flex items-center gap-3">
        <span x-show="!revealed" class="font-mono text-sm text-white/40 tracking-widest select-none">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span>
        <span x-show="revealed" x-cloak class="font-mono text-sm text-gray-100 break-all">{{ $value }}</span>
        <button type="button" @click="revealed = !revealed"
                class="text-xs text-brand-400 hover:text-brand-300 shrink-0"
                x-text="revealed ? 'Hide' : 'Reveal'">Reveal</button>
    </div>

@elseif(is_array($value))
    <p class="text-gray-100 text-sm max-w-2xl">{{ implode(', ', $value) }}</p>

@else
    <p class="text-gray-100 text-sm leading-relaxed wrap-break-word max-w-2xl">{{ $value }}</p>
@endif
