<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * R5: vendor_admin team management. Every action is scoped server-side to the
 * acting admin's own vendor (resolved from the vendor.scope middleware) — a
 * vendor_admin can never touch a user who does not belong to their vendor, even
 * by guessing an id. Privileged changes are audit-logged.
 */
class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $this->vendor($request);

        $members = $vendor->users()
            ->orderByPivot('vendor_role')
            ->orderBy('name')
            ->get();

        return view('vendor.team.index', compact('vendor', 'members'));
    }

    /** Promote/demote a member between admin and worker. */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $this->assertMember($vendor, $user);

        $validated = $request->validate([
            'vendor_role' => ['required', 'in:admin,worker'],
        ]);

        $newRole   = $validated['vendor_role'];
        $globalRole = $newRole === 'admin' ? 'vendor_admin' : 'vendor_worker';

        // Don't allow demoting the last remaining admin — the vendor would be
        // left with no one able to manage it.
        if ($newRole === 'worker' && $this->isLastAdmin($vendor, $user)) {
            return back()->withErrors(['team' => 'You cannot demote the last remaining admin.']);
        }

        $previous = $user->pivotRoleFor($vendor);

        $vendor->users()->updateExistingPivot($user->id, ['vendor_role' => $newRole]);
        $user->syncRoles([$globalRole]);
        $user->update(['role' => $globalRole]);

        AuditLog::record($request->user(), 'vendor.member.role_change', $user, [
            'vendor_id' => $vendor->id,
            'from'      => $previous,
            'to'        => $newRole,
        ]);

        return back()->with('status', 'Team member role updated.');
    }

    /** Remove a member from the vendor (detaches; does not delete the account). */
    public function remove(Request $request, User $user): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $this->assertMember($vendor, $user);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['team' => 'You cannot remove yourself.']);
        }

        if ($this->isLastAdmin($vendor, $user)) {
            return back()->withErrors(['team' => 'You cannot remove the last remaining admin.']);
        }

        $vendor->users()->detach($user->id);

        AuditLog::record($request->user(), 'vendor.member.remove', $user, [
            'vendor_id' => $vendor->id,
        ]);

        return back()->with('status', 'Team member removed.');
    }

    // ─── Guards ──────────────────────────────────────────────────────────────────

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 403);

        return $vendor;
    }

    /** Reject any target that is not a member of the acting admin's vendor. */
    private function assertMember(Vendor $vendor, User $user): void
    {
        abort_unless($vendor->users()->where('users.id', $user->id)->exists(), 404);
    }

    private function isLastAdmin(Vendor $vendor, User $user): bool
    {
        if ($user->pivotRoleFor($vendor) !== 'admin') {
            return false;
        }

        return $vendor->admins()->count() <= 1;
    }
}
