@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
<div class="max-w-lg">
    <div class="bg-surface border border-surface-lighter rounded-xl p-6">
        <h2 class="text-lg font-semibold text-white mb-4">Change Password</h2>

        <form method="POST" action="{{ route('admin.settings.change-password') }}" class="space-y-4">
            @csrf
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-300 mb-1">Current Password</label>
                <input type="password" name="current_password" id="current_password" required
                    class="w-full rounded-lg border-0 py-2 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">New Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full rounded-lg border-0 py-2 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full rounded-lg border-0 py-2 px-3 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-black hover:bg-brand-400 transition-colors">
                Change Password
            </button>
        </form>
    </div>
</div>
@endsection
