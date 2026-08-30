<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'recovery_code',
        'recovery_codes',
        'api_key',
    ];

    public function log(string $event, ?User $actor = null, ?Model $subject = null, array $metadata = [], ?Request $request = null): void
    {
        $request ??= request();

        AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'metadata' => $this->redact($metadata),
        ]);
    }

    private function redact(array $metadata): array
    {
        foreach ($metadata as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $metadata[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $metadata[$key] = $this->redact($value);
            }
        }

        return Arr::whereNotNull($metadata);
    }
}
