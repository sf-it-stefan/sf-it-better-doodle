<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormEntryController extends Controller
{
    public function index(Form $form): View
    {
        $form->load('fields');
        $entries = $form->entries()->paginate(50);

        return view('admin.entries.index', compact('form', 'entries'));
    }

    public function destroy(Form $form, FormEntry $entry): RedirectResponse
    {
        $entry->delete();

        return redirect()->route('admin.forms.entries', $form)
            ->with('success', 'Entry deleted.');
    }

    public function export(Form $form): StreamedResponse
    {
        $form->load('fields');
        $entries = $form->entries()->get();

        $filename = Str::slug($form->title) . '-entries-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($form, $entries) {
            $handle = fopen('php://output', 'w');

            // Header row
            $headers = ['Submitted At'];
            foreach ($form->fields as $field) {
                $headers[] = $field->label;
            }
            if ($form->allow_edit) {
                $headers[] = 'Edit Token';
            }
            fputcsv($handle, $headers);

            // Data rows
            foreach ($entries as $entry) {
                $row = [$entry->created_at->toIso8601String()];
                foreach ($form->fields as $field) {
                    $value = $entry->data[$field->id] ?? '';
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    $row[] = $value;
                }
                if ($form->allow_edit) {
                    $row[] = $entry->edit_token ?? '';
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function download(Form $form, FormEntry $entry, string $fieldId)
    {
        $fileData = $entry->data[$fieldId] ?? null;

        if (!$fileData || !is_array($fileData) || !isset($fileData['path'])) {
            abort(404);
        }

        if (!Storage::exists($fileData['path'])) {
            abort(404);
        }

        return Storage::download($fileData['path'], $fileData['original_name'] ?? 'download');
    }
}
