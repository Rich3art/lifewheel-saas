<?php

namespace LifeWheel\Plugins\Whop;

use Illuminate\Http\Request;

final class WhopSignatureVerifier
{
    public function verify(Request $request, string $webhookSecret, int $tolerance = 300): bool
    {
        $webhookId = (string) $request->header('webhook-id', '');
        $timestamp = (string) $request->header('webhook-timestamp', '');
        $signatureHeader = (string) $request->header('webhook-signature', '');

        if ($webhookSecret === '' || $webhookId === '' || $timestamp === '' || $signatureHeader === '') {
            return false;
        }

        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $signatures = $this->signatures($signatureHeader);

        if ($signatures === []) {
            return false;
        }

        $signedPayload = $webhookId.'.'.$timestamp.'.'.$request->getContent();
        $expected = base64_encode(hash_hmac('sha256', $signedPayload, $webhookSecret, true));

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function signatures(string $header): array
    {
        return collect(explode(' ', str_replace(',', ' ', $header)))
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => str_starts_with($part, 'v1,') || preg_match('/^[A-Za-z0-9+\/]+=*$/', $part) === 1)
            ->map(fn (string $part): string => str_starts_with($part, 'v1,') ? substr($part, 3) : $part)
            ->filter()
            ->values()
            ->all();
    }
}
