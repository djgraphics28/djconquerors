<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\WithdrawalResource;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiWithdrawalController extends Controller
{
    /**
     * GET /api/withdrawals
     * List authenticated user's withdrawals (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $withdrawals = $request->user()
            ->withdrawals()
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => WithdrawalResource::collection($withdrawals)->response()->getData(true),
        ]);
    }

    /**
     * POST /api/withdrawals
     * Create a new withdrawal request.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'notes'  => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $withdrawal = $request->user()->withdrawals()->create([
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Withdrawal request submitted',
            'data'    => new WithdrawalResource($withdrawal),
        ], 201);
    }

    /**
     * GET /api/withdrawals/{withdrawal}
     * Show a single withdrawal owned by the authenticated user.
     */
    public function show(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        if ($withdrawal->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new WithdrawalResource($withdrawal),
        ]);
    }
}
