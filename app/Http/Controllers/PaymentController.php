<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function checkout()
    {
        return view('checkout');
    }

    public function createCheckoutSession(Request $request)
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Sample Product',
                    ],
                    'unit_amount' => 2000, // $20.00
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('checkout.success', ['subdomain' => $request->route('subdomain')]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', ['subdomain' => $request->route('subdomain')]),
        ]);

        return response()->json([
            'id' => $session->id,
        ]);
    }

    public function success(Request $request)
    {
        // You can verify the session and fulfill order
        return view('checkout.success');
    }

    public function cancel()
    {
        return view('checkout.cancel');
    }
}