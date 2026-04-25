<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CalculatorUsageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiCalculatorController extends Controller
{
    /**
     * POST /api/calculator/calculate
     * Run a compound calculation and log it.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'initial_investment'    => 'required|numeric|min:0',
            'monthly_interest_rate' => 'required|numeric|min:0|max:100',
            'number_of_months'      => 'required|integer|min:1|max:360',
            'compounding_frequency' => 'required|in:monthly,quarterly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $principal   = (float) $request->initial_investment;
        $rate        = (float) $request->monthly_interest_rate / 100;
        $months      = (int) $request->number_of_months;
        $frequency   = $request->compounding_frequency;

        // Determine compounding period (months per compound)
        $periodMonths = $frequency === 'quarterly' ? 3 : 1;

        $breakdown    = [];
        $balance      = $principal;

        for ($month = 1; $month <= $months; $month++) {
            if ($month % $periodMonths === 0) {
                $interest = $balance * $rate * $periodMonths;
                $balance += $interest;
            } else {
                $interest = 0;
            }

            $breakdown[] = [
                'month'    => $month,
                'interest' => round($interest, 2),
                'balance'  => round($balance, 2),
            ];
        }

        $finalAmount   = round($balance, 2);
        $totalInterest = round($finalAmount - $principal, 2);

        // Log the usage
        $isFirstTime = ! CalculatorUsageLog::where('user_id', $request->user()->id)->exists();

        CalculatorUsageLog::create([
            'user_id'          => $request->user()->id,
            'calculator_type'  => 'compound',
            'invested_amount'  => $principal,
            'final_amount'     => $finalAmount,
            'is_first_time'    => $isFirstTime,
            'calculation_data' => $request->all(),
            'ip_address'       => $request->ip(),
            'user_agent'       => substr($request->userAgent() ?? '', 0, 255),
        ]);

        return response()->json([
            'status' => true,
            'data'   => [
                'initial_investment' => $principal,
                'final_amount'       => $finalAmount,
                'total_interest'     => $totalInterest,
                'number_of_months'   => $months,
                'breakdown'          => $breakdown,
            ],
        ]);
    }
}
