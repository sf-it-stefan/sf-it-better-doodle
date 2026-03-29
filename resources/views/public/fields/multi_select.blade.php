<div>
    <fieldset>
        <legend class="block text-sm font-medium text-white mb-1">
            {{ $field->label }}
            @if($field->required) <span class="text-red-400">*</span> @endif
        </legend>
        @if($field->description)
            <p class="text-xs text-white/40 mb-2">{{ $field->description }}</p>
        @endif
        <div class="space-y-2">
            @foreach($field->options ?? [] as $option)
                <label class="flex items-center gap-3 px-4 py-3 rounded-xl border border-white/10 bg-white/5 hover:border-white/20 cursor-pointer transition-colors has-[:checked]:bg-brand-500/10 has-[:checked]:border-brand-500/30">
                    <input type="checkbox" name="field_{{ $field->id }}[]" value="{{ $option }}"
                        {{ is_array($value) && in_array($option, $value) ? 'checked' : '' }}
                        class="rounded border-white/30 bg-transparent text-brand-500 focus:ring-brand-500 h-4 w-4">
                    <span class="text-sm text-white/80">{{ $option }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
