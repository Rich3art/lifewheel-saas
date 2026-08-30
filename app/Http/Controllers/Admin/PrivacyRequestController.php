<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivacyRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PrivacyRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.privacy.requests', [
            'requests' => PrivacyRequest::query()->with('user', 'processor')->latest()->paginate(25),
        ]);
    }

    public function update(Request $request, PrivacyRequest $privacyRequest, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'status' => ['required', 'in:pending,identity_check,processing,completed,rejected,cancelled'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $privacyRequest->forceFill([
            'status' => $attributes['status'],
            'admin_notes' => $attributes['admin_notes'] ?? null,
            'processed_by' => $request->user()->id,
            'completed_at' => $attributes['status'] === 'completed' ? now() : $privacyRequest->completed_at,
        ])->save();

        $audit->log('admin.privacy_request_updated', $request->user(), $privacyRequest, ['status' => $attributes['status']]);

        return back()->with('status', 'privacy-request-updated');
    }
}
