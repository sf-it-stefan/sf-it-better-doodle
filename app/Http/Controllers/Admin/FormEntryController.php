<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Enums\FieldType;
use App\Models\FormEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormEntryController extends Controller
{
    public function index(Form $form, Request $request): View
    {
        $form->load('fields');

        $view = $request->query('view') === 'table' ? 'table' : 'cards';
        $entries = $this->entriesQuery($form, $request)->paginate(50)->withQueryString();

        return view('admin.entries.index', [
            'form' => $form,
            'entries' => $entries,
            'view' => $view,
            'search' => (string) $request->query('search', ''),
            'sort' => $this->sortDirection($request),
        ]);
    }

    public function show(Form $form, FormEntry $entry, Request $request): View
    {
        if ($entry->form_id !== $form->id) {
            abort(404);
        }

        $form->load('fields');

        $sort = $this->sortDirection($request);
        $context = $request->only('search', 'sort', 'view');

        // Neighbours are resolved against the whole filtered set rather than the
        // current paginator page, so holding j/k walks past the 50-row boundary.
        $newer = $this->neighbour($form, $request, $entry, 'newer');
        $older = $this->neighbour($form, $request, $entry, 'older');

        $prev = $sort === 'oldest' ? $older : $newer;
        $next = $sort === 'oldest' ? $newer : $older;

        $total = $this->baseQuery($form, $request)->count();
        $ahead = $this->baseQuery($form, $request)
            ->where(fn (Builder $q) => $this->compare($q, $entry, $sort === 'oldest' ? 'older' : 'newer'))
            ->count();

        return view('admin.entries.show', [
            'form' => $form,
            'entry' => $entry,
            'siblings' => $this->entriesQuery($form, $request)->limit(200)->get(),
            'prev' => $prev,
            'next' => $next,
            'position' => $ahead + 1,
            'total' => $total,
            'context' => $context,
        ]);
    }

    private function sortDirection(Request $request): string
    {
        return $request->query('sort') === 'oldest' ? 'oldest' : 'newest';
    }

    /**
     * Single source of truth for which entries are in scope, shared by the index
     * paginator, the reader's neighbour lookups and its position counter.
     */
    private function baseQuery(Form $form, Request $request): Builder
    {
        return FormEntry::query()
            ->where('form_id', $form->id)
            ->when($request->query('search'), function (Builder $q, string $search) {
                // Postgres-only; this app has no other supported driver.
                $q->whereRaw('data::text ILIKE ?', ['%' . $search . '%']);
            });
    }

    private function entriesQuery(Form $form, Request $request): Builder
    {
        $direction = $this->sortDirection($request) === 'oldest' ? 'asc' : 'desc';

        return $this->baseQuery($form, $request)
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction);
    }

    private function neighbour(Form $form, Request $request, FormEntry $entry, string $which): ?FormEntry
    {
        $direction = $which === 'newer' ? 'asc' : 'desc';

        return $this->baseQuery($form, $request)
            ->where(fn (Builder $q) => $this->compare($q, $entry, $which))
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction)
            ->first();
    }

    /**
     * Keyset comparison on (created_at, id) so entries sharing a timestamp
     * still order deterministically.
     */
    private function compare(Builder $query, FormEntry $entry, string $which): Builder
    {
        $op = $which === 'newer' ? '>' : '<';

        return $query->where('created_at', $op, $entry->created_at)
            ->orWhere(function (Builder $q) use ($entry, $op) {
                $q->where('created_at', $entry->created_at)
                    ->where('id', $op, $entry->id);
            });
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

                    if ($field->type === FieldType::DateSlots) {
                        $value = collect($field->dateSlotAnswers($value))
                            ->map(fn ($a) => $a['label'] . ($a['state'] === 'maybe' ? ' (if need be)' : ''))
                            ->implode(', ');
                    } elseif ($field->type === FieldType::FileUpload && is_array($value)) {
                        $value = $value['original_name'] ?? '';
                    } elseif (is_array($value)) {
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
        if ($entry->form_id !== $form->id) {
            abort(404);
        }

        $validFieldIds = $form->fields()->pluck('id')->all();
        if (!in_array($fieldId, $validFieldIds, true)) {
            abort(404);
        }

        $fileData = $entry->data[$fieldId] ?? null;

        if (!$fileData || !is_array($fileData) || !isset($fileData['path'])) {
            abort(404);
        }

        $expectedPrefix = 'uploads/entries/' . $form->id . '/' . $entry->id . '/';
        if (!str_starts_with($fileData['path'], $expectedPrefix)) {
            abort(404);
        }

        if (!Storage::exists($fileData['path'])) {
            abort(404);
        }

        return Storage::download(
            $fileData['path'],
            $fileData['original_name'] ?? 'download',
            ['X-Content-Type-Options' => 'nosniff']
        );
    }
}
