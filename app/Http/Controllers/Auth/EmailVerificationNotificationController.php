<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('member.dashboard'));
        }

        $request->user()->sendEmailVerificationNotification();
        $audit->log('auth.verification_notification_sent', $request->user(), $request->user());

        return back()->with('status', 'verification-link-sent');
    }
}
