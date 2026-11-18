<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;

final class StoreClientActivityRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('clients.update') ?? false; }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:call,meeting,email,visit,note,follow_up'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
            'completed' => ['sometimes', 'boolean'],
        ];
    }
}
