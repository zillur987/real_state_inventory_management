<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        $role = $user->role;

        if (is_string($role)) {
            $role = UserRole::tryFrom($role);
        }

        return in_array($role, [
            UserRole::Admin,
            UserRole::Manager,
            UserRole::SalesAgent,
        ], true);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:180'],
            'profession' => ['nullable', 'string', 'max:150'],
            'detailed_address' => ['nullable', 'string', 'max:2000'],
            'legal_status_note' => ['nullable', 'string', 'max:3000'],
            'expected_price' => ['nullable', 'numeric', 'min:0'],

            'final_negotiated_price' => [
                'sometimes',
                'prohibited',
            ],

            'relationship_manager_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'owner_type' => [
                'required',
                Rule::in(['individual', 'joint', 'company']),
            ],

            'status' => [
                'required',
                Rule::in(['active', 'inactive', 'blocked']),
            ],

            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
