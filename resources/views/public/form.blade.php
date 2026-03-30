@extends('layouts.public')

@section('title', $form->title)

@section('content')
<div x-data="formPage()">
    {{-- Fullscreen glassmorphism overlay --}}
    <div :style="blocked
            ? 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1.5rem;opacity:1;transition:opacity 0.2s ease'
            : 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1.5rem;opacity:0;pointer-events:none;transition:opacity 0.15s ease'">
        {{-- Backdrop --}}
        <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>
        {{-- Modal card --}}
        <div style="position: relative; background: rgba(36,36,36,0.95); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 2rem; max-width: 24rem; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); text-align: center;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 9999px; background: rgba(245,158,11,0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                <svg style="width: 1.75rem; height: 1.75rem; color: #fbbf24; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #fff; margin-bottom: 0.5rem;">{{ $t['duplicate_title'] }}</h2>
            <p style="font-size: 0.875rem; color: rgba(255,255,255,0.5); margin-bottom: 1.5rem;">{{ $t['duplicate_message'] }}</p>
            <button @click="blocked = false"
                class="w-full h-11 rounded-xl bg-brand-500 text-black text-sm font-semibold hover:bg-brand-400 transition-colors">
                {{ $t['duplicate_continue'] }}
            </button>
        </div>
    </div>

    <div class="bg-surface border border-white/10 rounded-2xl overflow-hidden">
        @if($form->header_image)
            <img src="{{ asset('storage/uploads/headers/' . $form->header_image) }}" class="w-full h-48 object-cover" alt="">
        @endif

        <div class="p-6 sm:p-8">
            <h1 class="text-2xl font-bold text-white mb-2">{{ $form->title }}</h1>
            @if($form->description)
                <div class="text-white/60 text-sm mb-6 prose-sm prose-invert prose-a:text-brand-400 prose-a:underline [&>p]:mb-2 [&>ul]:list-disc [&>ul]:pl-5 [&>ul]:mb-2 [&>ol]:list-decimal [&>ol]:pl-5 [&>ol]:mb-2 [&>p:last-child]:mb-0">
                    {!! Str::markdown($form->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                </div>
            @endif

            @if($form->active_until)
                <div class="mb-6 text-xs text-white/40 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $t['open_until'] }} <span x-data x-text="new Date('{{ $form->active_until->toIso8601String() }}').toLocaleString()">{{ $form->active_until->format('M j, Y g:i A') }} UTC</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-500/10 border border-red-500/20 p-4">
                    <ul class="list-disc list-inside text-sm text-red-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ isset($isEdit) ? route('form.update', ['slug' => $form->slug, 'token' => $entry->edit_token]) : route('form.submit', ['slug' => $form->slug]) }}"
                  enctype="multipart/form-data"
                  @submit.prevent="if (!blocked) { $el.submit() }">
                @csrf
                @if(isset($isEdit)) @method('PUT') @endif

                {{-- Honeypot --}}
                <div class="absolute -left-[9999px] top-0" aria-hidden="true" tabindex="-1">
                    <label for="website">Leave this blank</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="space-y-6">
                    @foreach($form->fields as $field)
                        @include('public.fields.' . $field->type->value, [
                            'field' => $field,
                            'value' => isset($entry) ? ($entry->data[$field->id] ?? null) : old('field_' . $field->id),
                        ])
                    @endforeach
                </div>

                <div class="mt-6 text-xs text-white/30 leading-relaxed">
                    {{ $form->active_until ? $t['privacy_notice'] : $t['privacy_notice_no_expiry'] }}
                </div>

                <div class="mt-4">
                    <button type="submit" :disabled="blocked"
                        class="w-full h-12 rounded-xl bg-brand-500 text-black font-semibold text-sm hover:bg-brand-400 active:bg-brand-600 transition-colors focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ isset($isEdit) ? $t['update_response'] : $t['submit'] }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function formPage() {
    return {
        blocked: {{ $hasExistingEntry ? 'true' : 'false' }},
    };
}
</script>
@endsection
