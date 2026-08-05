<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormEntry;
use App\Models\FormField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateSlotMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2024_01_01_000006_normalize_date_slot_options_and_answers.php');
    }

    private function legacyForm(): array
    {
        $form = Form::create(['title' => 'Legacy', 'slug' => 'legacy', 'is_active' => true]);

        $field = FormField::create([
            'form_id' => $form->id,
            'type' => 'date_slots',
            'label' => 'Termin',
            'required' => true,
            'sort_order' => 0,
            'options' => [
                ['date' => '2026-08-24', 'start_time' => '15:00', 'end_time' => '16:00', 'multi_select' => true],
                ['date' => '2026-08-26', 'start_time' => '13:00', 'end_time' => '14:00', 'multi_select' => true],
            ],
        ]);

        return [$form, $field];
    }

    public function test_it_converts_options_and_answers(): void
    {
        [$form, $field] = $this->legacyForm();

        $entry = FormEntry::create([
            'form_id' => $form->id,
            'data' => [$field->id => ['2026-08-24_15:00_16:00']],
            'ip_address' => '10.0.0.1',
        ]);

        $this->migration()->up();

        $options = $field->fresh()->options;
        $this->assertTrue($options['multi_select']);
        $this->assertCount(2, $options['slots']);
        $this->assertArrayNotHasKey('multi_select', $options['slots'][0]);

        $data = $entry->fresh()->data[$field->id];
        $this->assertSame('yes', $data['2026-08-24_15:00_16:00']);
        // The slot the respondent left out becomes an explicit "no".
        $this->assertSame('no', $data['2026-08-26_13:00_14:00']);
    }

    public function test_it_keeps_answers_for_slots_the_admin_removed(): void
    {
        [$form, $field] = $this->legacyForm();

        $entry = FormEntry::create([
            'form_id' => $form->id,
            'data' => [$field->id => ['2026-08-24_15:00_16:00', '2026-07-01_09:00_10:00']],
            'ip_address' => '10.0.0.1',
        ]);

        $this->migration()->up();

        $data = $entry->fresh()->data[$field->id];
        $this->assertSame('yes', $data['2026-07-01_09:00_10:00']);
    }

    public function test_it_is_idempotent(): void
    {
        [$form, $field] = $this->legacyForm();

        $entry = FormEntry::create([
            'form_id' => $form->id,
            'data' => [$field->id => ['2026-08-24_15:00_16:00']],
            'ip_address' => '10.0.0.1',
        ]);

        $this->migration()->up();
        $after = $entry->fresh()->data;
        $options = $field->fresh()->options;

        $this->migration()->up();

        $this->assertSame($after, $entry->fresh()->data);
        $this->assertSame($options, $field->fresh()->options);
    }

    public function test_it_leaves_other_field_types_alone(): void
    {
        [$form, $field] = $this->legacyForm();

        $text = FormField::create([
            'form_id' => $form->id,
            'type' => 'text',
            'label' => 'Name',
            'required' => false,
            'sort_order' => 1,
        ]);

        $entry = FormEntry::create([
            'form_id' => $form->id,
            'data' => [$field->id => [], $text->id => 'Stefan'],
            'ip_address' => '10.0.0.1',
        ]);

        $this->migration()->up();

        $this->assertSame('Stefan', $entry->fresh()->data[$text->id]);
    }

    public function test_down_restores_the_legacy_shapes(): void
    {
        [$form, $field] = $this->legacyForm();

        $entry = FormEntry::create([
            'form_id' => $form->id,
            'data' => [$field->id => ['2026-08-24_15:00_16:00']],
            'ip_address' => '10.0.0.1',
        ]);

        $this->migration()->up();
        $this->migration()->down();

        $options = $field->fresh()->options;
        $this->assertTrue(array_is_list($options));
        $this->assertTrue($options[0]['multi_select']);

        $data = $entry->fresh()->data[$field->id];
        $this->assertSame(['2026-08-24_15:00_16:00'], array_values($data));
    }
}
