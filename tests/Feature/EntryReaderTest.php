<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormEntry;
use App\Models\FormField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryReaderTest extends TestCase
{
    use RefreshDatabase;

    private Form $form;
    private FormField $slotField;
    private FormField $notesField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form = Form::create([
            'title' => 'Infra Abstimmung',
            'slug' => 'infra-abstimmung',
            'is_active' => true,
        ]);

        $this->slotField = FormField::create([
            'form_id' => $this->form->id,
            'type' => 'date_slots',
            'label' => 'Termin',
            'required' => true,
            'sort_order' => 0,
            'options' => [
                ['date' => '2026-08-24', 'start_time' => '15:00', 'end_time' => '16:00', 'multi_select' => true],
                ['date' => '2026-08-26', 'start_time' => '13:00', 'end_time' => '14:00', 'multi_select' => true],
            ],
        ]);

        $this->notesField = FormField::create([
            'form_id' => $this->form->id,
            'type' => 'textarea',
            'label' => 'Notes',
            'required' => false,
            'sort_order' => 1,
        ]);
    }

    private function makeEntry(array $data, string $at): FormEntry
    {
        $entry = FormEntry::create([
            'form_id' => $this->form->id,
            'data' => $data,
            'ip_address' => '10.0.0.1',
        ]);
        $entry->forceFill(['created_at' => $at])->save();

        return $entry->refresh();
    }

    public function test_reader_renders_full_value_and_resolves_slot_labels(): void
    {
        $longNote = str_repeat('Sehr langer Text. ', 40);

        $entry = $this->makeEntry([
            $this->slotField->id => ['2026-08-24_15:00_16:00'],
            $this->notesField->id => $longNote,
        ], '2026-08-01 10:00:00');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.entries.show', [$this->form, $entry]));

        $response->assertOk();
        // Raw slot id must never reach the admin.
        $response->assertDontSee('2026-08-24_15:00_16:00');
        $response->assertSee('Mon, 24 Aug 2026 · 15:00–16:00', false);
        // Long text is shown in full, not truncated.
        $response->assertSee(trim($longNote));
    }

    public function test_unanswered_fields_are_shown_explicitly(): void
    {
        $entry = $this->makeEntry([
            $this->slotField->id => ['2026-08-26_13:00_14:00'],
        ], '2026-08-01 10:00:00');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.entries.show', [$this->form, $entry]))
            ->assertOk()
            ->assertSee('Not answered');
    }

    public function test_prev_and_next_walk_the_whole_set_in_sort_order(): void
    {
        $oldest = $this->makeEntry([$this->notesField->id => 'oldest'], '2026-08-01 10:00:00');
        $middle = $this->makeEntry([$this->notesField->id => 'middle'], '2026-08-02 10:00:00');
        $newest = $this->makeEntry([$this->notesField->id => 'newest'], '2026-08-03 10:00:00');

        $user = User::factory()->create();

        // Default sort is newest-first: next goes to older entries.
        $response = $this->actingAs($user)
            ->get(route('admin.forms.entries.show', [$this->form, $middle]));

        $response->assertOk();
        $response->assertSee(route('admin.forms.entries.show', [$this->form, $newest]), false);
        $response->assertSee(route('admin.forms.entries.show', [$this->form, $oldest]), false);
        $response->assertSee('Response 2 of 3');

        // Ends of the list have no neighbour in that direction.
        $this->actingAs($user)
            ->get(route('admin.forms.entries.show', [$this->form, $newest]))
            ->assertOk()
            ->assertSee('Response 1 of 3');

        $this->actingAs($user)
            ->get(route('admin.forms.entries.show', [$this->form, $oldest]))
            ->assertOk()
            ->assertSee('Response 3 of 3');
    }

    public function test_position_follows_the_oldest_first_sort(): void
    {
        $oldest = $this->makeEntry([$this->notesField->id => 'oldest'], '2026-08-01 10:00:00');
        $this->makeEntry([$this->notesField->id => 'middle'], '2026-08-02 10:00:00');
        $this->makeEntry([$this->notesField->id => 'newest'], '2026-08-03 10:00:00');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.entries.show', [$this->form, $oldest]) . '?sort=oldest')
            ->assertOk()
            ->assertSee('Response 1 of 3');
    }

    public function test_entry_from_another_form_is_not_reachable(): void
    {
        $other = Form::create(['title' => 'Other', 'slug' => 'other', 'is_active' => true]);
        $entry = FormEntry::create([
            'form_id' => $other->id,
            'data' => [],
            'ip_address' => '10.0.0.2',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.entries.show', [$this->form, $entry]))
            ->assertNotFound();
    }

    public function test_reader_requires_auth(): void
    {
        $entry = $this->makeEntry([$this->notesField->id => 'x'], '2026-08-01 10:00:00');

        $this->get(route('admin.forms.entries.show', [$this->form, $entry]))
            ->assertRedirect(route('login'));
    }

    public function test_index_cards_link_to_the_reader(): void
    {
        $entry = $this->makeEntry([$this->notesField->id => 'hello'], '2026-08-01 10:00:00');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.entries', $this->form))
            ->assertOk()
            ->assertSee(route('admin.forms.entries.show', [$this->form, $entry]), false);
    }

    public function test_csv_export_resolves_slot_labels(): void
    {
        $this->makeEntry([
            $this->slotField->id => ['2026-08-24_15:00_16:00'],
        ], '2026-08-01 10:00:00');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.entries.export', $this->form));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringNotContainsString('2026-08-24_15:00_16:00', $csv);
        $this->assertStringContainsString('Mon, 24 Aug 2026', $csv);
    }
}
