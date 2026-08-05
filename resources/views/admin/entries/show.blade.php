@extends('layouts.admin')

@section('title', 'Response - ' . $form->title)
@section('heading', 'Response ' . $position . ' of ' . $total)

@section('heading_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.forms.entries', array_merge([$form], $context)) }}"
       class="rounded-lg bg-surface-lighter px-4 py-2 text-sm text-gray-300 hover:bg-surface-light transition-colors">
        All responses
    </a>
</div>
@endsection

@section('content')
@php
    // First non-empty answer, used as the rail teaser.
    $teaserFor = function ($e) use ($form) {
        foreach ($form->fields as $f) {
            $v = $e->data[$f->id] ?? null;
            if ($v === null || $v === '' || (is_array($v) && count($v) === 0)) {
                continue;
            }
            if ($f->type === \App\Enums\FieldType::SecretText) {
                continue;
            }
            if ($f->type === \App\Enums\FieldType::DateSlots) {
                return collect((array) $v)->map(fn ($id) => $f->dateSlotLabel((string) $id))->implode(', ');
            }
            if (is_array($v)) {
                return isset($v['original_name']) ? $v['original_name'] : implode(', ', $v);
            }
            return is_bool($v) ? ($v ? 'Yes' : 'No') : (string) $v;
        }
        return null;
    };
@endphp

<div x-data="entryReader(
        @js($prev ? route('admin.forms.entries.show', array_merge([$form, $prev], $context)) : null),
        @js($next ? route('admin.forms.entries.show', array_merge([$form, $next], $context)) : null),
        @js(route('admin.forms.entries', array_merge([$form], $context)))
     )"
     @keydown.window="onKey($event)"
     class="flex gap-6">

    {{-- Rail: jump to any response without going back to the list --}}
    <aside class="hidden lg:block w-72 shrink-0">
        <div class="bg-surface border border-surface-lighter rounded-xl overflow-hidden sticky top-6 max-h-[calc(100vh-6rem)] overflow-y-auto">
            @foreach($siblings as $sibling)
                @php $isCurrent = $sibling->id === $entry->id; @endphp
                <a href="{{ route('admin.forms.entries.show', array_merge([$form, $sibling], $context)) }}"
                   @if($isCurrent) x-init="$el.scrollIntoView({ block: 'nearest' })" @endif
                   class="block px-4 py-3 border-b border-surface-lighter last:border-0 transition-colors {{ $isCurrent ? 'bg-brand-500/15 border-l-2 border-l-brand-500' : 'hover:bg-surface-light/50' }}">
                    <p class="text-xs {{ $isCurrent ? 'text-brand-300' : 'text-white/40' }}"
                       x-data x-text="new Date('{{ $sibling->created_at->toIso8601String() }}').toLocaleString()">
                        {{ $sibling->created_at->format('M j, Y g:i A') }}
                    </p>
                    @php $teaser = $teaserFor($sibling); @endphp
                    <p class="text-sm truncate mt-0.5 {{ $isCurrent ? 'text-white' : 'text-white/70' }}">
                        {{ $teaser ?? 'No answers' }}
                    </p>
                </a>
            @endforeach
            @if($siblings->count() >= 200)
                <p class="px-4 py-3 text-xs text-white/30 italic">Showing first 200 &mdash; use search to narrow.</p>
            @endif
        </div>
    </aside>

    {{-- Reader --}}
    <div class="flex-1 min-w-0">
        <div class="bg-surface border border-surface-lighter rounded-xl overflow-hidden">

            <div class="sticky top-0 z-10 bg-surface/95 backdrop-blur border-b border-surface-lighter px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm text-gray-200 truncate"
                       x-data x-text="new Date('{{ $entry->created_at->toIso8601String() }}').toLocaleString()"
                       title="{{ $entry->created_at->toIso8601String() }}">
                        {{ $entry->created_at->format('M j, Y g:i A') }}
                    </p>
                    <p class="text-xs text-white/35 font-mono mt-0.5">{{ $entry->ip_address }}</p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if($prev)
                        <a href="{{ route('admin.forms.entries.show', array_merge([$form, $prev], $context)) }}"
                           class="rounded-lg bg-surface-lighter hover:bg-surface-light px-3 py-2 text-sm text-gray-300 transition-colors min-w-11 flex items-center justify-center"
                           aria-label="Previous response">&uarr;</a>
                    @else
                        <span class="rounded-lg bg-surface-lighter/40 px-3 py-2 text-sm text-white/20 min-w-11 flex items-center justify-center">&uarr;</span>
                    @endif
                    @if($next)
                        <a href="{{ route('admin.forms.entries.show', array_merge([$form, $next], $context)) }}"
                           class="rounded-lg bg-surface-lighter hover:bg-surface-light px-3 py-2 text-sm text-gray-300 transition-colors min-w-11 flex items-center justify-center"
                           aria-label="Next response">&darr;</a>
                    @else
                        <span class="rounded-lg bg-surface-lighter/40 px-3 py-2 text-sm text-white/20 min-w-11 flex items-center justify-center">&darr;</span>
                    @endif
                </div>
            </div>

            <div class="px-4 sm:px-6 py-6 space-y-6">
                @foreach($form->fields as $field)
                    <div>
                        <p class="text-xs uppercase tracking-wider text-white/50 mb-2">
                            {{ $field->label }}
                        </p>
                        <x-entries.field-value-full
                            :field="$field"
                            :value="$entry->data[$field->id] ?? null"
                            :form="$form"
                            :entry="$entry" />
                    </div>
                @endforeach
            </div>

            <div class="px-4 sm:px-6 py-4 border-t border-surface-lighter flex items-center justify-between gap-4">
                <p class="text-xs text-white/25 hidden sm:block">
                    <kbd class="font-mono">j</kbd>/<kbd class="font-mono">k</kbd> or arrows to move &middot; <kbd class="font-mono">Esc</kbd> for the list
                </p>
                @if($form->allow_edit && $entry->edit_token)
                    <a href="{{ route('form.edit', [$form->slug, $entry->edit_token]) }}" target="_blank" rel="noopener"
                       class="text-xs text-white/40 hover:text-white/70 underline">Open respondent edit link</a>
                @endif
                <form method="POST" action="{{ route('admin.forms.entries.destroy', [$form, $entry]) }}"
                      onsubmit="return confirm('Delete this entry?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400/60 hover:text-red-400">Delete response</button>
                </form>
            </div>
        </div>
    </div>
