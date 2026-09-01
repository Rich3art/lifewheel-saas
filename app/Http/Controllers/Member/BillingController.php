<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BillingController extends Controller
{
    public function index(Request $request): View
    {
        return view('member.billing.index', [
            'subscriptions' => Subscription::query()
                ->with('package')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get(),
            'invoices' => BillingInvoice::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(25)
                ->get(),
        ]);
    }
}
