<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\GuideResource;
use App\Models\Guide;
use App\Models\GuideOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiGuideController extends Controller
{
    /**
     * GET /api/guides
     * List published guides grouped by option/category.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Guide::where('is_published', true)
            ->with('option')
            ->orderBy('order');

        if ($request->filled('search')) {
            $search = strip_tags($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('option_id')) {
            $query->where('guide_option_id', $request->query('option_id'));
        }

        $guides = $query->paginate(20);

        return response()->json([
            'status' => true,
            'data'   => GuideResource::collection($guides)->response()->getData(true),
        ]);
    }

    /**
     * GET /api/guides/categories
     * Return all guide options/categories for filter UI.
     */
    public function categories(): JsonResponse
    {
        $options = GuideOption::select('id', 'name')->get();

        return response()->json([
            'status' => true,
            'data'   => $options,
        ]);
    }

    /**
     * GET /api/guides/{guide}
     * Show a single guide with its items.
     */
    public function show(Guide $guide): JsonResponse
    {
        if (! $guide->is_published) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new GuideResource($guide->load(['items', 'option'])),
        ]);
    }
}
