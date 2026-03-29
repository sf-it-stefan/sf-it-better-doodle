<?php

namespace App\Http\Controllers;

use App\Enums\FieldType;
use App\FormTranslations;
use App\Models\Form;
use App\Models\FormEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $form = Form::where('slug', $slug)->with('fields')->firstOrFail();
        $t = FormTranslations::all($form->language);

        if (!$form->isAcceptingResponses()) {
            return view('public.closed', compact('form', 't'));
        }

        $hasExistingEntry = FormEntry::where('form_id', $form->id)
            ->where('ip_address', $request->ip())
            ->exists();

        return view('public.form', compact('form', 't', 'hasExistingEntry'));
    }

    public function submit(Request $request, string $slug): RedirectResponse|View
    {
        $form = Form::where('slug', $slug)->with('fields')->firstOrFail();
        $t = FormTranslations::all($form->language);

        if (!$form->isAcceptingResponses()) {
            return view('public.closed', compact('form', 't'));
        }

        // Honeypot check
        if ($request->filled('website')) {
            return redirect()->route('form.thanks', ['slug' => $form->slug]);
        }

        // Validate fields
        $rules = [];
        foreach ($form->fields as $field) {
            $key = 'field_' . $field->id;
            $fieldRules = [];

            if ($field->required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            match ($field->type) {
                FieldType::Text, FieldType::SecretText => $fieldRules[] = 'string|max:500',
                FieldType::Textarea => $fieldRules[] = 'string|max:5000',
                FieldType::Select => $fieldRules[] = 'string',
                FieldType::MultiSelect, FieldType::DateSlots => $fieldRules[] = 'array',
                FieldType::Checkbox => $fieldRules[] = 'boolean',
                FieldType::ImageUpload => $fieldRules[] = 'string',
                FieldType::FileUpload => $fieldRules[] = 'file|max:20480',
                default => null,
            };

            $rules[$key] = implode('|', $fieldRules);
        }

        $validated = $request->validate($rules);

        // Build entry data
        $data = [];
        $entry = new FormEntry();
        $entry->form_id = $form->id;
        $entry->ip_address = $request->ip();

        // Save first to get the ID for image storage
        $entry->data = [];
        $entry->save();

        foreach ($form->fields as $field) {
            $key = 'field_' . $field->id;
            $value = $validated[$key] ?? null;

            if ($field->type === FieldType::ImageUpload && $value) {
                $value = $this->storeBase64Image($value, $form->id, $entry->id, $field->id);
            }

            if ($field->type === FieldType::FileUpload && $request->hasFile($key)) {
                $value = $this->storePrivateFile($request->file($key), $form->id, $entry->id, $field->id);
            }

            if ($field->type === FieldType::Checkbox) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            $data[$field->id] = $value;
        }

        $entry->data = $data;
        $entry->save();

        // Flash edit URL if available
        if ($entry->edit_token) {
            session()->flash('edit_url', $entry->getEditUrl());
        }

        return redirect()->route('form.thanks', ['slug' => $form->slug]);
    }

    public function thanks(string $slug): View
    {
        $form = Form::where('slug', $slug)->firstOrFail();
        $t = FormTranslations::all($form->language);

        return view('public.thanks', [
            'form' => $form,
            't' => $t,
            'editUrl' => session('edit_url'),
        ]);
    }

    public function edit(string $slug, string $token): View
    {
        $form = Form::where('slug', $slug)->with('fields')->firstOrFail();
        $entry = FormEntry::where('edit_token', $token)->where('form_id', $form->id)->firstOrFail();
        $t = FormTranslations::all($form->language);

        return view('public.form', [
            'form' => $form,
            'entry' => $entry,
            'isEdit' => true,
            't' => $t,
            'hasExistingEntry' => false,
        ]);
    }

    public function update(Request $request, string $slug, string $token): RedirectResponse|View
    {
        $form = Form::where('slug', $slug)->with('fields')->firstOrFail();
        $entry = FormEntry::where('edit_token', $token)->where('form_id', $form->id)->firstOrFail();

        // Honeypot check
        if ($request->filled('website')) {
            return redirect()->route('form.thanks', ['slug' => $form->slug]);
        }

        // Validate fields
        $rules = [];
        foreach ($form->fields as $field) {
            $key = 'field_' . $field->id;
            $fieldRules = [];

            if ($field->required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            match ($field->type) {
                FieldType::Text, FieldType::SecretText => $fieldRules[] = 'string|max:500',
                FieldType::Textarea => $fieldRules[] = 'string|max:5000',
                FieldType::Select => $fieldRules[] = 'string',
                FieldType::MultiSelect, FieldType::DateSlots => $fieldRules[] = 'array',
                FieldType::Checkbox => $fieldRules[] = 'boolean',
                FieldType::ImageUpload => $fieldRules[] = 'string',
                FieldType::FileUpload => $fieldRules[] = 'file|max:20480',
                default => null,
            };

            $rules[$key] = implode('|', $fieldRules);
        }

        $validated = $request->validate($rules);

        $data = [];
        foreach ($form->fields as $field) {
            $key = 'field_' . $field->id;
            $value = $validated[$key] ?? null;

            if ($field->type === FieldType::ImageUpload && $value && str_starts_with($value, 'data:')) {
                $value = $this->storeBase64Image($value, $form->id, $entry->id, $field->id);
            } elseif ($field->type === FieldType::ImageUpload && !$value) {
                $value = $entry->data[$field->id] ?? null;
            }

            if ($field->type === FieldType::FileUpload && $request->hasFile($key)) {
                $value = $this->storePrivateFile($request->file($key), $form->id, $entry->id, $field->id);
            } elseif ($field->type === FieldType::FileUpload && !$value) {
                $value = $entry->data[$field->id] ?? null;
            }

            if ($field->type === FieldType::Checkbox) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            $data[$field->id] = $value;
        }

        $entry->data = $data;
        $entry->save();

        return redirect()->route('form.thanks', ['slug' => $form->slug])
            ->with('success', 'updated');
    }

    private function storeBase64Image(string $base64, string $formId, string $entryId, string $fieldId): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $data = base64_decode(substr($base64, strpos($base64, ',') + 1));

            $dir = "uploads/entries/{$formId}/{$entryId}";
            $filename = "{$fieldId}.{$extension}";

            Storage::disk('public')->makeDirectory($dir);
            Storage::disk('public')->put("{$dir}/{$filename}", $data);

            return "uploads/entries/{$formId}/{$entryId}/{$filename}";
        }

        return $base64;
    }

    private function storePrivateFile($file, string $formId, string $entryId, string $fieldId): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = "{$fieldId}.{$extension}";
        $dir = "uploads/entries/{$formId}/{$entryId}";

        // Store in the default (private) disk — not publicly accessible
        Storage::makeDirectory($dir);
        Storage::putFileAs($dir, $file, $filename);

        return [
            'path' => "{$dir}/{$filename}",
            'original_name' => $originalName,
            'size' => $file->getSize(),
        ];
    }
}
