<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormEntry;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalForms' => Form::count(),
            'activeForms' => Form::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('active_until')->orWhere('active_until', '>', now());
                })
                ->count(),
            'totalEntries' => FormEntry::count(),
            'recentForms' => Form::withCount('entries')->latest()->limit(5)->get(),
        ]);
    }
}
