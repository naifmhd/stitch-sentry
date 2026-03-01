<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Services\PlanResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(private readonly PlanResolver $planResolver) {}

    /**
     * Display the billing page for the current organization.
     */
    public function __invoke(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $org = $user->currentOrganization();

        $currentPlanSlug = $org ? $this->planResolver->resolve($org) : 'free';

        /** @var array<string, array<string, mixed>> $allPlans */
        $allPlans = config('features.plans', []);

        $paidPlans = collect($allPlans)
            ->except('free')
            ->map(fn (array $plan, string $slug) => [
                'slug' => $slug,
                'label' => $plan['label'],
                'limits' => $plan['limits'],
                'priceConfigured' => filled(config("paddle_plans.prices.{$slug}")),
            ])
            ->values()
            ->all();

        $subscriptionStatus = null;

        if ($org) {
            $subscriptionName = config('paddle_plans.subscription_name', 'default');
            $subscription = $org->subscription($subscriptionName);

            if ($subscription) {
                $subscriptionStatus = $subscription->status;
            }
        }

        return Inertia::render('billing/Index', [
            'organization' => $org ? ['id' => $org->id, 'name' => $org->name] : null,
            'currentPlanSlug' => $currentPlanSlug,
            'currentPlanLabel' => $allPlans[$currentPlanSlug]['label'] ?? 'Free',
            'subscriptionStatus' => $subscriptionStatus,
            'paidPlans' => $paidPlans,
        ]);
    }
}
