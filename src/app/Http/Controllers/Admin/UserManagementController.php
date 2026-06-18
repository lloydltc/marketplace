<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * R6: super-admin user management. Privileged, destructive actions (create,
 * suspend, role change, password reset, email-verify bypass) are restricted to
 * super_admin at the route layer; plain admins get read-only index/show via
 * {@see UserController}. Every action here is audit-logged.
 *
 * Staff roles a super_admin may assign directly. Vendor membership roles are
 * managed through the vendor team flow (R5), not here.
 */
class UserManagementController extends Controller
{
    private const ASSIGNABLE_ROLES = [
        'super_admin', 'admin', 'agent', 'rider', 'private_seller', 'customer',
    ];

    public function create(): \Illuminate\View\View
    {
        return view('admin.users.create', ['roles' => self::ASSIGNABLE_ROLES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'role'  => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        $tempPassword = Str::password(12);

        $user = User::create([
            'name'                  => $validated['name'],
            'email'                 => $validated['email'],
            'password'              => Hash::make($tempPassword),
            'role'                  => $validated['role'],
            'status'                => 'active',
            'force_password_change' => true,
            'email_verified_at'     => now(), // staff accounts skip OTP verification
        ]);
        $user->syncRoles([$validated['role']]);

        AuditLog::record($request->user(), 'user.create', $user, [
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('status', "User created. Temporary password: {$tempPassword}");
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($this->isSelf($request, $user)) {
            return back()->withErrors(['user' => 'You cannot suspend your own account.']);
        }

        $user->update(['status' => 'suspended']);

        AuditLog::record($request->user(), 'user.suspend', $user, [
            'reason' => $request->input('reason'),
        ]);

        return back()->with('status', "{$user->name} has been suspended.");
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $user->update(['status' => 'active']);

        AuditLog::record($request->user(), 'user.reactivate', $user);

        return back()->with('status', "{$user->name} has been reactivated.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        if ($this->isSelf($request, $user)) {
            return back()->withErrors(['user' => 'You cannot change your own role.']);
        }

        $previous = $user->role;

        $user->update(['role' => $validated['role']]);
        $user->syncRoles([$validated['role']]);

        AuditLog::record($request->user(), 'user.role_change', $user, [
            'from' => $previous,
            'to'   => $validated['role'],
        ]);

        return back()->with('status', "Role updated to \"{$validated['role']}\".");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $tempPassword = Str::password(12);

        $user->update([
            'password'              => Hash::make($tempPassword),
            'force_password_change' => true,
        ]);

        AuditLog::record($request->user(), 'user.password_reset', $user);

        return back()->with('status', "Password reset. Temporary password: {$tempPassword}");
    }

    public function verifyEmail(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['email_verified_at' => now()])->save();

        AuditLog::record($request->user(), 'user.email_verify_bypass', $user);

        return back()->with('status', "{$user->name}'s email has been marked verified.");
    }

    private function isSelf(Request $request, User $user): bool
    {
        return $request->user()->id === $user->id;
    }
}
