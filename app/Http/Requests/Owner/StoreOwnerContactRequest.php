<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOwnerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('owners.request_contact') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
