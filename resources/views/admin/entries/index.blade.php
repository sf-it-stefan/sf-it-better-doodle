@extends('layouts.admin')

@section('title', 'Responses - ' . $form->title)
@section('heading', 'Responses: ' . $form->title)

@section('heading_actions')
<div class="flex flex-wrap items-center gap-2">
    @if($form->fields->contains('type', \App\Enums\FieldType::DateSlots))
        <a href="{{ route('admin.forms.availability', $form) }}" class="rounded-lg bg-brand-500/15 border border-brand-500/40 px-4 py-2 text-sm text-brand-200 hover:bg-brand-500/25 transition-colors">
            Find a date
        </a>
    @endif
    <a href="{{ route('admin.forms.entries.export', $form) }}" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        Export CSV
    </a>
    <a href="{{ route('admin.forms.show', $form) }}" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        Back to Form
    </a>
</div>
@endsection

@section('content')
@php
    $query = ['search' => $search ?: null, 'sort' => $sort === 'newest' ? null : $sort];
    $linkContext = array_filter($query + ['view' => $view === 'cards' ? null : $view]);
@endphp

{{-- Toolbar --}}
<div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3">
    <form method="GET" action="{{ route('admin.forms.entries', $form) }}" class="flex-1 flex gap-2">
        <input type="hidden" name="view" value="{{ $view }}">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="search" name="search" value="{{ $search }}" placeholder="Search responses&hellip;"
               class="flex-1 rounded-lg border-0 py-2 px-3 bg-surface text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm placeholder:text-white/25">
        <button type="submit" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">Search</button>
        @if($search)
            <a href="{{ route('admin.forms.entries', array_filter([$form, 'view' => $view === 'cards' ? null : $view])) }}"
               class="rounded-lg px-3 py-2 text-sm text-white/40 hover:text-white/70 transition-colors">Clear</a>
        @endif
    </form>

    <div class="flex items-center gap-2">
        <a href="{{ route('admin.forms.entries', array_filter([$form] + $query + ['view' => $view, 'sort' => $sort === 'oldest' ? null : 'oldest'])) }}"
           class="rounded-lg bg-surface-lighter px-3 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors whitespace-nowrap">
            {{ $sort === 'oldest' ? 'Oldest first' : 'Newest first' }}
        </a>

        <div class="flex rounded-lg bg-surface-lighter p-0.5">
            @foreach(['cards' => 'Cards', 'table' => 'Table'] as $mode => $label)
                <a href="{{ route('admin.forms.entries', array_filter([$form] + $query + ['view' => $mode === 'cards' ? null : $mode])) }}"
                   class="rounded-md px-3 py-1.5 text-sm transition-colors {{ $view === $mode ? 'bg-surface text-white' : 'text-white/50 hover:text-white/80' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</div>

@if($entries->isEmpty())
    <div class="bg-surface border border-surface-lighter rounded-xl px-6 py-12 text-center">
        <p class="text-white/40">{{ $search ? 'No responses match that search.' : 'No responses yet.' }}</p>
    </div>
@else
    {{-- Table is opt-in and desktop-only; a 10-column grid is unusable on a phone
         regardless of the remembered preference, so cards win under lg. --}}
    @if($view === 'table')
        <div class="hidden lg:block">
            @include('admin.entries._table', ['linkContext' => $linkContext])
        </div>
        <div class="lg:hidden">
            @include('admin.entries._cards', ['linkContext' => $linkContext])
        </div>
    @else
        @include('admin.entries._cards', ['linkContext' => $linkContext])
    @endif

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
@endif
@endsection
