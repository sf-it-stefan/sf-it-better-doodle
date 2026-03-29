<!DOCTYPE html>
<html lang="{{ $form->language ?? 'en' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Form') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
<body class="min-h-full bg-surface-dark text-gray-200">
    <div class="min-h-screen flex flex-col">
        {{-- Logo header --}}
        <header class="py-6 flex justify-center">
            <a href="{{ url('/') }}" class="flex items-center gap-2 group" x-data="publicLogo()" @mousemove.window="moveEyes($event)">
                <div class="relative w-10 h-[18px] cursor-pointer" @click="spin()">
                    <div class="absolute left-0 w-[45%] h-full bg-white rounded-[7.5%] flex justify-center items-center" style="border: 2px solid #2dd4bf">
                        <span class="absolute w-[5px] h-[5px]" x-ref="leftEye">
                            <span class="block w-full h-full rounded-full bg-black" :class="isSpinning ? 'eye-spin' : ''"></span>
                        </span>
                    </div>
                    <div class="absolute bg-brand-400" style="left: 45%; top: 45%; margin-top: 2px; width: 4px; height: 2px;"></div>
                    <div class="absolute right-0 w-[45%] h-full bg-white rounded-[7.5%] flex justify-center items-center" style="border: 2px solid #2dd4bf">
                        <span class="absolute w-[5px] h-[5px]" x-ref="rightEye">
                            <span class="block w-full h-full rounded-full bg-black" :class="isSpinning ? 'eye-spin' : ''"></span>
                        </span>
                    </div>
                </div>
                <span class="text-brand-400 font-bold text-lg group-hover:text-brand-300 transition-colors">Better Doodle</span>
            </a>
        </header>

        <main class="flex-1 flex items-start justify-center px-4 pb-8">
            <div class="w-full max-w-2xl">
                @yield('content')
            </div>
        </main>

        <footer class="py-6 text-center">
            <a href="{{ url('/') }}" class="text-xs text-white/20 hover:text-white/40 transition-colors">
                {{ $t['powered_by'] ?? 'Powered by SF-IT Better Doodle' }}
            </a>
        </footer>
    </div>

    <script>
    function publicLogo() {
        return {
            isSpinning: false,
            moveEyes(event) {
                if (this.isSpinning) return;
                [this.$refs.leftEye, this.$refs.rightEye].forEach(eye => {
                    if (!eye) return;
                    const rect = eye.parentElement.getBoundingClientRect();
                    const cx = rect.left + rect.width / 2;
                    const cy = rect.top + rect.height / 2;
                    const maxDist = rect.width / 4;
                    const angle = Math.atan2(event.clientY - cy, event.clientX - cx);
                    const dist = Math.min(maxDist, Math.hypot(event.clientX - cx, event.clientY - cy));
                    eye.style.transform = `translate(${Math.cos(angle) * dist}px, ${Math.sin(angle) * dist}px)`;
                });
            },
            spin() {
                if (this.isSpinning) return;
                this.isSpinning = true;
                setTimeout(() => this.isSpinning = false, 800);
            }
        };
    }
    </script>

    @yield('scripts')
</body>
</html>
