<div>
    <label class="flex items-center gap-3 px-4 py-3 rounded-xl border border-white/10 bg-white/5 hover:border-white/20 cursor-pointer transition-colors has-[:checked]:bg-brand-500/10 has-[:checked]:border-brand-500/30">
        <input type="hidden" name="field_{{ $field->id }}" value="0">
        <input type="checkbox" name="field_{{ $field->id }}" value="1"
            {{ $value ? 'checked' : '' }}
            {{ $field->required ? 'required' : '' }}
            class="rounded border-white/30 bg-transparent text-brand-500 focus:ring-brand-500 h-5 w-5">
        <div>
            <span class="text-sm font-medium text-white">{{ $field->label }}</span>
            @if($field->required) <span class="text-red-400">*</span> @endif
            @if($field->description)
                <p class="text-xs text-white/40 mt-0.5">{{ $field->description }}</p>
            @endif
        </div>
    </label>
</div>
