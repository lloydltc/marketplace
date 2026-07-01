<?php

namespace App\Http\Controllers;

use App\Modules\Inspection\Models\Inspection;
use App\Modules\Inspection\Models\Inspector;
use App\Modules\Inspection\Services\InspectionBookingService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * TI3/TI4: buyer inspection booking (pick inspector/slot → pay via Pesepay →
 * confirm), report viewing, and rating the inspector. Manual-first.
 */
class InspectionController extends Controller
{
    public function __construct(private readonly InspectionBookingService $booking) {}

    public function create(Vehicle $vehicle): View
    {
        abort_unless($vehicle->isActive(), 404);

        return view('inspections.create', [
            'vehicle'    => $vehicle,
            'inspectors' => Inspector::active()->orderByDesc('rating')->get(),
            'feeUsd'     => $this->booking->feeMinor() / 100,
        ]);
    }

    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->isActive(), 404);

        $validated = $request->validate([
            'inspector_id'  => ['required', 'uuid', 'exists:inspectors,id'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
        ]);

        $inspector = Inspector::active()->findOrFail($validated['inspector_id']);
        $inspection = $this->booking->book(
            $request->user(), $inspector, $vehicle, null,
            ! empty($validated['scheduled_for']) ? Carbon::parse($validated['scheduled_for']) : null,
        );

        $redirect = $this->booking->pay($inspection, route('inspections.return', $inspection), route('payments.webhook'));

        return $redirect ? redirect()->away($redirect)
            : redirect()->route('inspections.show', $inspection)->with('status', 'Inspection booked and paid.');
    }

    public function paymentReturn(Request $request, Inspection $inspection): RedirectResponse
    {
        $this->authorizeBuyer($request, $inspection);
        $this->booking->confirm($inspection);

        return redirect()->route('inspections.show', $inspection);
    }

    public function index(Request $request): View
    {
        $inspections = Inspection::where('buyer_id', $request->user()->id)
            ->with(['inspector', 'vehicle.make', 'vehicle.vehicleModel'])->latest()->paginate(20);

        return view('inspections.index', compact('inspections'));
    }

    public function show(Request $request, Inspection $inspection): View
    {
        $this->authorizeBuyer($request, $inspection);
        $inspection->load(['inspector', 'vehicle.make', 'vehicle.vehicleModel']);

        return view('inspections.show', compact('inspection'));
    }

    public function cancel(Request $request, Inspection $inspection): RedirectResponse
    {
        $this->authorizeBuyer($request, $inspection);
        abort_if(in_array($inspection->status, ['completed', 'cancelled'], true), 422, 'This inspection cannot be cancelled.');

        $this->booking->cancel($inspection);

        return back()->with('status', 'Inspection cancelled.');
    }

    /** TI4: buyer rates the inspector (feeds inspector reputation). */
    public function rate(Request $request, Inspection $inspection): RedirectResponse
    {
        $this->authorizeBuyer($request, $inspection);
        abort_unless($inspection->isCompleted(), 422, 'You can rate once the report is in.');

        $validated = $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);
        $inspection->update(['rating_given' => $validated['rating']]);

        // Recompute the inspector's average rating deterministically.
        $inspector = $inspection->inspector;
        if ($inspector) {
            $rated = $inspector->inspections()->whereNotNull('rating_given');
            $inspector->update(['rating' => round((float) $rated->avg('rating_given'), 2), 'review_count' => $rated->count()]);
        }

        return back()->with('status', 'Thanks for rating your inspector.');
    }

    private function authorizeBuyer(Request $request, Inspection $inspection): void
    {
        abort_unless($inspection->buyer_id === $request->user()->id, 403);
    }
}
