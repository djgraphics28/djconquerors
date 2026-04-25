<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RiscoinLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiRiscoinLinkController extends Controller
{
    /**
     * GET /api/riscoin-links
     * Return all active RisCoin referral links.
     */
    public function index(): JsonResponse
    {
        $links = RiscoinLink::where('is_active', true)
            ->select('id', 'url', 'is_active', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $links,
        ]);
    }
}
