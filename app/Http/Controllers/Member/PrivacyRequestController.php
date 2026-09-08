<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DataExport;
use App\Models\Page;
use App\Models\PolicyAcceptance;
use App\Models\PrivacyConsent;
use App\Models\PrivacyRequest as UserPrivacyRequest;
use App\Services\AuditLogger;
use App\Services\Privacy\PrivacyExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PrivacyRequestController extends Controller
{
    public function store(Request $request, AuditLogger $audit, PrivacyExportService $exports): RedirectResponse
    {
        $attributes = $request->validate([
            'type' => ['required', 'in:data_export,correction,erasure,consent_review'],
            'details' => ['nullable', 'string', 'max:3000'],
            'identity_confirmed' => ['nullable', 'accepted'],
        ]);

        $privacyRequest = UserPrivacyRequest::query()->create([
            'user_id' => $request->user()->id,
            'type' => $attributes['type'],
            'details' => $attributes['details'] ?? null,
            'identity_confirmed_by_user_at' => isset($attributes['identity_confirmed']) ? now() : null,
            'due_at' => now()->addDays(30),
        ]);

        if ($attributes['type'] === 'data_export') {
            $export = DataExport::query()->create([
                'user_id' => $request->user()->id,
                'privacy_request_id' => $privacyRequest->id,
                'status' => 'pending',
                'format' => 'json',
                'expires_at' => now()->addDays(7),
            ]);

            $exports->generate($export);
        }

        $audit->log('privacy.request_created', $request->user(), $privacyRequest, ['type' => $attributes['type']]);

        return back()->with('status', 'privacy-request-created');
    }

    public function download(Request $request, DataExport $dataExport): BinaryFileResponse
    {
        abort_unless($dataExport->user_id === $request->user()->id, 404);
        abort_unless($dataExport->status === 'ready', 404);
        abort_if($dataExport->expires_at !== null && $dataExport->expires_at->isPast(), 410);
        abort_unless($dataExport->path && is_file($dataExport->path), 404);

        return response()->download($dataExport->path, 'lifewheel-data-export-'.$dataExport->id.'.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function updateConsent(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'consents' => ['array'],
            'consents.product_updates' => ['nullable', 'boolean'],
            'consents.research_feedback' => ['nullable', 'boolean'],
        ]);

        foreach (['product_updates', 'research_feedback'] as $key) {
            $granted = (bool) data_get($attributes, 'consents.'.$key, false);
            PrivacyConsent::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'key' => $key],
                [
                    'granted' => $granted,
                    'granted_at' => $granted ? now() : null,
                    'revoked_at' => $granted ? null : now(),
                    'metadata' => ['source' => 'member_settings'],
                ],
            );
        }

        $audit->log('privacy.consents_updated', $request->user());

        return back()->with('status', 'privacy-consents-updated');
    }

    public function acceptPolicy(Request $request, Page $page, AuditLogger $audit): RedirectResponse
    {
        $page->load('currentVersion');
        abort_unless($page->is_legal && $page->currentVersion, 404);

        PolicyAcceptance::query()->firstOrCreate(
            ['user_id' => $request->user()->id, 'page_version_id' => $page->currentVersion->id],
            [
                'page_id' => $page->id,
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ],
        );

        $audit->log('privacy.policy_accepted', $request->user(), $page, ['version' => $page->currentVersion->version]);

        return back()->with('status', 'privacy-policy-accepted');
    }
}
