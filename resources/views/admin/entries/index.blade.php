@extends('layouts.admin')

@section('title', 'Responses - ' . $form->title)
@section('heading', 'Responses: ' . $form->title)

@section('heading_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.forms.entries.export', $form) }}" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        Export CSV
    </a>
    <a href="{{ route('admin.forms.show', $form) }}" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        Back to Form
    </a>
</div>
@endsection

@section('content')
<div class="bg-surface border border-surface-lighter rounded-xl overflow-hidden">
    @if($entries->isEmpty())
        <div class="px-6 py-12 text-center">
            <p class="text-white/40">No responses yet.</p>
        </div>
    @else
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
                    <tr class="hover:bg-surface-light/50">
                        <td class="px-4 py-3 text-white/60 whitespace-nowrap">
                            <span x-data x-text="new Date('{{ $entry->created_at->toIso8601String() }}').toLocaleString()">{{ $entry->created_at->format('M j, Y g:i A') }}</span>
                        </td>
                        <td class="px-4 py-3 text-white/40 text-xs font-mono whitespace-nowrap">{{ $entry->ip_address }}</td>
                        @foreach($form->fields as $field)
                            <td class="px-4 py-3 text-white/80 max-w-xs truncate">
                                @php $value = $entry->data[$field->id] ?? null; @endphp
                                @if($field->type === \App\Enums\FieldType::FileUpload && is_array($value) && isset($value['original_name']))
                                    <a href="{{ route('admin.forms.entries.download', [$form, $entry, $field->id]) }}" class="text-brand-400 hover:text-brand-300 text-xs underline">{{ $value['original_name'] }}</a>
                                @elseif($field->type === \App\Enums\FieldType::SecretText && $value)
                                    <span class="text-white/30 text-xs italic">hidden</span>
                                @elseif(is_array($value))
                                    {{ implode(', ', $value) }}
                                @elseif($field->type === \App\Enums\FieldType::ImageUpload && $value)
                                    <img src="{{ asset('storage/' . $value) }}" class="w-10 h-10 rounded object-cover" alt="">
                                @elseif($field->type === \App\Enums\FieldType::Checkbox)
                                    {{ $value ? 'Yes' : 'No' }}
                                @else
                                    {{ Str::limit((string)$value, 50) }}
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.forms.entries.destroy', [$form, $entry]) }}" onsubmit="return confirm('Delete this entry?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400/50 hover:text-red-400 text-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-surface-lighter">
            {{ $entries->links() }}
        </div>
    @endif
</div>
@endsection
