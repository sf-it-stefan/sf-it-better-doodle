<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
    @foreach($entries as $entry)
        <a href="{{ route('admin.forms.entries.show', array_merge([$form, $entry], $linkContext)) }}"
           class="group block bg-surface border border-surface-lighter rounded-xl p-4 hover:border-brand-500/60 hover:bg-surface-light/30 transition-colors">
            <div class="flex items-baseline justify-between gap-2 mb-3">
                <p class="text-xs text-white/50"
                   x-data x-text="new Date('{{ $entry->created_at->toIso8601String() }}').toLocaleString()">
                    {{ $entry->created_at->format('M j, Y g:i A') }}
                </p>
                <p class="text-[10px] text-white/25 font-mono shrink-0">{{ $entry->ip_address }}</p>
            </div>

            <dl class="space-y-2">
                @foreach($form->fields->take(4) as $field)
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-white/35">{{ Str::limit($field->label, 30) }}</dt>
                        <dd class="text-sm text-white/80 truncate">
                            <x-entries.field-value-teaser
                                :field="$field"
                                :value="$entry->data[$field->id] ?? null"
                                :form="$form"
                                :entry="$entry"
                                :limit="60" />
                        </dd>
                    </div>
                @endforeach
            </dl>

            @if($form->fields->count() > 4)
                <p class="text-[10px] text-white/25 mt-2">+{{ $form->fields->count() - 4 }} more</p>
            @endif

            <p class="text-xs text-brand-400/70 group-hover:text-brand-300 mt-3">View full response &rarr;</p>
        </a>
    @endforeach
</div>
