<?php

namespace LifeWheel\Plugins\PayPal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class PayPalWebhookVerifier
{
    public function verify(Request $request, string $webhookId): bool
    {
        if ($webhookId === '') {
            return false;
        }

        $transmissionId = (string) $request->header('PAYPAL-TRANSMISSION-ID', '');
        $transmissionTime = (string) $request->header('PAYPAL-TRANSMISSION-TIME', '');
        $transmissionSignature = (string) $request->header('PAYPAL-TRANSMISSION-SIG', '');
        $certificateUrl = (string) $request->header('PAYPAL-CERT-URL', '');
        $algorithm = (string) $request->header('PAYPAL-AUTH-ALGO', '');

        if ($transmissionId === '' || $transmissionTime === '' || $transmissionSignature === '' || $certificateUrl === '' || $algorithm === '') {
            return false;
        }

        if (! $this->allowedCertificateUrl($certificateUrl)) {
            return false;
        }

        if (! in_array($algorithm, ['SHA256withRSA', 'SHA512withRSA'], true)) {
            return false;
        }

        $certificate = Http::timeout(5)->get($certificateUrl);

        if (! $certificate->ok()) {
            return false;
        }

        $publicKey = openssl_pkey_get_public($certificate->body());

        if ($publicKey === false) {
            return false;
        }

        $crc = sprintf('%u', crc32($request->getContent()));
        $signedMessage = "{$transmissionId}|{$transmissionTime}|{$webhookId}|{$crc}";
        $opensslAlgorithm = $algorithm === 'SHA512withRSA' ? OPENSSL_ALGO_SHA512 : OPENSSL_ALGO_SHA256;

        return openssl_verify($signedMessage, base64_decode($transmissionSignature, true) ?: '', $publicKey, $opensslAlgorithm) === 1;
    }

    private function allowedCertificateUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== 'https' || ! is_string($host)) {
            return false;
        }

        return $host === 'api-m.paypal.com'
            || $host === 'api-m.sandbox.paypal.com'
            || str_ends_with($host, '.paypal.com');
    }
}
