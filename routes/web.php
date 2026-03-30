<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\FormEntryController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

// Redirect root to admin
Route::redirect('/', '/admin');

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin (auth required)
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('forms', FormController::class);
    Route::get('forms/{form}/entries', [FormEntryController::class, 'index'])->name('forms.entries');
    Route::delete('forms/{form}/entries/{entry}', [FormEntryController::class, 'destroy'])->name('forms.entries.destroy');
    Route::get('forms/{form}/export', [FormEntryController::class, 'export'])->name('forms.entries.export');
    Route::get('forms/{form}/entries/{entry}/download/{fieldId}', [FormEntryController::class, 'download'])->name('forms.entries.download');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('settings/change-password', [SettingsController::class, 'changePassword'])->name('settings.change-password');
});

// OG image
Route::get('/og/default.png', [App\Http\Controllers\OgImageController::class, 'default'])->name('og.default');

// Public form routes
Route::get('/f/{slug}', [PublicFormController::class, 'show'])->name('form.show');
Route::post('/f/{slug}', [PublicFormController::class, 'submit'])->middleware('throttle:10,1')->name('form.submit');
Route::get('/f/{slug}/thanks', [PublicFormController::class, 'thanks'])->name('form.thanks');
Route::get('/f/{slug}/edit/{token}', [PublicFormController::class, 'edit'])->name('form.edit');
Route::put('/f/{slug}/edit/{token}', [PublicFormController::class, 'update'])->middleware('throttle:10,1')->name('form.update');
