<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function plans()
    {
        return inertia('billing/Plans', [
            'stripeKey' => config('cashier.key'),
            'prices' => [
                ['id' => env('STRIPE_PRICE_DEFAULT'), 'name' => 'Abonnement mensuel', 'amount' => '4,99 € / mois'],
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $price = $request->query('price') ?? env('STRIPE_PRICE_DEFAULT');
        abort_unless($price, 400, 'Missing price');

        $checkout = $request->user()

            ->newSubscription('default', $price)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('billing.cancel'),
            ]);

        $redirectResponse = $checkout->redirect();


        if ($request->inertia()) {
            return Inertia::location($redirectResponse->getTargetUrl());
        }

        return $redirectResponse;
    }

    public function success(Request $request)
    {
        return inertia('billing/Success');
    }

    public function cancel()
    {
        return inertia('billing/Cancel');
    }

    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('billing.plans'));
    }
}
