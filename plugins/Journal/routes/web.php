<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use LifeWheel\Plugins\Journal\Events\JournalEntryCreated;
use LifeWheel\Plugins\Journal\JournalAreas;

require_once dirname(__DIR__).'/src/JournalAreas.php';
require_once dirname(__DIR__).'/src/Events/JournalEntryCreated.php';

Route::middleware(['auth', 'verified', 'twofactor', 'feature:journal.use'])
    ->prefix('app/journal')
    ->name('plugins.journal.')
    ->group(function (): void {
        Route::get('/', function (Request $request) {
            $search = trim((string) $request->query('search'));
            $canSearch = app(\App\Services\EntitlementService::class)->userHasFeature($request->user(), 'journal.search');

            abort_if($search !== '' && ! $canSearch, 403);

            $entries = DB::table('journal_entries')
                ->where('user_id', $request->user()->id)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('body', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('entry_date')
                ->orderByDesc('created_at')
                ->paginate(12)
                ->withQueryString();

            return View::file(dirname(__DIR__).'/resources/views/index.blade.php', [
                'entries' => $entries,
                'areas' => JournalAreas::all(),
                'search' => $search,
                'canSearch' => $canSearch,
            ]);
        })->name('index');

        Route::post('/entries', function (Request $request) {
            $attributes = journalValidatedAttributes($request);

            $entryId = DB::table('journal_entries')->insertGetId([
                'user_id' => $request->user()->id,
                'title' => $attributes['title'] ?? null,
                'body' => $attributes['body'],
                'areas' => json_encode($attributes['areas'] ?? []),
                'mood' => $attributes['mood'] ?? null,
                'energy' => $attributes['energy'] ?? null,
                'entry_date' => $attributes['entry_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            event(new JournalEntryCreated($request->user(), $entryId));

            return redirect()->route('plugins.journal.show', $entryId)->with('status', 'journal-entry-created');
        })->name('entries.store');

        Route::get('/entries/{entryId}', function (Request $request, int $entryId) {
            return View::file(dirname(__DIR__).'/resources/views/show.blade.php', [
                'entry' => journalEntryForUser($request->user()->id, $entryId),
                'areas' => JournalAreas::all(),
            ]);
        })->name('show');

        Route::put('/entries/{entryId}', function (Request $request, int $entryId) {
            journalEntryForUser($request->user()->id, $entryId);
            $attributes = journalValidatedAttributes($request);

            DB::table('journal_entries')
                ->where('id', $entryId)
                ->where('user_id', $request->user()->id)
                ->update([
                    'title' => $attributes['title'] ?? null,
                    'body' => $attributes['body'],
                    'areas' => json_encode($attributes['areas'] ?? []),
                    'mood' => $attributes['mood'] ?? null,
                    'energy' => $attributes['energy'] ?? null,
                    'entry_date' => $attributes['entry_date'],
                    'updated_at' => now(),
                ]);

            return redirect()->route('plugins.journal.show', $entryId)->with('status', 'journal-entry-updated');
        })->name('entries.update');

        Route::delete('/entries/{entryId}', function (Request $request, int $entryId) {
            journalEntryForUser($request->user()->id, $entryId);

            DB::table('journal_entries')
                ->where('id', $entryId)
                ->where('user_id', $request->user()->id)
                ->delete();

            return redirect()->route('plugins.journal.index')->with('status', 'journal-entry-deleted');
        })->name('entries.destroy');
    });

if (! function_exists('journalValidatedAttributes')) {
    function journalValidatedAttributes(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
            'areas' => ['array'],
            'areas.*' => ['string', 'in:'.implode(',', array_keys(JournalAreas::all()))],
            'mood' => ['nullable', 'integer', 'min:1', 'max:10'],
            'energy' => ['nullable', 'integer', 'min:1', 'max:10'],
            'entry_date' => ['required', 'date'],
        ]);
    }
}

if (! function_exists('journalEntryForUser')) {
    function journalEntryForUser(int $userId, int $entryId): object
    {
        $entry = DB::table('journal_entries')
            ->where('id', $entryId)
            ->where('user_id', $userId)
            ->first();

        abort_unless($entry, 404);

        $entry->areas = json_decode((string) $entry->areas, true) ?: [];

        return $entry;
    }
}
