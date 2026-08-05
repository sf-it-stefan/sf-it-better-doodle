<?php

use App\Enums\FieldType;
use App\Models\FormEntry;
use App\Models\FormField;
use Illuminate\Database\Migrations\Migration;

/**
 * Moves date_slots onto the shapes the availability feature needs:
 *
 *  options: [{date, start_time, end_time, multi_select}, ...]
 *        -> {"multi_select": bool, "slots": [{date, start_time, end_time}, ...]}
 *
 *  entry data: ["slotId", ...]                (implicitly "yes")
 *           -> {"slotId": "yes", "otherSlotId": "no", ...}
 *
 * Both readers tolerate the legacy shapes, so this is a cleanup rather than a
 * hard cutover — but the heatmap only counts "maybe" on migrated rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fields = FormField::where('type', FieldType::DateSlots->value)->get();

        foreach ($fields as $field) {
            $options = $field->getRawOriginal('options');
            $options = is_string($options) ? json_decode($options, true) : $options;

            if (!is_array($options) || isset($options['slots'])) {
                continue; // already canonical
            }

            $slots = [];
            $multiSelect = false;

            foreach ($options as $slot) {
                if (!is_array($slot)) {
                    continue;
                }
                $multiSelect = $multiSelect || (bool) ($slot['multi_select'] ?? false);
                $slots[] = [
                    'date' => $slot['date'] ?? '',
                    'start_time' => $slot['start_time'] ?? '',
                    'end_time' => $slot['end_time'] ?? '',
                ];
            }

            $field->options = ['multi_select' => $multiSelect, 'slots' => $slots];
            $field->save();
        }

        // Rewrite answers for every entry belonging to a form with slot fields.
        $slotFieldsByForm = $fields->groupBy('form_id');

        foreach ($slotFieldsByForm as $formId => $formFields) {
            FormEntry::where('form_id', $formId)->chunkById(200, function ($entries) use ($formFields) {
                foreach ($entries as $entry) {
                    $data = $entry->data ?? [];
                    $changed = false;

                    foreach ($formFields as $field) {
                        $value = $data[$field->id] ?? null;

                        // Only a list of ids is legacy; an object is already migrated.
                        if (!is_array($value) || array_is_list($value) === false) {
                            continue;
                        }

                        $chosen = array_map('strval', $value);
                        $states = [];

                        foreach ($field->dateSlots() as $slot) {
                            $id = FormField::dateSlotId($slot);
                            $states[$id] = in_array($id, $chosen, true) ? 'yes' : 'no';
                        }

                        // Keep answers for slots the admin has since removed.
                        foreach ($chosen as $id) {
                            $states[$id] ??= 'yes';
                        }

                        $data[$field->id] = $states;
                        $changed = true;
                    }

                    if ($changed) {
                        $entry->data = $data;
                        $entry->save();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $fields = FormField::where('type', FieldType::DateSlots->value)->get();

        foreach ($fields as $field) {
            $options = $field->getRawOriginal('options');
            $options = is_string($options) ? json_decode($options, true) : $options;

            if (!is_array($options) || !isset($options['slots'])) {
                continue;
            }

            $multiSelect = (bool) ($options['multi_select'] ?? false);
            $field->options = array_map(
                fn ($slot) => $slot + ['multi_select' => $multiSelect],
                $options['slots']
            );
            $field->save();
        }

        $slotFieldsByForm = $fields->groupBy('form_id');

        foreach ($slotFieldsByForm as $formId => $formFields) {
            FormEntry::where('form_id', $formId)->chunkById(200, function ($entries) use ($formFields) {
                foreach ($entries as $entry) {
                    $data = $entry->data ?? [];
                    $changed = false;

                    foreach ($formFields as $field) {
                        $value = $data[$field->id] ?? null;

                        if (!is_array($value) || array_is_list($value)) {
                            continue;
                        }

                        // "maybe" collapses into "yes"; the old shape cannot hold it.
                        $data[$field->id] = array_keys(array_filter(
                            $value,
                            fn ($state) => in_array($state, ['yes', 'maybe'], true)
                        ));
                        $changed = true;
                    }

                    if ($changed) {
                        $entry->data = $data;
                        $entry->save();
                    }
                }
            });
        }
    }
};
