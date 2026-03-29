@extends('layouts.admin')

@section('title', 'Forms')
@section('heading', 'Forms')

@section('heading_actions')
<a href="{{ route('admin.forms.create') }}" class="inline-flex items-center gap-1 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-black hover:bg-brand-400 transition-colors">
    + New Form
</a>
@endsection

@section('content')
<div class="mb-4">
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search forms..."
            class="flex-1 rounded-lg border-0 py-2 px-4 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 sm:text-sm">
        <button type="submit" class="rounded-lg bg-surface-light px-4 py-2 text-sm text-gray-300 ring-1 ring-inset ring-surface-lighter hover:bg-surface-lighter transition-colors">Search</button>
    </form>
</div>

<div class="bg-surface border border-surface-lighter rounded-xl overflow-hidden">
    @if($forms->isEmpty())
        <div class="px-6 py-12 text-center">
            <p class="text-white/40 mb-4">No forms found.</p>
            <a href="{{ route('admin.forms.create') }}" class="inline-flex items-center gap-1 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-black hover:bg-brand-400 transition-colors">
                Create your first form
            </a>
        </div>
    @else
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-white/50 text-xs uppercase tracking-wider">
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Title</th>
                    <th class="px-6 py-3">Responses</th>
                    <th class="px-6 py-3">Expiry</th>
                    <th class="px-6 py-3">Created</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-lighter">
                @foreach($forms as $form)
                <tr class="hover:bg-surface-light/50 {{ $form->isExpired() ? 'opacity-60' : '' }}">
                    <td class="px-6 py-3">
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
                    </td>
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.forms.show', $form) }}" class="text-white hover:text-brand-400 transition-colors font-medium">{{ $form->title }}</a>
                        <p class="text-xs text-white/30 mt-0.5">/f/{{ $form->slug }}</p>
                    </td>
                    <td class="px-6 py-3 text-white/60">{{ $form->entries_count }}</td>
                    <td class="px-6 py-3 text-white/60">
                        @if($form->active_until)
                            <span x-data x-text="new Date('{{ $form->active_until->toIso8601String() }}').toLocaleDateString()">{{ $form->active_until->format('M j, Y') }}</span>
                        @else
                            Never
                        @endif
                    </td>
                    <td class="px-6 py-3 text-white/40">{{ $form->created_at->format('M j, Y') }}</td>
                    <td class="px-6 py-3 text-right" x-data="{ open: false }">
                        <div class="relative inline-block text-left">
                            <button @click="open = !open" @click.outside="open = false" class="text-white/40 hover:text-white p-1 rounded hover:bg-surface-lighter">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="4" r="2"/><circle cx="10" cy="10" r="2"/><circle cx="10" cy="16" r="2"/></svg>
                            </button>
                            <div x-show="open" x-transition class="absolute right-0 mt-1 w-48 bg-surface-light border border-surface-lighter rounded-lg shadow-lg z-10">
                                <a href="{{ route('admin.forms.show', $form) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-surface-lighter hover:text-white">View Details</a>
                                <a href="{{ route('admin.forms.entries', $form) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-surface-lighter hover:text-white">View Responses</a>
                                <a href="{{ route('admin.forms.edit', $form) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-surface-lighter hover:text-white">Edit</a>
                                <button @click="navigator.clipboard.writeText('{{ $form->getPublicUrl() }}'); open = false" class="w-full text-left block px-4 py-2 text-sm text-gray-300 hover:bg-surface-lighter hover:text-white">Copy Link</button>
                                <form method="POST" action="{{ route('admin.forms.destroy', $form) }}" onsubmit="return confirm('Delete this form and ALL its entries?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-400 hover:bg-red-500/10">Delete</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-surface-lighter">
            {{ $forms->links() }}
        </div>
    @endif
</div>
@endsection
