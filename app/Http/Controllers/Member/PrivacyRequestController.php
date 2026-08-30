<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DataExport;
use App\Models\PrivacyRequest as UserPrivacyRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PrivacyRequestController extends Controller
{
    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'type' => ['required', 'in:data_export,correction,erasure,consent_review'],
            'details' => ['nullable', 'string', 'max:3000'],
        ]);

        $privacyRequest = UserPrivacyRequest::query()->create([
            'user_id' => $request->user()->id,
            'type' => $attributes['type'],
            'details' => $attributes['details'] ?? null,
        ]);

        if ($attributes['type'] === 'data_export') {
            DataExport::query()->create([
                'user_id' => $request->user()->id,
                'privacy_request_id' => $privacyRequest->id,
                'status' => 'pending',
                'format' => 'json',
                'expires_at' => now()->addDays(7),
            ]);
        }

        $audit->log('privacy.request_created', $request->user(), $privacyRequest, ['type' => $attributes['type']]);

        return back()->with('status', 'privacy-request-created');
    }
}
