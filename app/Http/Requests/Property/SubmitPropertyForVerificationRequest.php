<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitPropertyForVerificationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:5000']];
    }
}
