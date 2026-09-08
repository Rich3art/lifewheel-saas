<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PolicyAcceptance;
use App\Models\PrivacyConsent;
use App\Models\PrivacyRequest;
use App\Services\MemberSettingsRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function __invoke(Request $request, MemberSettingsRegistry $settings): View
    {
        $user = $request->user()->load(['packages' => fn ($query) => $query->latest()]);

        return view('member.settings.index', [
            'user' => $user,
            'sections' => $settings->visibleSections(),
            'privacyRequests' => PrivacyRequest::query()
                ->with('dataExports')
                ->whereBelongsTo($user)
                ->latest()
                ->limit(10)
                ->get(),
            'privacyConsents' => PrivacyConsent::query()
                ->whereBelongsTo($user)
                ->get()
                ->keyBy('key'),
            'legalPages' => Page::query()
                ->with('currentVersion')
                ->where('is_legal', true)
                ->where('status', 'published')
                ->orderBy('title')
                ->get(),
            'policyAcceptances' => PolicyAcceptance::query()
                ->whereBelongsTo($user)
                ->pluck('page_version_id')
                ->all(),
        ]);
    }
}
