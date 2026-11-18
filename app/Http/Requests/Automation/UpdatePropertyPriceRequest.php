<?php

declare(strict_types=1);

namespace App\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePropertyPriceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('automation.price-history') ?? false; }
    public function rules(): array
    {
        return ['asking_price' => ['required', 'numeric', 'min:0', 'max:999999999999.99']];
    }
}
