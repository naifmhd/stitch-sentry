<?php

namespace App\Http\Requests\QaRun;

use Illuminate\Foundation\Http\FormRequest;

class CreateQaRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preset' => ['sometimes', 'string', 'in:custom,standard,premium'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preset.in' => 'The selected preset is not available on your plan.',
        ];
    }
}
