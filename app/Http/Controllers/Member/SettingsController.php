<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
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
                ->whereBelongsTo($user)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
