<?php

use App\Models\Form;
use App\Models\FormEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// Auto-deactivate expired forms every minute
Schedule::call(function () {
    Form::where('is_active', true)
        ->whereNotNull('active_until')
        ->where('active_until', '<=', now())
        ->update(['is_active' => false]);
})->everyMinute()->name('deactivate-expired-forms');

// Delete expired forms and all their data after 90 days (GDPR cleanup)
Schedule::call(function () {
    $cutoff = now()->subDays(90);
    $forms = Form::whereNotNull('active_until')
        ->where('active_until', '<=', $cutoff)
        ->get();

    foreach ($forms as $form) {
        Log::info("GDPR cleanup: deleting form '{$form->title}' (expired {$form->active_until->toDateString()})");
        $form->delete();
    }
})->daily()->name('gdpr-cleanup-expired-forms');

// Delete old entries from forms without expiry after 365 days
Schedule::call(function () {
    $cutoff = now()->subDays(365);
    $entries = FormEntry::whereHas('form', fn ($q) => $q->whereNull('active_until'))
        ->where('created_at', '<=', $cutoff)
        ->get();

    foreach ($entries as $entry) {
        Log::info("GDPR cleanup: deleting old entry from form '{$entry->form->title}' (submitted {$entry->created_at->toDateString()})");
        $entry->delete();
    }
})->daily()->name('gdpr-cleanup-old-entries');
