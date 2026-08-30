<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $this->authorize('update', $request->user());

        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $request->user());

        $request->user()->fill($request->validated());
        $request->user()->save();

        $audit->log('profile.updated', $request->user(), $request->user());

        return back()->with('status', 'profile-updated');
    }

    public function password(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manageSecurity', $request->user());

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
        ])->save();

        $request->session()->regenerate();
        $audit->log('profile.password_updated', $request->user(), $request->user());

        return back()->with('status', 'password-updated');
    }
}
