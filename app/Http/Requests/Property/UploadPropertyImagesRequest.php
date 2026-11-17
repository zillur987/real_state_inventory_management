<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

final class UploadPropertyImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
