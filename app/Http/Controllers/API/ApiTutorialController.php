<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\TutorialResource;
use App\Models\Tutorial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTutorialController extends Controller
{
    /**
     * GET /api/tutorials
     * List published tutorials with optional search & category filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tutorial::where('is_published', true);

        if ($request->filled('search')) {
            $search = strip_tags($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('video_type', $request->query('category'));
        }

        $tutorials = $query->latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => TutorialResource::collection($tutorials)->response()->getData(true),
        ]);
    }

    /**
     * GET /api/tutorials/{tutorial}
     * Show a single published tutorial.
     */
    public function show(Tutorial $tutorial): JsonResponse
    {
        if (! $tutorial->is_published) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new TutorialResource($tutorial),
        ]);
    }
}
