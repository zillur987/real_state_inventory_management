<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StoreSavedPropertySearchRequest;
use App\Models\SavedPropertySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SavedPropertySearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = SavedPropertySearch::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'name', 'filters', 'created_at']);

        return response()->json(['data' => $items]);
    }

    public function store(StoreSavedPropertySearchRequest $request): JsonResponse
    {
        $saved = SavedPropertySearch::query()->create([
            'user_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'filters' => $request->validated('filters'),
        ]);

        return response()->json(['data' => $saved], 201);
    }

    public function destroy(Request $request, SavedPropertySearch $savedSearch): JsonResponse
    {
        abort_unless((int) $savedSearch->user_id === (int) $request->user()->id, 404);

        $savedSearch->delete();

        return response()->json(['message' => 'Saved search deleted.']);
    }
}
