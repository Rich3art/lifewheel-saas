<?php

namespace LifeWheel\Plugins\Stripe;

use Illuminate\Http\Request;

final class StripeSignatureVerifier
{
    public function verify(Request $request, string $endpointSecret, int $tolerance = 300): bool
    {
        $signatureHeader = (string) $request->header('Stripe-Signature', '');

        if ($endpointSecret === '' || $signatureHeader === '') {
            return false;
        }

        $parts = $this->parseHeader($signatureHeader);
        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? [];

        if ($timestamp <= 0 || $signatures === []) {
            return false;
        }

        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$request->getContent();
        $expected = hash_hmac('sha256', $signedPayload, $endpointSecret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function parseHeader(string $header): array
    {
        $parts = [];

        foreach (explode(',', $header) as $segment) {
            if (! str_contains($segment, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $segment, 2));
            $parts[$key] ??= [];
            $parts[$key][] = $value;
        }

        return $parts;
    }
}
