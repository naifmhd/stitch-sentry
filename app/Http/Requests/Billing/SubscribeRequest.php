<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $knownPaidPlans = array_keys(
            array_diff_key(
                config('features.plans', []),
                ['free' => true]
            )
        );

        /** @var array<string, string|null> $prices */
        $prices = config('paddle_plans.prices', []);

        $plansWithPrices = array_filter($knownPaidPlans, fn (string $slug) => filled($prices[$slug] ?? null));

        return [
            'planSlug' => ['required', 'string', 'in:'.implode(',', $plansWithPrices)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'planSlug.in' => 'The selected plan is not available for purchase.',
        ];
    }

    /**
     * Prepare the data for validation by merging route parameters.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['planSlug' => $this->route('planSlug')]);
    }
}
