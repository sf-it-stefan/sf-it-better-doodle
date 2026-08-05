@props(['field', 'value', 'form', 'entry', 'limit' => 80])

@php
    use App\Enums\FieldType;
    $isEmpty = $value === null || $value === '' || (is_array($value) && count($value) === 0);
@endphp

@if($isEmpty)
    <span class="text-white/20 italic text-xs">&mdash;</span>
@elseif($field->type === FieldType::FileUpload && is_array($value) && isset($value['original_name']))
    <a href="{{ route('admin.forms.entries.download', [$form, $entry, $field->id]) }}"
       @click.stop
       class="text-brand-400 hover:text-brand-300 text-xs underline">{{ Str::limit($value['original_name'], 30) }}</a>
@elseif($field->type === FieldType::SecretText)
    <span class="text-white/30 text-xs italic">hidden</span>
@elseif($field->type === FieldType::ImageUpload)
    <img src="{{ asset('storage/' . $value) }}" class="w-10 h-10 rounded object-cover" alt="">
@elseif($field->type === FieldType::Checkbox)
    {{ $value ? 'Yes' : 'No' }}
@elseif($field->type === FieldType::DateSlots)
    @php
        $answers = $field->dateSlotAnswers($value);
        $text = collect($answers)
            ->map(fn ($a) => $a['label'] . ($a['state'] === 'maybe' ? ' (if need be)' : ''))
            ->implode(', ');
    @endphp
    @if($text === '')
        <span class="text-white/20 italic text-xs">No availability</span>
    @else
        {{ Str::limit($text, $limit) }}
    @endif
@elseif(is_array($value))
    {{ Str::limit(implode(', ', $value), $limit) }}
@else
    {{ Str::limit((string) $value, $limit) }}
@endif
