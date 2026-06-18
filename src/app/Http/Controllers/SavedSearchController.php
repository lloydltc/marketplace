<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedSearchController extends Controller
{
    /** Request keys that are never part of a saved filter set. */
    private const IGNORED_KEYS = ['_token', '_method', 'name', 'type', 'page'];

    public function index(Request $request): View
    {
        $searches = SavedSearch::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('saved-searches.index', compact('searches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:products,vehicles'],
        ]);

        $params = collect($request->except(self::IGNORED_KEYS))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        SavedSearch::create([
            'user_id'      => $request->user()->id,
            'name'         => $validated['name'],
            'type'         => $validated['type'],
            'query_params' => $params,
        ]);

        return back()->with('status', 'Search saved. View it under “Saved searches”.');
    }

    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 403);

        $savedSearch->delete();

        return back()->with('status', 'Saved search removed.');
    }
}
