<div>
    <label for="field_{{ $field->id }}" class="block text-sm font-medium text-white mb-1">
        {{ $field->label }}
        @if($field->required) <span class="text-red-400">*</span> @endif
    </label>
    @if($field->description)
        <p class="text-xs text-white/40 mb-2">{{ $field->description }}</p>
    @endif
    <select name="field_{{ $field->id }}" id="field_{{ $field->id }}"
        {{ $field->required ? 'required' : '' }}
        class="w-full h-12 px-4 bg-white/5 border border-white/10 rounded-xl text-white text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
        <option value="" class="bg-surface">{{ $t['select_placeholder'] }}</option>
        @foreach($field->options ?? [] as $option)
            <option value="{{ $option }}" {{ $value === $option ? 'selected' : '' }} class="bg-surface">{{ $option }}</option>
        @endforeach
    </select>
</div>
