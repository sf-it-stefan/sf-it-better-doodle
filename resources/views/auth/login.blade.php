<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-surface-dark text-gray-200">
    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm flex flex-col items-center">
            {{-- Logo --}}
            <div x-data="loginLogo()" @mousemove.window="moveEyes($event)" class="mb-6">
                <div class="relative cursor-pointer" style="width: 8rem; aspect-ratio: 32.5 / 15;" @click="spin()">
                    <div class="absolute left-0 w-[45%] h-full bg-white rounded-[7.5%] flex justify-center items-center" style="border: 3px solid #2dd4bf">
                        <span class="absolute" style="width: 0.5rem; height: 0.5rem;" x-ref="leftEye">
                            <span class="block w-full h-full rounded-full bg-black" :class="isSpinning ? 'eye-spin' : ''"></span>
                        </span>
                    </div>
                    <div class="absolute bg-brand-400" style="left: 45%; top: 45%; margin-top: 3px; width: 0.5rem; height: 0.25rem;"></div>
                    <div class="absolute right-0 w-[45%] h-full bg-white rounded-[7.5%] flex justify-center items-center" style="border: 3px solid #2dd4bf">
                        <span class="absolute" style="width: 0.5rem; height: 0.5rem;" x-ref="rightEye">
                            <span class="block w-full h-full rounded-full bg-black" :class="isSpinning ? 'eye-spin' : ''"></span>
                        </span>
                    </div>
                </div>
            </div>
            <h2 class="text-center text-2xl font-bold tracking-tight text-brand-400">Better Doodle</h2>
            <p class="mt-2 text-center text-sm text-gray-400">Sign in to the admin dashboard</p>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-900/50 border border-red-700 p-4">
                    <p class="text-sm text-red-200">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                        class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 bg-surface-light text-gray-100 shadow-sm ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                    <input type="password" name="password" id="password" required
                        class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 bg-surface-light text-gray-100 shadow-sm ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="h-4 w-4 rounded border-surface-lighter bg-surface-light text-brand-500 focus:ring-brand-500">
                    <label for="remember" class="ml-2 text-sm text-gray-300">Remember me</label>
                </div>

                <button type="submit" class="w-full rounded-md bg-brand-500 px-3 py-1.5 text-sm font-semibold text-black shadow-sm hover:bg-brand-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
                    Sign in
                </button>
            </form>
        </div>
    </div>
    <script>
    function loginLogo() {
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
</body>
</html>
