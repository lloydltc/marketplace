<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Modules\Parts\Models\Part;
use App\Modules\Parts\Services\PartMerger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PM9: merge a duplicate canonical part into a keeper (offerings, OEM numbers,
 * fitments move over; duplicate is soft-deleted). Audited.
 */
class PartMergeController extends Controller
{
    public function merge(Request $request, PartMerger $merger): RedirectResponse
    {
        $validated = $request->validate([
            'keeper_id'    => ['required', 'uuid', 'exists:parts,id'],
            'duplicate_id' => ['required', 'uuid', 'exists:parts,id', 'different:keeper_id'],
        ]);

        $keeper = Part::findOrFail($validated['keeper_id']);
        $duplicate = Part::findOrFail($validated['duplicate_id']);

        $merger->merge($keeper, $duplicate);

        AuditLog::record($request->user(), 'catalog.part.merge', $keeper, [
            'duplicate_id' => $duplicate->id, 'duplicate_name' => $duplicate->name,
        ]);

        return redirect()->route('admin.parts.edit', $keeper)
            ->with('status', "Merged \"{$duplicate->name}\" into \"{$keeper->name}\".");
    }
}
