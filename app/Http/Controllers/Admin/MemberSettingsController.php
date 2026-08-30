<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberSettingsSection;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MemberSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.member-sections', [
            'sections' => MemberSettingsSection::query()->orderBy('sort_order')->orderBy('label')->get(),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'sections' => ['array'],
            'sections.*.enabled' => ['nullable', 'boolean'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        foreach (MemberSettingsSection::query()->get() as $section) {
            $input = $attributes['sections'][$section->id] ?? [];
            $section->forceFill([
                'enabled' => $section->required || (bool) ($input['enabled'] ?? false),
                'sort_order' => (int) ($input['sort_order'] ?? $section->sort_order),
            ])->save();
        }

        $audit->log('admin.member_settings_visibility_updated', $request->user());

        return back()->with('status', 'member-settings-updated');
    }
}
