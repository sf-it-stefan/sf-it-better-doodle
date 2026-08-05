<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FieldType;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormAvailabilityController extends Controller
{
    public function show(Form $form): View
    {
        $form->load('fields');

        $field = $form->fields->firstWhere('type', FieldType::DateSlots);

        if (!$field) {
            abort(404);
        }

        $entries = $form->entries()->orderBy('created_at')->get();
        $tally = $this->tally($field, $entries);

        return view('admin.availability.show', [
            'form' => $form,
            'field' => $field,
            'entries' => $entries,
            'slots' => $tally,
            'best' => $this->best($tally),
            'confirmed' => $form->settings['confirmed_slot'] ?? null,
        ]);
    }

    public function confirm(Form $form, Request $request): RedirectResponse
    {
        $form->load('fields');

        $field = $form->fields->firstWhere('type', FieldType::DateSlots);

        if (!$field) {
            abort(404);
        }

        $validated = $request->validate([
            'slot_id' => 'nullable|string',
        ]);

        $slotId = $validated['slot_id'] ?? null;
        $settings = $form->settings ?? [];

        if ($slotId === null || $slotId === '') {
            unset($settings['confirmed_slot']);
            $form->settings = $settings;
            $form->save();

            return redirect()
                ->route('admin.forms.availability', $form)
                ->with('success', 'Confirmed slot cleared.');
        }

        $known = array_map(
            fn ($slot) => FormField::dateSlotId($slot),
            $field->dateSlots()
        );

        if (!in_array($slotId, $known, true)) {
            return redirect()
                ->route('admin.forms.availability', $form)
                ->withErrors(['slot_id' => 'That slot is not part of this form.']);
        }

        $settings['confirmed_slot'] = [
            'field_id' => $field->id,
            'slot_id' => $slotId,
            'label' => $field->dateSlotLabel($slotId),
        ];

        $form->settings = $settings;
        $form->save();

        return redirect()
            ->route('admin.forms.availability', $form)
            ->with('success', 'Slot confirmed.');
    }

    /**
     * Count yes/maybe/no per slot, keeping the respondent breakdown for drill-down.
     *
     * @return array<int, array<string, mixed>>
     */
    private function tally(FormField $field, $entries): array
    {
        $rows = [];

        foreach ($field->dateSlots() as $slot) {
            $id = FormField::dateSlotId($slot);
            $rows[$id] = [
                'id' => $id,
                'date' => $slot['date'] ?? '',
                'start_time' => $slot['start_time'] ?? '',
                'end_time' => $slot['end_time'] ?? '',
                'label' => $field->dateSlotLabel($id),
                'yes' => 0,
                'maybe' => 0,
                'no' => 0,
                'respondents' => [],
            ];
        }

        foreach ($entries as $index => $entry) {
            $states = FormField::dateSlotStates($entry->data[$field->id] ?? null);

            foreach ($rows as $id => &$row) {
                $state = $states[$id] ?? 'no';
                $row[$state]++;
                $row['respondents'][] = [
                    'entry' => $entry,
                    'number' => $index + 1,
                    'state' => $state,
                ];
            }
            unset($row);
        }

        return array_values($rows);
    }

    /**
     * Most "yes", then fewest "no", then earliest — so a clear winner surfaces
     * without the admin counting columns.
     */
    private function best(array $slots): ?array
    {
        $ranked = collect($slots)
            ->filter(fn ($s) => $s['yes'] + $s['maybe'] > 0)
            ->sortBy([
                fn ($a, $b) => $b['yes'] <=> $a['yes'],
                fn ($a, $b) => $a['no'] <=> $b['no'],
                fn ($a, $b) => $b['maybe'] <=> $a['maybe'],
                fn ($a, $b) => ($a['date'] . $a['start_time']) <=> ($b['date'] . $b['start_time']),
            ]);

        return $ranked->first();
    }
}
