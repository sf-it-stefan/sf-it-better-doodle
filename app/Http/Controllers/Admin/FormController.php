<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\FieldType;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FormController extends Controller
{
    public function index(Request $request): View
    {
        $query = Form::withCount('entries')->latest();

        if ($search = $request->get('search')) {
            $query->where('title', 'ilike', "%{$search}%");
        }

        return view('admin.forms.index', [
            'forms' => $query->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.forms.form', [
            'form' => null,
            'fieldTypes' => FieldType::cases(),
            'existingFields' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:forms,slug',
            'description' => 'nullable|string',
            'language' => 'required|string|in:en,de',
            'active_until' => 'nullable|date',
            'timezone' => 'nullable|string',
            'allow_edit' => 'boolean',
            'is_active' => 'boolean',
            'header_image' => 'nullable|image|max:5120',
            'fields' => 'required|array|min:1',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.description' => 'nullable|string',
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $form = new Form();
            $form->title = $validated['title'];
            $form->slug = $validated['slug'] ?: Form::generateUniqueSlug($validated['title']);
            $form->description = $validated['description'] ?? null;
            $form->language = $validated['language'];
            $form->allow_edit = $request->boolean('allow_edit');
            $form->is_active = $request->boolean('is_active', true);

            if (!empty($validated['active_until'])) {
                $form->active_until = $this->convertToUtc($validated['active_until'], $validated['timezone'] ?? 'UTC');
            }

            // Save first so UUID is generated
            $form->save();

            if ($request->hasFile('header_image')) {
                $form->header_image = $this->storeHeaderImage($request->file('header_image'), $form);
                $form->save();
            }

            $this->syncFields($form, $validated['fields']);

            return redirect()->route('admin.forms.show', $form)
                ->with('success', 'Form created successfully.');
        });
    }

    public function show(Form $form): View
    {
        $form->load('fields');
        $form->loadCount('entries');

        return view('admin.forms.show', compact('form'));
    }

    public function edit(Form $form): View
    {
        $form->load('fields');

        $existingFields = $form->fields->map(function ($f) {
            $isDateSlots = $f->type === FieldType::DateSlots;
            $multiSelect = $isDateSlots && is_array($f->options) && isset($f->options[0]['multi_select'])
                ? $f->options[0]['multi_select']
                : false;

            return [
                'id' => $f->id,
                'type' => $f->type->value,
                'label' => $f->label,
                'description' => $f->description,
                'options' => $f->options,
                'required' => $f->required,
                '_key' => $f->id,
                '_expanded' => false,
                '_multiSelect' => $multiSelect,
            ];
        })->values()->toArray();

        return view('admin.forms.form', [
            'form' => $form,
            'fieldTypes' => FieldType::cases(),
            'existingFields' => $existingFields,
        ]);
    }

    public function update(Request $request, Form $form): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:forms,slug,' . $form->id,
            'description' => 'nullable|string',
            'language' => 'required|string|in:en,de',
            'active_until' => 'nullable|date',
            'timezone' => 'nullable|string',
            'allow_edit' => 'boolean',
            'is_active' => 'boolean',
            'header_image' => 'nullable|image|max:5120',
            'remove_header_image' => 'boolean',
            'fields' => 'required|array|min:1',
            'fields.*.id' => 'nullable|uuid',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.description' => 'nullable|string',
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable',
        ]);

        return DB::transaction(function () use ($validated, $request, $form) {
            $form->title = $validated['title'];
            if (!empty($validated['slug'])) {
                $form->slug = $validated['slug'];
            }
            $form->description = $validated['description'] ?? null;
            $form->language = $validated['language'];
            $form->allow_edit = $request->boolean('allow_edit');
            $form->is_active = $request->boolean('is_active', true);

            if (!empty($validated['active_until'])) {
                $form->active_until = $this->convertToUtc($validated['active_until'], $validated['timezone'] ?? 'UTC');
            } else {
                $form->active_until = null;
            }

            if ($request->boolean('remove_header_image') && $form->header_image) {
                Storage::disk('public')->delete('uploads/headers/' . $form->header_image);
                $form->header_image = null;
            }

            if ($request->hasFile('header_image')) {
                if ($form->header_image) {
                    Storage::disk('public')->delete('uploads/headers/' . $form->header_image);
                }
                $form->header_image = $this->storeHeaderImage($request->file('header_image'), $form);
            }

            $form->save();
            $this->syncFields($form, $validated['fields']);

            return redirect()->route('admin.forms.show', $form)
                ->with('success', 'Form updated successfully.');
        });
    }

    public function destroy(Form $form): RedirectResponse
    {
        $form->delete();

        return redirect()->route('admin.forms.index')
            ->with('success', 'Form and all its entries have been deleted.');
    }

    private function syncFields(Form $form, array $fields): void
    {
        $existingIds = $form->fields()->pluck('id')->toArray();
        $keepIds = [];

        foreach ($fields as $index => $fieldData) {
            $options = $fieldData['options'] ?? null;
            if (is_string($options)) {
                $options = json_decode($options, true);
            }

            $attributes = [
                'type' => $fieldData['type'],
                'label' => $fieldData['label'],
                'description' => $fieldData['description'] ?? null,
                'options' => $options,
                'required' => filter_var($fieldData['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'sort_order' => $index,
            ];

            if (!empty($fieldData['id']) && in_array($fieldData['id'], $existingIds)) {
                $form->fields()->where('id', $fieldData['id'])->update($attributes);
                $keepIds[] = $fieldData['id'];
            } else {
                $field = $form->fields()->create($attributes);
                $keepIds[] = $field->id;
            }
        }

        // Delete removed fields
        $form->fields()->whereNotIn('id', $keepIds)->delete();
    }

    private function storeHeaderImage($file, Form $form): string
    {
        $filename = $form->id . '.' . $file->getClientOriginalExtension();
        $file->storeAs('uploads/headers', $filename, 'public');
        return $filename;
    }

    private function convertToUtc(string $datetime, string $timezone): string
    {
        try {
            $tz = new \DateTimeZone($timezone);
            $dt = new \DateTime($datetime, $tz);
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return $datetime;
        }
    }
}
