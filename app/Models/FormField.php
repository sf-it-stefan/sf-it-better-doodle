<?php

namespace App\Models;

use App\Enums\FieldType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_id',
        'type',
        'label',
        'description',
        'options',
        'required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'options' => 'array',
            'required' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * The slot list for a date_slots field.
     *
     * Canonical shape is {"multi_select": bool, "slots": [...]}. Older forms
     * stored a flat slot array with multi_select duplicated onto every slot;
     * both are read here so unmigrated rows keep working.
     */
    public function dateSlots(): array
    {
        $options = $this->options ?? [];

        if (isset($options['slots']) && is_array($options['slots'])) {
            return array_values(array_filter($options['slots'], 'is_array'));
        }

        return array_values(array_filter($options, 'is_array'));
    }

    public function dateSlotsMultiSelect(): bool
    {
        $options = $this->options ?? [];

        if (array_key_exists('multi_select', $options)) {
            return (bool) $options['multi_select'];
        }

        return (bool) ($options[0]['multi_select'] ?? false);
    }

    /**
     * Build the identifier a date_slots option is submitted under.
     * Must stay in sync with resources/views/public/fields/date_slots.blade.php.
     */
    public static function dateSlotId(array $slot): string
    {
        return ($slot['date'] ?? '')
            . '_' . ($slot['start_time'] ?? 'allday')
            . '_' . ($slot['end_time'] ?? '');
    }

    /**
     * Read a stored answer into [slotId => yes|maybe|no], tolerating the legacy
     * list-of-chosen-ids shape.
     */
    public static function dateSlotStates(mixed $value): array
    {
        if (is_array($value) && !array_is_list($value)) {
            return array_filter($value, fn ($s) => in_array($s, ['yes', 'maybe', 'no'], true));
        }

        $chosen = is_array($value) ? $value : array_filter([$value]);

        return array_fill_keys(array_map('strval', $chosen), 'yes');
    }

    /**
     * The answers worth showing: everything the respondent did not rule out.
     *
     * @return array<int, array{id: string, label: string, state: string}>
     */
    public function dateSlotAnswers(mixed $value): array
    {
        $answers = [];

        foreach (self::dateSlotStates($value) as $id => $state) {
            if ($state === 'no') {
                continue;
            }
            $answers[] = [
                'id' => (string) $id,
                'label' => $this->dateSlotLabel((string) $id),
                'state' => $state,
            ];
        }

        return $answers;
    }

    /**
     * Turn a submitted slot id back into something a human can read.
     * Falls back to the raw id if the slot no longer exists on the field.
     */
    public function dateSlotLabel(string $slotId): string
    {
        foreach ($this->dateSlots() as $slot) {
            if (self::dateSlotId($slot) !== $slotId) {
                continue;
            }

            $date = \Carbon\Carbon::parse($slot['date'])->format('D, j M Y');

            if (!empty($slot['start_time']) && !empty($slot['end_time'])) {
                return $date . ' · ' . $slot['start_time'] . '–' . $slot['end_time'];
            }

            if (!empty($slot['start_time'])) {
                return $date . ' · ' . $slot['start_time'];
            }

            return $date . ' · All day';
        }

        return $slotId;
    }
}
