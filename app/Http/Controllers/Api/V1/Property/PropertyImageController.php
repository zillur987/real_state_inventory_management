<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\UploadPropertyImagesRequest;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

final class PropertyImageController extends Controller
{
    public function store(UploadPropertyImagesRequest $request, Property $property): JsonResponse
    {
        $saved = [];

        foreach ($request->file('images', []) as $file) {
            $path = $file->store("properties/{$property->getKey()}", 'public');

            $image = $property->images()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);

            $saved[] = $this->transform($image);
        }

        return response()->json(['data' => $saved], 201);
    }

    public function destroy(Property $property, PropertyImage $image): JsonResponse
    {
        abort_unless($image->property_id === $property->id, 404);

        Storage::disk($image->disk)->delete($image->path);
        $image->delete();

        return response()->json(['message' => 'Property image deleted.']);
    }

    /** @return array<string, mixed> */
    private function transform(PropertyImage $image): array
    {
        return [
            'id' => $image->id,
            'original_name' => $image->original_name,
            'url' => Storage::disk($image->disk)->url($image->path),
            'mime_type' => $image->mime_type,
            'size' => $image->size,
            'created_at' => $image->created_at,
        ];
    }
}
