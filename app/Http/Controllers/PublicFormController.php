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

        $validated = $request->validate($this->buildValidationRules($form));

        $entry = new FormEntry();
        $entry->form_id = $form->id;
        $entry->ip_address = $request->ip();
        $entry->data = [];
        $entry->save();

        $data = $this->buildEntryData($form, $validated, $request, $entry);
        $entry->data = $data;
        $entry->save();

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

        if ($request->filled('website')) {
            return redirect()->route('form.thanks', ['slug' => $form->slug]);
        }

        $validated = $request->validate($this->buildValidationRules($form));

        $data = $this->buildEntryData($form, $validated, $request, $entry, isEdit: true);
        $entry->data = $data;
        $entry->save();

        return redirect()->route('form.thanks', ['slug' => $form->slug])
            ->with('success', 'updated');
    }

    private function buildValidationRules(Form $form): array
    {
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
                FieldType::Text => $this->addTextValidation($fieldRules, $field),
                FieldType::SecretText => $fieldRules[] = 'string|max:500',
                FieldType::Textarea => $fieldRules[] = 'string|max:5000',
                FieldType::Select => $this->addSelectValidation($fieldRules, $field),
                FieldType::MultiSelect => $this->addMultiSelectValidation($fieldRules, $field),
                FieldType::DateSlots => $fieldRules[] = 'array',
                FieldType::Checkbox => $fieldRules[] = 'boolean',
                FieldType::ImageUpload => $fieldRules[] = 'string|max:15000000',
                FieldType::FileUpload => $fieldRules[] = 'file|max:20480|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp,gif,zip',
                default => null,
            };

            $rules[$key] = implode('|', $fieldRules);
        }
        return $rules;
    }

    private function addTextValidation(array &$rules, $field): void
    {
        $datatype = $field->options['datatype'] ?? 'text';

        if ($datatype === 'number') {
            $rules[] = 'numeric';
            if (isset($field->options['min'])) {
                $rules[] = 'min:' . $field->options['min'];
            }
            if (isset($field->options['max'])) {
                $rules[] = 'max:' . $field->options['max'];
            }
        } else {
            $rules[] = 'string|max:500';
        }
    }

    private function addSelectValidation(array &$rules, $field): void
    {
        $options = $field->options ?? [];
        if (!empty($options)) {
            $rules[] = 'string|in:' . implode(',', $options);
        } else {
            $rules[] = 'string';
        }
    }

    private function addMultiSelectValidation(array &$rules, $field): void
    {
        $options = $field->options ?? [];
        $rules[] = 'array';
        if (!empty($options)) {
            $rules[] = 'in:' . implode(',', $options);
        }
    }

    private function buildEntryData(Form $form, array $validated, Request $request, FormEntry $entry, bool $isEdit = false): array
    {
        $data = [];
        foreach ($form->fields as $field) {
            $key = 'field_' . $field->id;
            $value = $validated[$key] ?? null;

            if ($field->type === FieldType::ImageUpload && $value) {
                if (!$isEdit || str_starts_with($value, 'data:')) {
                    $value = $this->storeBase64Image($value, $form->id, $entry->id, $field->id);
                }
            } elseif ($field->type === FieldType::ImageUpload && $isEdit && !$value) {
                $value = $entry->data[$field->id] ?? null;
            }

            if ($field->type === FieldType::FileUpload && $request->hasFile($key)) {
                $value = $this->storePrivateFile($request->file($key), $form->id, $entry->id, $field->id);
            } elseif ($field->type === FieldType::FileUpload && (!$value || !$request->hasFile($key))) {
                $value = $entry->data[$field->id] ?? null;
            }

            if ($field->type === FieldType::SecretText && $isEdit && ($value === null || $value === '')) {
                $value = $entry->data[$field->id] ?? null;
            }

            if ($field->type === FieldType::Checkbox) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            $data[$field->id] = $value;
        }
        return $data;
    }

    private function storeBase64Image(string $base64, string $formId, string $entryId, string $fieldId): string
    {
        if (!preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $base64)) {
            throw new \InvalidArgumentException('Invalid image data.');
        }

        $decoded = base64_decode(substr($base64, strpos($base64, ',') + 1), strict: true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64 data.');
        }

        // Re-encode through GD to strip any embedded payloads
        $image = @imagecreatefromstring($decoded);
        if ($image === false) {
            throw new \InvalidArgumentException('Data is not a valid image.');
        }

        $dir = "uploads/entries/{$formId}/{$entryId}";
        $filename = "{$fieldId}.jpg"; // Always JPEG — never trust client-supplied extension
        $tmpPath = tempnam(sys_get_temp_dir(), 'img_');

        imagejpeg($image, $tmpPath, 85);
        imagedestroy($image);

        Storage::disk('public')->makeDirectory($dir);
        Storage::disk('public')->put("{$dir}/{$filename}", file_get_contents($tmpPath));
        unlink($tmpPath);

        return "{$dir}/{$filename}";
    }

    private function storePrivateFile($file, string $formId, string $entryId, string $fieldId): array
    {
        $extension = match ($file->getMimeType()) {
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/zip' => 'zip',
            default => 'bin',
        };

        $originalName = preg_replace('/[^a-zA-Z0-9._\- ]/', '_', $file->getClientOriginalName());
        $originalName = ltrim($originalName, '.');

        $filename = "{$fieldId}.{$extension}";
        $dir = "uploads/entries/{$formId}/{$entryId}";

        Storage::makeDirectory($dir);
        Storage::putFileAs($dir, $file, $filename);

        return [
            'path' => "{$dir}/{$filename}",
            'original_name' => $originalName,
            'size' => $file->getSize(),
        ];
    }
}
