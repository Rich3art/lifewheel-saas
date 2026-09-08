<?php

namespace App\Http\Controllers;

use App\Services\Installer\InstallationState;
use App\Services\Installer\Installer;
use App\Services\Installer\RequirementChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class InstallController extends Controller
{
    public function show(InstallationState $installationState, RequirementChecker $checker): View
    {
        abort_if($installationState->isLocked(), 404);

        return view('install.show', [
            'checks' => $checker->check(),
            'connectionResult' => session('installer_connection'),
        ]);
    }

    public function check(Request $request, InstallationState $installationState, RequirementChecker $checker): RedirectResponse
    {
        abort_if($installationState->isLocked(), 404);

        $validated = $request->validate($this->databaseRules());
        $result = $checker->checkDatabase($this->databasePayload($validated));

        return back()
            ->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation']))
            ->with('installer_connection', $result);
    }

    public function store(
        Request $request,
        InstallationState $installationState,
        RequirementChecker $checker,
        Installer $installer,
    ): RedirectResponse {
        abort_if($installationState->isLocked(), 404);

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
            'timezone' => ['required', 'timezone'],
            ...$this->databaseRules(),
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email:rfc,dns', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($checker->hasBlockingFailures()) {
            return back()
                ->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors(['requirements' => 'Resolve the failed server requirements before installing.']);
        }

        $connection = $checker->checkDatabase($this->databasePayload($validated));

        if (! $connection['ok']) {
            return back()
                ->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors(['db_database' => $connection['detail']]);
        }

        $installer->install($validated);

        return redirect()->route('login')->with('status', 'LifeWheel SaaS installation is complete. Sign in with the Super Admin account.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function databaseRules(): array
    {
        return [
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{host: string, port: int|string, database: string, username: string, password?: string|null}
     */
    private function databasePayload(array $validated): array
    {
        return [
            'host' => (string) $validated['db_host'],
            'port' => $validated['db_port'],
            'database' => (string) $validated['db_database'],
            'username' => (string) $validated['db_username'],
            'password' => $validated['db_password'] ?? null,
        ];
    }
}
