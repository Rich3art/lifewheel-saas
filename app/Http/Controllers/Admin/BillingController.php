<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageProviderMapping;
use App\Models\PaymentProvider;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Billing\BillingManager;
use App\Services\Billing\BillingSubscriptionData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BillingController extends Controller
{
    public function index(): View
    {
        $activeSubscriptions = Subscription::query()->whereIn('status', ['active', 'trialing']);
        $monthlyMrrCents = (clone $activeSubscriptions)->get()->sum(function (Subscription $subscription): int {
            return match ($subscription->billing_interval) {
                'monthly' => $subscription->amount_cents,
                'quarterly' => (int) round($subscription->amount_cents / 3),
                'yearly' => (int) round($subscription->amount_cents / 12),
                default => 0,
            };
        });

        return view('admin.billing.index', [
            'providers' => PaymentProvider::query()->withCount('mappings')->orderBy('name')->get(),
            'mappings' => PackageProviderMapping::query()->with('package', 'provider')->latest()->get(),
            'subscriptions' => Subscription::query()->with('user', 'package', 'provider', 'events')->latest()->limit(50)->get(),
            'recentInvoices' => \App\Models\BillingInvoice::query()->with('user', 'subscription.package')->latest()->limit(20)->get(),
            'recentEvents' => SubscriptionEvent::query()->with('subscription.user')->latest()->limit(20)->get(),
            'packages' => Package::query()->where('active', true)->orderBy('sort_order')->get(),
            'users' => User::query()->orderBy('email')->limit(100)->get(),
            'metrics' => [
                'total_subscriptions' => Subscription::query()->count(),
                'active_subscriptions' => (clone $activeSubscriptions)->count(),
                'past_due_subscriptions' => Subscription::query()->where('status', 'past_due')->count(),
                'monthly_mrr_cents' => $monthlyMrrCents,
                'annual_run_rate_cents' => $monthlyMrrCents * 12,
            ],
        ]);
    }

    public function updateProvider(Request $request, PaymentProvider $provider, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'enabled' => ['nullable', 'boolean'],
            'sandbox' => ['nullable', 'boolean'],
        ]);

        $provider->fill([
            'name' => $attributes['name'],
            'enabled' => (bool) ($attributes['enabled'] ?? false),
            'sandbox' => (bool) ($attributes['sandbox'] ?? false),
        ])->save();

        $audit->log('billing.provider_updated', $request->user(), $provider);

        return back()->with('status', 'billing-provider-updated');
    }

    public function storeMapping(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'payment_provider_id' => ['required', 'integer', 'exists:payment_providers,id'],
            'external_product_id' => ['nullable', 'string', 'max:190'],
            'external_price_id' => ['nullable', 'string', 'max:190'],
            'amount_cents' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'active' => ['nullable', 'boolean'],
        ]);

        $mapping = PackageProviderMapping::query()->create([
            ...$attributes,
            'currency' => isset($attributes['currency']) ? strtoupper($attributes['currency']) : null,
            'active' => (bool) ($attributes['active'] ?? false),
        ]);

        $audit->log('billing.mapping_created', $request->user(), $mapping);

        return back()->with('status', 'billing-mapping-created');
    }

    public function updateMapping(Request $request, PackageProviderMapping $mapping, AuditLogger $audit): RedirectResponse
    {
        $attributes = $request->validate([
            'external_product_id' => ['nullable', 'string', 'max:190'],
            'external_price_id' => ['nullable', 'string', 'max:190'],
            'amount_cents' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'active' => ['nullable', 'boolean'],
        ]);

        $mapping->fill([
            'external_product_id' => $attributes['external_product_id'] ?? null,
            'external_price_id' => $attributes['external_price_id'] ?? null,
            'amount_cents' => $attributes['amount_cents'] ?? null,
            'currency' => isset($attributes['currency']) ? strtoupper($attributes['currency']) : null,
            'active' => (bool) ($attributes['active'] ?? false),
        ])->save();

        $audit->log('billing.mapping_updated', $request->user(), $mapping);

        return back()->with('status', 'billing-mapping-updated');
    }

    public function activateManualSubscription(Request $request, BillingManager $billing): RedirectResponse
    {
        $attributes = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'billing_interval' => ['required', 'in:monthly,quarterly,yearly,lifetime'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'current_period_ends_at' => ['nullable', 'date'],
        ]);

        $billing->activateSubscription(new BillingSubscriptionData(
            userId: (int) $attributes['user_id'],
            packageId: (int) $attributes['package_id'],
            providerKey: 'manual',
            externalSubscriptionId: 'manual-'.$attributes['user_id'].'-'.$attributes['package_id'].'-'.now()->timestamp,
            amountCents: (int) $attributes['amount_cents'],
            currency: $attributes['currency'],
            billingInterval: $attributes['billing_interval'],
            currentPeriodStartsAt: now(),
            currentPeriodEndsAt: isset($attributes['current_period_ends_at']) ? \Illuminate\Support\Carbon::parse($attributes['current_period_ends_at']) : null,
            metadata: ['source' => 'admin_manual'],
        ), $request->user());

        return back()->with('status', 'manual-subscription-activated');
    }

    public function cancelSubscription(Request $request, Subscription $subscription, BillingManager $billing): RedirectResponse
    {
        $attributes = $request->validate(['immediate' => ['nullable', 'boolean']]);
        $billing->cancelSubscription($subscription, $request->user(), (bool) ($attributes['immediate'] ?? false));

        return back()->with('status', 'subscription-cancelled');
    }
}
