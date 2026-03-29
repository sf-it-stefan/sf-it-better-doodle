@extends('layouts.admin')

@section('title', $form->title)
@section('heading', $form->title)

@section('heading_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.forms.entries', $form) }}" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        Responses ({{ $form->entries_count }})
    </a>
    <a href="{{ route('admin.forms.edit', $form) }}" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        Edit
    </a>
    <form method="POST" action="{{ route('admin.forms.destroy', $form) }}" onsubmit="return confirm('Delete this form and ALL its entries?')" class="inline">
        @csrf @method('DELETE')
        <button type="submit" class="rounded-lg bg-red-500/10 border border-red-500/20 px-4 py-2 text-sm text-red-400 hover:bg-red-500/20 transition-colors">Delete</button>
    </form>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Public URL card --}}
    <div class="lg:col-span-2 bg-surface border border-surface-lighter rounded-xl p-6">
        <h2 class="text-sm font-medium text-white/50 uppercase tracking-wider mb-3">Public URL</h2>
        <div class="flex items-center gap-2" x-data="{ copied: false }">
            <code class="flex-1 bg-surface-light border border-surface-lighter rounded-lg px-4 py-3 text-sm text-brand-400 font-mono break-all">
                {{ $form->getPublicUrl() }}
            </code>
            <button @click="navigator.clipboard.writeText('{{ $form->getPublicUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="shrink-0 rounded-lg bg-surface-lighter px-3 py-3 text-sm text-gray-300 hover:bg-surface-light transition-colors">
                <span x-show="!copied">Copy</span>
                <span x-show="copied" class="text-brand-400">Copied!</span>
            </button>
            <a href="{{ $form->getPublicUrl() }}" target="_blank"
                class="shrink-0 rounded-lg bg-surface-lighter px-3 py-3 text-sm text-gray-300 hover:bg-surface-light transition-colors">
                Open
            </a>
        </div>

        <div class="mt-4 flex items-center gap-4 text-sm">
            @if($form->isAcceptingResponses())
                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full bg-brand-500/15 text-brand-400 border border-brand-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-400"></span> Active
                </span>
            @elseif($form->isExpired())
                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Expired
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full bg-gray-500/15 text-gray-400 border border-gray-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                </span>
            @endif

            @if($form->active_until)
                <span class="text-white/40">
                    Expires: <span x-data x-text="new Date('{{ $form->active_until->toIso8601String() }}').toLocaleString()">{{ $form->active_until->format('M j, Y g:i A') }}</span>
                </span>
            @endif

            @if($form->allow_edit)
                <span class="text-white/40">Edit links enabled</span>
            @endif
        </div>
    </div>

    {{-- Stats card --}}
    <div class="bg-surface border border-surface-lighter rounded-xl p-6">
        <h2 class="text-sm font-medium text-white/50 uppercase tracking-wider mb-3">Stats</h2>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-white/60">Responses</span>
                <span class="text-white font-semibold">{{ $form->entries_count }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-white/60">Fields</span>
                <span class="text-white font-semibold">{{ $form->fields->count() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-white/60">Created</span>
                <span class="text-white/60">{{ $form->created_at->format('M j, Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Fields overview --}}
    <div class="lg:col-span-3 bg-surface border border-surface-lighter rounded-xl p-6">
        <h2 class="text-sm font-medium text-white/50 uppercase tracking-wider mb-4">Form Fields</h2>
        <div class="space-y-2">
            @foreach($form->fields as $field)
                <div class="flex items-center gap-3 bg-surface-light border border-surface-lighter rounded-lg px-4 py-3">
                    <span class="text-xs text-brand-400 font-mono uppercase w-24">{{ $field->type->label() }}</span>
                    <span class="text-white">{{ $field->label }}</span>
                    @if($field->required)
                        <span class="text-xs text-red-400/60">required</span>
                    @endif
                    @if($field->description)
                        <span class="text-xs text-white/30 ml-auto">{{ Str::limit($field->description, 40) }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
