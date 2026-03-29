@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-surface border border-surface-lighter rounded-xl p-5">
        <p class="text-xs text-white/50 uppercase tracking-widest mb-1">Total Forms</p>
        <p class="text-3xl font-bold text-white">{{ $totalForms }}</p>
    </div>
    <div class="bg-surface border border-surface-lighter rounded-xl p-5">
        <p class="text-xs text-white/50 uppercase tracking-widest mb-1">Active Forms</p>
        <p class="text-3xl font-bold text-brand-400">{{ $activeForms }}</p>
    </div>
    <div class="bg-surface border border-surface-lighter rounded-xl p-5">
        <p class="text-xs text-white/50 uppercase tracking-widest mb-1">Total Responses</p>
        <p class="text-3xl font-bold text-white">{{ $totalEntries }}</p>
    </div>
</div>

<div class="bg-surface border border-surface-lighter rounded-xl overflow-hidden">
    <div class="px-6 py-4 border-b border-surface-lighter flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white">Recent Forms</h2>
        <a href="{{ route('admin.forms.create') }}" class="inline-flex items-center gap-1 rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-semibold text-black hover:bg-brand-400 transition-colors">
            + New Form
        </a>
    </div>

    @if($recentForms->isEmpty())
        <div class="px-6 py-12 text-center">
            <p class="text-white/40 mb-4">You haven't created any forms yet.</p>
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
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-lighter">
                @foreach($recentForms as $form)
                <tr class="hover:bg-surface-light/50">
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
                        <a href="{{ route('admin.forms.show', $form) }}" class="text-white hover:text-brand-400 transition-colors">{{ $form->title }}</a>
                    </td>
                    <td class="px-6 py-3 text-white/60">{{ $form->entries_count }}</td>
                    <td class="px-6 py-3 text-white/60">
                        @if($form->active_until)
                            <span x-data x-text="new Date('{{ $form->active_until->toIso8601String() }}').toLocaleDateString()">{{ $form->active_until->format('M j, Y') }}</span>
                        @else
                            Never
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
