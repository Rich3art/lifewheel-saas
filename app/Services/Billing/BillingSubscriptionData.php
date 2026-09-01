<?php

namespace App\Services\Billing;

use Carbon\CarbonInterface;

final readonly class BillingSubscriptionData
{
    public function __construct(
        public int $userId,
        public int $packageId,
        public string $providerKey,
        public ?int $paymentProviderId = null,
        public ?string $externalCustomerId = null,
        public ?string $externalSubscriptionId = null,
        public string $status = 'active',
        public int $amountCents = 0,
        public string $currency = 'USD',
        public string $billingInterval = 'monthly',
        public bool $trial = false,
        public ?CarbonInterface $currentPeriodStartsAt = null,
        public ?CarbonInterface $currentPeriodEndsAt = null,
        public ?CarbonInterface $cancelsAt = null,
        public ?array $metadata = null,
    ) {
    }
}
