<?php

namespace LifeWheel\Plugins\Paystack;

use Illuminate\Http\Request;

final class PaystackSignatureVerifier
{
    public function verify(Request $request, string $secretKey): bool
    {
        $signature = (string) $request->header('x-paystack-signature', '');

        if ($signature === '' || $secretKey === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $request->getContent(), $secretKey);

        return hash_equals($expected, $signature);
    }
}
