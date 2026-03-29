@extends('layouts.public')

@section('title', $form->title)

@section('content')
<div class="bg-surface border border-white/10 rounded-2xl p-6 sm:p-8 text-center">
    <div class="w-16 h-16 rounded-full bg-brand-500/15 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>

    <h1 class="text-2xl font-bold text-white mb-2">
        @if(session('success'))
            {{ $t['response_updated_title'] }}
        @else
            {{ $t['thank_you_title'] }}
        @endif
    </h1>
    <p class="text-white/60 text-sm mb-6">{{ $t['response_recorded'] }}</p>

    @if($editUrl)
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 text-left mt-6">
            <p class="text-sm text-amber-300 font-medium mb-2">{{ $t['edit_prompt'] }}</p>
            <div class="flex items-center gap-2" x-data="{ copied: false }">
                <code class="flex-1 bg-surface-dark border border-white/10 rounded-lg px-3 py-2.5 text-xs text-brand-400 font-mono break-all select-all">{{ $editUrl }}</code>
                <button @click="navigator.clipboard.writeText('{{ $editUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="shrink-0 rounded-lg bg-surface-dark border border-white/10 px-3 py-2.5 text-xs text-gray-300 hover:bg-surface-light transition-colors">
                    <span x-show="!copied">{{ $t['copy'] }}</span>
                    <span x-show="copied" class="text-brand-400">{{ $t['copied'] }}</span>
                </button>
            </div>
            <p class="text-xs text-amber-300/60 mt-2">{{ $t['save_link_warning'] }}</p>
        </div>
    @endif
</div>
@endsection
