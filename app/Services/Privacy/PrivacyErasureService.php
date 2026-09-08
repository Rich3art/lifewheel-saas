<?php

namespace App\Services\Privacy;

use App\Models\PrivacyRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PrivacyErasureService
{
    public function anonymizeEligibleUserData(PrivacyRequest $privacyRequest): array
    {
        $user = $privacyRequest->user;
        $anonymousEmail = 'erased-user-'.$user->id.'@example.invalid';

        DB::transaction(function () use ($user, $anonymousEmail): void {
            $user->forceFill([
                'name' => 'Erased User '.$user->id,
                'email' => $anonymousEmail,
                'username' => null,
                'avatar_path' => null,
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'suspended_at' => now(),
            ])->save();

            $this->deleteRows([
                'sessions',
                'privacy_consents',
            ], $user->id);
        });

        return [
            'anonymized_user_id' => $user->id,
            'retained_records' => [
                'billing, audit, policy acceptance, and product history may be retained for legal, security, or account integrity review.',
            ],
        ];
    }

    /**
     * @param array<int, string> $tables
     */
    private function deleteRows(array $tables, int $userId): void
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $userId)->delete();
            }
        }
    }
}
