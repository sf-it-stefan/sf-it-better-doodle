<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormEntry;
use App\Models\FormField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Form $form;
    private FormField $field;

    private string $monA = '2026-08-24_15:00_16:00';
    private string $wedA = '2026-08-26_13:00_14:00';
    private string $friA = '2026-08-28_13:00_14:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->form = Form::create([
            'title' => 'Infra Abstimmung',
            'slug' => 'infra-abstimmung',
            'is_active' => true,
        ]);

        $this->field = FormField::create([
            'form_id' => $this->form->id,
            'type' => 'date_slots',
            'label' => 'Termin',
            'required' => true,
            'sort_order' => 0,
            'options' => [
                'multi_select' => true,
                'slots' => [
                    ['date' => '2026-08-24', 'start_time' => '15:00', 'end_time' => '16:00'],
                    ['date' => '2026-08-26', 'start_time' => '13:00', 'end_time' => '14:00'],
                    ['date' => '2026-08-28', 'start_time' => '13:00', 'end_time' => '14:00'],
                ],
            ],
        ]);
    }

    private function answer(array $states): FormEntry
    {
        return FormEntry::create([
            'form_id' => $this->form->id,
            'data' => [$this->field->id => $states],
            'ip_address' => '10.0.0.1',
        ]);
    }

    public function test_submission_stores_a_state_for_every_slot(): void
    {
        $this->post(route('form.submit', 'infra-abstimmung'), [
            'field_' . $this->field->id => [
                $this->monA => 'yes',
                $this->wedA => 'maybe',
            ],
        ])->assertRedirect(route('form.thanks', 'infra-abstimmung'));

        $data = FormEntry::first()->data[$this->field->id];

        $this->assertSame('yes', $data[$this->monA]);
        $this->assertSame('maybe', $data[$this->wedA]);
        // Unanswered slots are recorded as an explicit "no".
        $this->assertSame('no', $data[$this->friA]);
    }

    public function test_unknown_slot_ids_are_discarded(): void
    {
        $this->post(route('form.submit', 'infra-abstimmung'), [
            'field_' . $this->field->id => [
                $this->monA => 'yes',
                '2099-01-01_09:00_10:00' => 'yes',
            ],
        ])->assertRedirect();

        $this->assertArrayNotHasKey('2099-01-01_09:00_10:00', FormEntry::first()->data[$this->field->id]);
    }

    public function test_required_field_rejects_an_all_no_answer(): void
    {
        $this->post(route('form.submit', 'infra-abstimmung'), [
            'field_' . $this->field->id => [
                $this->monA => 'no',
                $this->wedA => 'no',
                $this->friA => 'no',
            ],
        ])->assertSessionHasErrors('field_' . $this->field->id);

        $this->assertSame(0, FormEntry::count());
    }

    public function test_single_select_keeps_only_one_positive_answer(): void
    {
        $this->field->update([
            'options' => ['multi_select' => false, 'slots' => $this->field->dateSlots()],
        ]);

        $this->post(route('form.submit', 'infra-abstimmung'), [
            'field_' . $this->field->id => [
                $this->monA => 'yes',
                $this->wedA => 'yes',
            ],
        ])->assertRedirect();

        $data = FormEntry::first()->data[$this->field->id];

        $this->assertSame(1, count(array_filter($data, fn ($s) => $s !== 'no')));
    }

    public function test_heatmap_counts_states_and_picks_the_best_slot(): void
    {
        $this->answer([$this->monA => 'yes', $this->wedA => 'yes', $this->friA => 'no']);
        $this->answer([$this->monA => 'no', $this->wedA => 'yes', $this->friA => 'maybe']);
        $this->answer([$this->monA => 'no', $this->wedA => 'yes', $this->friA => 'no']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.availability', $this->form));

        $response->assertOk();
        $response->assertSee('Best slot');
        // Wednesday has 3 yes vs Monday's 1 and Friday's 0.
        $response->assertSee('Wed, 26 Aug 2026 · 13:00–14:00', false);

        $best = $response->viewData('best');
        $this->assertSame($this->wedA, $best['id']);
        $this->assertSame(3, $best['yes']);

        $slots = collect($response->viewData('slots'))->keyBy('id');
        $this->assertSame(1, $slots[$this->friA]['maybe']);
        $this->assertSame(2, $slots[$this->friA]['no']);
    }

    public function test_best_slot_breaks_ties_by_fewest_no(): void
    {
        // Both Monday and Wednesday get one yes, but Wednesday also has a maybe.
        $this->answer([$this->monA => 'yes', $this->wedA => 'no', $this->friA => 'no']);
        $this->answer([$this->monA => 'no', $this->wedA => 'yes', $this->friA => 'no']);
        $this->answer([$this->monA => 'no', $this->wedA => 'maybe', $this->friA => 'no']);

        $best = $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.availability', $this->form))
            ->viewData('best');

        $this->assertSame($this->wedA, $best['id']);
    }

    public function test_confirming_a_slot_shows_it_on_the_public_form(): void
    {
        $this->answer([$this->monA => 'yes']);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.forms.availability.confirm', $this->form), ['slot_id' => $this->wedA])
            ->assertRedirect(route('admin.forms.availability', $this->form));

        $this->assertSame($this->wedA, $this->form->fresh()->settings['confirmed_slot']['slot_id']);

        $this->get(route('form.show', 'infra-abstimmung'))
            ->assertOk()
            ->assertSee('Confirmed date')
            ->assertSee('Wed, 26 Aug 2026 · 13:00–14:00', false);
    }

    public function test_confirming_an_unknown_slot_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.forms.availability.confirm', $this->form), ['slot_id' => 'nope'])
            ->assertSessionHasErrors('slot_id');

        $this->assertArrayNotHasKey('confirmed_slot', $this->form->fresh()->settings ?? []);
    }

    public function test_confirmation_can_be_cleared(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.forms.availability.confirm', $this->form), ['slot_id' => $this->monA]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.forms.availability.confirm', $this->form), ['slot_id' => '']);

        $this->assertArrayNotHasKey('confirmed_slot', $this->form->fresh()->settings ?? []);
    }

    public function test_availability_requires_auth_and_a_slot_field(): void
    {
        $this->get(route('admin.forms.availability', $this->form))
            ->assertRedirect(route('login'));

        $plain = Form::create(['title' => 'Plain', 'slug' => 'plain', 'is_active' => true]);
        FormField::create([
            'form_id' => $plain->id,
            'type' => 'text',
            'label' => 'Name',
            'required' => false,
            'sort_order' => 0,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.forms.availability', $plain))
            ->assertNotFound();
    }

    public function test_dense_forms_render_the_week_grid(): void
    {
        // 3 days x 4 times — the setUp field only has 3 slots, so widen it.
        $slots = [];
        foreach (['2026-08-24', '2026-08-26', '2026-08-28'] as $date) {
            foreach ([['13:00', '14:00'], ['14:00', '15:00'], ['15:00', '16:00'], ['16:00', '17:00']] as [$from, $to]) {
                $slots[] = ['date' => $date, 'start_time' => $from, 'end_time' => $to];
            }
        }
        $this->field->update(['options' => ['multi_select' => true, 'slots' => $slots]]);

        $response = $this->get(route('form.show', 'infra-abstimmung'));

        $response->assertOk();
        $response->assertSee('slot-grid', false);
        $response->assertSee('Drag to mark several at once');
        // Without min-w-0 the fieldset's default min-width: min-content stops the
        // scroll container from shrinking and the card clips the extra columns.
        $response->assertSee('<fieldset class="min-w-0">', false);
        $response->assertSee('slot-rowhead', false);
        // 4 time ranges become 4 row labels (+1 corner cell), not 12 repeated ones.
        $this->assertSame(5, substr_count($response->getContent(), 'slot-rowhead'));
    }

    public function test_grid_flips_orientation_when_days_outnumber_times(): void
    {
        // 10 days x 4 times: days as columns would need 10 columns and scroll.
        $slots = [];
        foreach (range(3, 12) as $day) {
            foreach ([['09:00', '10:00'], ['10:00', '11:00'], ['11:00', '12:00'], ['12:00', '13:00']] as [$from, $to]) {
                $slots[] = ['date' => sprintf('2026-08-%02d', $day), 'start_time' => $from, 'end_time' => $to];
            }
        }
        $this->field->update(['options' => ['multi_select' => true, 'slots' => $slots]]);

        $content = $this->get(route('form.show', 'infra-abstimmung'))->assertOk()->getContent();

        // 4 time columns, not 10 day columns.
        $this->assertStringContainsString('--slot-cols: 4', $content);
        $this->assertSame(40, substr_count($content, "startPaint('"));
    }

    public function test_grid_keeps_days_as_columns_when_they_are_the_smaller_axis(): void
    {
        $slots = [];
        foreach (['2026-08-24', '2026-08-26', '2026-08-28'] as $date) {
            foreach ([['13:00', '14:00'], ['14:00', '15:00'], ['15:00', '16:00'], ['16:00', '17:00']] as [$from, $to]) {
                $slots[] = ['date' => $date, 'start_time' => $from, 'end_time' => $to];
            }
        }
        $this->field->update(['options' => ['multi_select' => true, 'slots' => $slots]]);

        $content = $this->get(route('form.show', 'infra-abstimmung'))->assertOk()->getContent();

        $this->assertStringContainsString('--slot-cols: 3', $content);
    }

    public function test_sparse_forms_keep_the_stacked_list(): void
    {
        $this->field->update([
            'options' => [
                'multi_select' => true,
                'slots' => [
                    ['date' => '2026-08-24', 'start_time' => '15:00', 'end_time' => '16:00'],
                    ['date' => '2026-08-26', 'start_time' => '13:00', 'end_time' => '14:00'],
                ],
            ],
        ]);

        $response = $this->get(route('form.show', 'infra-abstimmung'));

        $response->assertOk();
        $response->assertDontSee('slot-grid', false);
        $response->assertSee('Monday, August 24, 2026');
    }

    public function test_grid_offers_a_cell_only_where_a_slot_exists(): void
    {
        $this->field->update([
            'options' => [
                'multi_select' => true,
                'slots' => [
                    ['date' => '2026-08-24', 'start_time' => '13:00', 'end_time' => '14:00'],
                    ['date' => '2026-08-26', 'start_time' => '13:00', 'end_time' => '14:00'],
                    ['date' => '2026-08-26', 'start_time' => '14:00', 'end_time' => '15:00'],
                    ['date' => '2026-08-28', 'start_time' => '14:00', 'end_time' => '15:00'],
                ],
            ],
        ]);

        $content = $this->get(route('form.show', 'infra-abstimmung'))->assertOk()->getContent();

        // 3 days x 2 rows = 6 cells, but only 4 slots exist.
        $this->assertSame(4, substr_count($content, "startPaint('"));
        $this->assertSame(2, substr_count($content, 'border-dashed'));
    }

    public function test_slot_state_labels_follow_the_form_language(): void
    {
        $this->get(route('form.show', 'infra-abstimmung'))
            ->assertOk()
            ->assertSee('Available')
            ->assertSee('If need be');

        $this->form->update(['language' => 'de']);

        $german = $this->get(route('form.show', 'infra-abstimmung'));

        $german->assertOk()
            ->assertSee('Passt')
            ->assertSee('Zur Not')
            ->assertDontSee('If need be');
    }

    public function test_confirmed_banner_follows_the_form_language(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.forms.availability.confirm', $this->form), ['slot_id' => $this->monA]);

        $this->form->update(['language' => 'de']);

        $this->get(route('form.show', 'infra-abstimmung'))
            ->assertOk()
            ->assertSee('Bestätigter Termin')
            ->assertDontSee('Confirmed date');
    }

    public function test_legacy_answer_and_option_shapes_are_still_readable(): void
    {
        $legacy = FormField::create([
            'form_id' => $this->form->id,
            'type' => 'date_slots',
            'label' => 'Legacy',
            'required' => false,
            'sort_order' => 1,
            'options' => [
                ['date' => '2026-09-01', 'start_time' => '10:00', 'end_time' => '11:00', 'multi_select' => true],
            ],
        ]);

        $this->assertTrue($legacy->dateSlotsMultiSelect());
        $this->assertCount(1, $legacy->dateSlots());

        // Old entries stored a bare list of chosen ids.
        $answers = $legacy->dateSlotAnswers(['2026-09-01_10:00_11:00']);

        $this->assertCount(1, $answers);
        $this->assertSame('yes', $answers[0]['state']);
        $this->assertSame('Tue, 1 Sep 2026 · 10:00–11:00', $answers[0]['label']);
    }
}
