<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-surface-dark text-gray-200">
    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <h2 class="mt-10 text-center text-2xl font-bold tracking-tight text-brand-400">Better Doodle</h2>
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
</body>
</html>
