<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewPropertyVerificationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['verified', 'rejected', 'changes_requested'])],
            'notes' => ['required', 'string', 'max:5000'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['boolean'],
        ];
    }
}
