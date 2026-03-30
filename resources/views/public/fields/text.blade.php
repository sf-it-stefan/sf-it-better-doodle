@php
    $datatype = $field->options['datatype'] ?? 'text';
    $isNumber = $datatype === 'number';
@endphp
<div>
    <label for="field_{{ $field->id }}" class="block text-sm font-medium text-white mb-1">
        {{ $field->label }}
        @if($field->required) <span class="text-red-400">*</span> @endif
    </label>
    @if($field->description)
        <p class="text-xs text-white/40 mb-2">{{ $field->description }}</p>
    @endif
    <input type="{{ $isNumber ? 'number' : 'text' }}"
        name="field_{{ $field->id }}" id="field_{{ $field->id }}" value="{{ $value }}"
        {{ $field->required ? 'required' : '' }}
        @if($isNumber && isset($field->options['min'])) min="{{ $field->options['min'] }}" @endif
        @if($isNumber && isset($field->options['max'])) max="{{ $field->options['max'] }}" @endif
        @if($isNumber && isset($field->options['step'])) step="{{ $field->options['step'] }}" @endif
        class="w-full h-12 px-4 bg-white/5 border border-white/10 rounded-xl text-white text-sm placeholder:text-white/30 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
</div>
