<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivacyRequest;
use App\Services\AuditLogger;
use App\Services\Privacy\PrivacyErasureService;
use App\Services\Privacy\PrivacyExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PrivacyRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.privacy.requests', [
            'requests' => PrivacyRequest::query()->with('user', 'processor', 'dataExports')->latest()->paginate(25),
            'metrics' => [
                'pending' => PrivacyRequest::query()->where('status', 'pending')->count(),
                'identity_check' => PrivacyRequest::query()->where('status', 'identity_check')->count(),
                'processing' => PrivacyRequest::query()->where('status', 'processing')->count(),
                'overdue' => PrivacyRequest::query()->whereNull('completed_at')->where('due_at', '<', now())->count(),
            ],
        ]);
    }

    public function update(
        Request $request,
        PrivacyRequest $privacyRequest,
        AuditLogger $audit,
        PrivacyExportService $exports,
        PrivacyErasureService $erasures,
    ): RedirectResponse {
        $attributes = $request->validate([
            'status' => ['required', 'in:pending,identity_check,processing,completed,rejected,cancelled'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
            'identity_confirmed' => ['nullable', 'boolean'],
            'confirm_erasure' => ['nullable', 'accepted'],
        ]);

        $resolution = $privacyRequest->resolution_summary ?? [];

        if ($privacyRequest->type === 'data_export' && in_array($attributes['status'], ['processing', 'completed'], true)) {
            $export = $privacyRequest->dataExports()->latest()->first();

            if ($export && $export->status !== 'ready') {
                $exports->generate($export);
                $resolution['data_export_id'] = $export->id;
            }
        }

        if ($privacyRequest->type === 'erasure' && $attributes['status'] === 'completed') {
            abort_unless((bool) ($attributes['confirm_erasure'] ?? false), 422);
            $privacyRequest->loadMissing('user');
            $resolution['erasure'] = $erasures->anonymizeEligibleUserData($privacyRequest);
        }

        $privacyRequest->forceFill([
            'status' => $attributes['status'],
            'admin_notes' => $attributes['admin_notes'] ?? null,
            'identity_confirmed_at' => (bool) ($attributes['identity_confirmed'] ?? false) ? now() : $privacyRequest->identity_confirmed_at,
            'processed_by' => $request->user()->id,
            'completed_at' => $attributes['status'] === 'completed' ? now() : $privacyRequest->completed_at,
            'resolution_summary' => $resolution,
        ])->save();

        $audit->log('admin.privacy_request_updated', $request->user(), $privacyRequest, ['status' => $attributes['status']]);

        return back()->with('status', 'privacy-request-updated');
    }
}
