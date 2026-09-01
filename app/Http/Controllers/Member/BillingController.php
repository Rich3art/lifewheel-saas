<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\Package;
use App\Models\PackageProviderMapping;
use App\Models\Subscription;
use App\Services\Billing\CheckoutRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class BillingController extends Controller
{
    public function index(Request $request): View
    {
        return view('member.billing.index', [
            'subscriptions' => Subscription::query()
                ->with('package', 'provider')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get(),
            'invoices' => BillingInvoice::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(25)
                ->get(),
            'packages' => Package::query()
                ->with(['providerMappings' => fn ($query) => $query
                    ->where('active', true)
                    ->whereHas('provider', fn ($provider) => $provider->where('enabled', true))
                    ->with('provider')])
                ->where('active', true)
                ->where('public', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function checkout(Request $request, CheckoutRouter $checkout): RedirectResponse
    {
        $attributes = $request->validate([
            'mapping_id' => ['required', 'integer', 'exists:package_provider_mappings,id'],
        ]);

        $mapping = PackageProviderMapping::query()
            ->with('package', 'provider')
            ->whereKey($attributes['mapping_id'])
            ->where('active', true)
            ->whereHas('package', fn ($query) => $query->where('active', true)->where('public', true))
            ->whereHas('provider', fn ($query) => $query->where('enabled', true))
            ->firstOrFail();

        try {
            return $checkout->redirectToProvider($mapping);
        } catch (RuntimeException) {
            abort(404);
        }
    }
}
