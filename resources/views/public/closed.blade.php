@extends('layouts.public')

@section('title', $form->title . ' - ' . $t['form_closed'])
@section('og_title', $form->title)
@section('og_description', $t['form_closed'])
@if($form->header_image)
    @section('og_image', asset('storage/uploads/headers/' . $form->header_image))
@endif

@section('content')
<div class="bg-surface border border-white/10 rounded-2xl p-6 sm:p-8 text-center">
    <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
    </div>
    <h1 class="text-xl font-bold text-white mb-2">{{ $t['form_closed'] }}</h1>
    @if($form->isExpired())
        <p class="text-white/50 text-sm">
            {{ $t['form_closed_on'] }}
            <span x-data x-text="new Date('{{ $form->active_until->toIso8601String() }}').toLocaleDateString()">{{ $form->active_until->format('M j, Y') }}</span>
        </p>
    @else
        <p class="text-white/50 text-sm">{{ $t['form_no_longer_accepting'] }}</p>
    @endif

    <p class="text-white/30 text-xs mt-4">{{ $form->title }}</p>

    @if($form->allow_edit)
        <p class="text-white/30 text-xs mt-2">{{ $t['edit_link_note'] }}</p>
    @endif
</div>
@endsection
