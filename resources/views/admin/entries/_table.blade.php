<div class="bg-surface border border-surface-lighter rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-white/50 text-xs uppercase tracking-wider">
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3">IP</th>
                    @foreach($form->fields as $field)
                        <th class="px-4 py-3">{{ Str::limit($field->label, 20) }}</th>
                    @endforeach
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-lighter">
                @foreach($entries as $entry)
                @php $showUrl = route('admin.forms.entries.show', array_merge([$form, $entry], $linkContext)); @endphp
                <tr class="hover:bg-surface-light/50 cursor-pointer"
                    x-data
                    @click="window.location = @js($showUrl)">
                    <td class="px-4 py-3 text-white/60 whitespace-nowrap">
                        <a href="{{ $showUrl }}" class="hover:text-white"
                           x-data x-text="new Date('{{ $entry->created_at->toIso8601String() }}').toLocaleString()">{{ $entry->created_at->format('M j, Y g:i A') }}</a>
                    </td>
                    <td class="px-4 py-3 text-white/40 text-xs font-mono whitespace-nowrap">{{ $entry->ip_address }}</td>
                    @foreach($form->fields as $field)
                        <td class="px-4 py-3 text-white/80 max-w-xs truncate">
                            <x-entries.field-value-teaser
                                :field="$field"
                                :value="$entry->data[$field->id] ?? null"
                                :form="$form"
                                :entry="$entry"
                                :limit="50" />
                        </td>
                    @endforeach
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.forms.entries.destroy', [$form, $entry]) }}"
                              onsubmit="return confirm('Delete this entry?')" class="inline" @click.stop>
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400/50 hover:text-red-400 text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
