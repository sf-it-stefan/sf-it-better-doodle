@extends('layouts.admin')

@section('title', 'Availability - ' . $form->title)
@section('heading', 'Availability: ' . $form->title)

@section('heading_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.forms.entries', $form) }}" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        Responses
    </a>
    <a href="{{ route('admin.forms.show', $form) }}" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        Back to Form
    </a>
</div>
@endsection

@section('content')
@php
    $respondentCount = $entries->count();
    $grouped = collect($slots)->groupBy('date');
    $maxYes = collect($slots)->max('yes') ?: 0;
@endphp

@if($confirmed)
    <div class="mb-4 rounded-xl border border-brand-500/50 bg-brand-500/10 px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <p class="text-xs uppercase tracking-wider text-brand-300/70">Confirmed</p>
            <p class="text-lg font-semibold text-white">{{ $confirmed['label'] }}</p>
            <p class="text-xs text-white/40 mt-0.5">Shown on the public form instead of the picker.</p>
        </div>
        <form method="POST" action="{{ route('admin.forms.availability.confirm', $form) }}">
            @csrf
            <input type="hidden" name="slot_id" value="">
            <button type="submit" class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
                Unconfirm
            </button>
        </form>
    </div>
@endif

@if($respondentCount === 0)
    <div class="bg-surface border border-surface-lighter rounded-xl px-6 py-12 text-center">
        <p class="text-white/40">No responses yet &mdash; nothing to compare.</p>
    </div>
@else
    @if($best && !$confirmed)
        <div class="mb-4 rounded-xl border border-surface-lighter bg-surface px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wider text-white/40">Best slot</p>
                <p class="text-lg font-semibold text-white">{{ $best['label'] }}</p>
                <p class="text-xs text-white/50 mt-0.5">
                    {{ $best['yes'] }} of {{ $respondentCount }} available{{ $best['maybe'] ? ", {$best['maybe']} if need be" : '' }}
                </p>
            </div>
            <form method="POST" action="{{ route('admin.forms.availability.confirm', $form) }}">
                @csrf
                <input type="hidden" name="slot_id" value="{{ $best['id'] }}">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-black hover:bg-brand-400 transition-colors whitespace-nowrap">
                    Confirm this slot
                </button>
            </form>
        </div>
    @endif

    <p class="text-xs text-white/40 mb-3">
        {{ $respondentCount }} {{ Str::plural('response', $respondentCount) }}.
        Click a slot to see who answered what.
    </p>

    <div class="space-y-4" x-data="{ open: null }">
        @foreach($grouped as $date => $dateSlots)
            <div class="bg-surface border border-surface-lighter rounded-xl overflow-hidden">
                <p class="px-4 py-2.5 text-xs uppercase tracking-wider text-white/50 border-b border-surface-lighter"
                   x-data x-text="new Date('{{ $date }}T00:00:00').toLocaleDateString(undefined, {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'})">
                    {{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}
                </p>

                <div class="divide-y divide-surface-lighter">
                    @foreach($dateSlots as $slot)
                        @php
                            // Bar is scaled against the best-performing slot, not the
                            // respondent count, so differences stay visible.
                            $pct = $maxYes > 0 ? round($slot['yes'] / $maxYes * 100) : 0;
                            $isBest = $best && $slot['id'] === $best['id'];
                            $isConfirmed = $confirmed && $slot['id'] === $confirmed['slot_id'];
                        @endphp
                        <div>
                            <button type="button" @click="open = (open === '{{ $slot['id'] }}' ? null : '{{ $slot['id'] }}')"
                                    class="w-full text-left px-4 py-3 hover:bg-surface-light/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-gray-100 w-32 shrink-0">
                                        {{ $slot['start_time'] }}@if($slot['end_time']) &ndash; {{ $slot['end_time'] }}@endif
                                    </span>

                                    <span class="flex-1 h-6 rounded bg-surface-dark overflow-hidden flex min-w-0">
                                        <span class="bg-brand-500 h-full" style="width: {{ $respondentCount ? $slot['yes'] / $respondentCount * 100 : 0 }}%"></span>
                                        <span class="bg-amber-500/60 h-full" style="width: {{ $respondentCount ? $slot['maybe'] / $respondentCount * 100 : 0 }}%"></span>
                                    </span>

                                    <span class="text-xs shrink-0 w-24 text-right">
                                        <span class="text-brand-300 font-medium">{{ $slot['yes'] }}</span><span class="text-white/25">/{{ $respondentCount }}</span>
                                        @if($slot['maybe'])
                                            <span class="text-amber-300/80">+{{ $slot['maybe'] }}</span>
                                        @endif
                                    </span>

                                    @if($isConfirmed)
                                        <span class="text-[10px] uppercase tracking-wider text-brand-300 shrink-0">Confirmed</span>
                                    @elseif($isBest)
                                        <span class="text-[10px] uppercase tracking-wider text-white/40 shrink-0">Best</span>
                                    @else
                                        <span class="w-16 shrink-0"></span>
                                    @endif
                                </div>
                            </button>

                            <div x-show="open === '{{ $slot['id'] }}'" x-cloak class="px-4 pb-4 pt-1">
                                <div class="flex flex-wrap gap-1.5 mb-3">
                                    @foreach($slot['respondents'] as $r)
                                        <a href="{{ route('admin.forms.entries.show', [$form, $r['entry']]) }}"
                                           class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs transition-colors
                                            @if($r['state'] === 'yes') bg-brand-500/15 text-brand-200 hover:bg-brand-500/25
                                            @elseif($r['state'] === 'maybe') bg-amber-500/15 text-amber-200 hover:bg-amber-500/25
                                            @else bg-white/5 text-white/35 hover:bg-white/10 @endif">
                                            #{{ $r['number'] }}
                                            <span class="opacity-60">
                                                {{ $r['state'] === 'yes' ? 'yes' : ($r['state'] === 'maybe' ? 'if need be' : 'no') }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>

                                @if(!$isConfirmed)
                                    <form method="POST" action="{{ route('admin.forms.availability.confirm', $form) }}">
                                        @csrf
                                        <input type="hidden" name="slot_id" value="{{ $slot['id'] }}">
                                        <button type="submit" class="rounded-lg bg-surface-lighter px-3 py-1.5 text-xs text-gray-200 hover:bg-surface-light transition-colors">
                                            Confirm this slot
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
