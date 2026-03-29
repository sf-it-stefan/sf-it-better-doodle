<div>
    <label for="field_{{ $field->id }}" class="block text-sm font-medium text-white mb-1">
        {{ $field->label }}
        @if($field->required) <span class="text-red-400">*</span> @endif
    </label>
    @if($field->description)
        <p class="text-xs text-white/40 mb-2">{{ $field->description }}</p>
    @endif

    @if(is_array($value) && isset($value['original_name']))
        <div class="mb-2 flex items-center gap-2 text-xs text-white/50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            {{ $value['original_name'] }}
        </div>
    @endif

    <input type="file" name="field_{{ $field->id }}" id="field_{{ $field->id }}"
        {{ $field->required && !$value ? 'required' : '' }}
        class="w-full text-sm text-white/60 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-white/5 file:text-white/80 hover:file:bg-white/10 file:cursor-pointer file:transition-colors">
    <p class="text-xs text-white/25 mt-1.5">{{ $t['file_max_size'] ?? 'Max. 20MB' }}</p>
</div>
