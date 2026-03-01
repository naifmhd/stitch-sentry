<?php

namespace App\Http\Requests\Upload;

use App\Domain\Billing\Services\FeatureGate;
use Illuminate\Foundation\Http\FormRequest;

class IngestRequest extends FormRequest
{
    public function __construct(private readonly FeatureGate $featureGate)
    {
        parent::__construct();
    }

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
        $org = $this->user()?->currentOrganization();

        // Derive per-plan max in KB; fall back to 10 MB if no org context yet.
        $maxKb = $org
            ? (int) ceil($this->featureGate->maxFileSizeBytes($org) / 1024)
            : 10_240;

        return [
            'file' => ['required', 'file', 'extensions:dst', "max:{$maxKb}"],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a DST file to upload.',
            'file.file' => 'The upload must be a valid file.',
            'file.extensions' => 'Only .dst embroidery files are accepted.',
            'file.max' => 'The file exceeds the maximum size allowed by your current plan.',
        ];
    }
}
