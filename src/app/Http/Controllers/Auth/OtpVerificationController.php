<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OtpVerificationController extends Controller
{
    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended('/');
        }

        if (! $user->isOtpValid($validated['otp'])) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code. Please request a new one.']);
        }

        $user->markEmailAsVerified();
        $user->updateQuietly(['email_otp' => null, 'email_otp_expires_at' => null]);

        event(new Verified($user));

        return redirect()->intended('/')->with('status', 'email-verified');
    }
}
