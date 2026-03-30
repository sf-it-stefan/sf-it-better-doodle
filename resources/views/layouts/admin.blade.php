<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'Admin') - {{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-surface-dark text-gray-200">
    <div class="min-h-full">
        <nav class="bg-surface border-b border-surface-lighter">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2" x-data="logo()" @mousemove.window="moveEyes($event)">
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
                            <span class="text-brand-400 font-bold text-lg">Better Doodle</span>
                        </a>
                        <div class="ml-10 flex items-baseline space-x-1">
                            <a href="{{ route('admin.dashboard') }}" class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-brand-500/20 text-brand-300' : 'text-gray-400 hover:bg-surface-lighter hover:text-gray-200' }}">Dashboard</a>
                            <a href="{{ route('admin.forms.index') }}" class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.forms.*') ? 'bg-brand-500/20 text-brand-300' : 'text-gray-400 hover:bg-surface-lighter hover:text-gray-200' }}">Forms</a>
                            <a href="{{ route('admin.settings') }}" class="rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.settings') ? 'bg-brand-500/20 text-brand-300' : 'text-gray-400 hover:bg-surface-lighter hover:text-gray-200' }}">Settings</a>
                        </div>
                    </div>
                    <div>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-brand-300 text-sm">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-surface border-b border-surface-lighter">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight text-gray-100">@yield('heading')</h1>
                @yield('heading_actions')
            </div>
        </header>

        <main>
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-4 rounded-md bg-brand-900/50 border border-brand-700 p-4">
                        <p class="text-sm text-brand-200">{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-900/50 border border-red-700 p-4">
                        <p class="text-sm text-red-200">{{ session('error') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-900/50 border border-red-700 p-4">
                        <ul class="list-disc list-inside text-sm text-red-200">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
    function logo() {
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

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
    </script>
</body>
</html>