</div>

    {{-- Image lightbox. Lives inside entryReader's scope so Escape closes it
         instead of navigating back to the list. --}}
    <div x-show="lightbox"
         x-cloak
         @click="lightbox = null"
         class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 cursor-zoom-out">
        <img :src="lightbox" class="max-w-full max-h-full object-contain rounded-lg" alt="">
    </div>
</div>

<script>
function entryReader(prevUrl, nextUrl, listUrl) {
    return {
        lightbox: null,

        init() {
            this.$el.addEventListener('lightbox-open', (e) => {
                this.lightbox = e.detail.src;
            });
        },

        onKey(e) {
            // Never hijack typing or browser/OS shortcuts.
            if (e.metaKey || e.ctrlKey || e.altKey) return;
            const t = e.target;
            if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;

            if (e.key === 'Escape') {
                // The lightbox swallows the first Escape.
                if (this.lightbox) {
                    this.lightbox = null;
                } else {
                    window.location = listUrl;
                }
                return;
            }

            if (this.lightbox) return;

            if ((e.key === 'k' || e.key === 'ArrowUp') && prevUrl) {
                e.preventDefault();
                window.location = prevUrl;
            } else if ((e.key === 'j' || e.key === 'ArrowDown') && nextUrl) {
                e.preventDefault();
                window.location = nextUrl;
            }
        }
    };
}
</script>
@endsection
