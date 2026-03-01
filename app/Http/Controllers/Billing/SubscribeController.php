<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubscribeRequest;
use Illuminate\Http\RedirectResponse;

class SubscribeController extends Controller
{
    /**
     * Initiate a Paddle subscription checkout for the given plan.
     *
     * Creates am org-scoped Cashier checkout and stores the Paddle
     * checkout options in the session. The billing page reads this
     * flash value and opens the Paddle overlay.
     */
    public function __invoke(SubscribeRequest $request, string $planSlug): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $org = $user->currentOrganization();

        if ($org === null) {
            return redirect()->route('billing')->withErrors([
                'message' => 'No active organization found.',
            ]);
        }

        $priceId = config("paddle_plans.prices.{$planSlug}");

        $subscriptionName = config('paddle_plans.subscription_name', 'default');

        $checkout = $org->subscribe($priceId, $subscriptionName)
            ->returnTo(route('billing'));

        session(['paddle_checkout' => $checkout->options()]);

        return redirect()->route('billing');
    }
}
