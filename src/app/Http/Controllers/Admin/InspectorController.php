<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Inspection\Models\Inspection;
use App\Modules\Inspection\Models\Inspector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** TI3: admin vetting/management of the inspector panel + inspection oversight. */
class InspectorController extends Controller
{
    public function index(): View
    {
        return view('admin.inspectors.index', [
            'inspectors' => Inspector::withCount('inspections')->orderBy('name')->get(),
            'inspections' => Inspection::with(['inspector', 'buyer', 'vehicle.make', 'vehicle.vehicleModel'])->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'kind'          => ['required', 'in:company,mechanic,expert'],
            'coverage_area' => ['nullable', 'string', 'max:150'],
            'phone'         => ['nullable', 'string', 'max:40'],
            'email'         => ['nullable', 'email', 'max:150'],
            'link_email'    => ['nullable', 'email'], // link a login for the inspector portal
        ]);

        $userId = null;
        if (! empty($validated['link_email'])) {
            $userId = User::where('email', $validated['link_email'])->value('id');
        }

        $inspector = Inspector::create([
            'name' => $validated['name'], 'kind' => $validated['kind'],
            'coverage_area' => $validated['coverage_area'] ?? null,
            'phone' => $validated['phone'] ?? null, 'email' => $validated['email'] ?? null,
            'user_id' => $userId, 'is_active' => true,
        ]);

        AuditLog::record($request->user(), 'inspector.create', $inspector, ['name' => $inspector->name]);

        return back()->with('status', 'Inspector added to the panel.');
    }

    public function toggle(Request $request, Inspector $inspector): RedirectResponse
    {
        $inspector->update(['is_active' => ! $inspector->is_active]);

        AuditLog::record($request->user(), 'inspector.toggle', $inspector, ['is_active' => $inspector->is_active]);

        return back()->with('status', 'Inspector ' . ($inspector->is_active ? 'activated' : 'deactivated') . '.');
    }
}
