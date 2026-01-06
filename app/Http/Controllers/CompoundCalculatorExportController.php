<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CompoundCalculatorExport;

class CompoundCalculatorExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            // accept both GET and POST inputs
            $invested = (float) ($request->query('invested', $request->post('invested', 1000)));
            $firstReward = (float) ($request->query('firstReward', $request->post('firstReward', 0)));
            $signals = (int) ($request->query('signals', $request->post('signals', 2)));
            $days = (int) ($request->query('days', $request->post('days', 30)));
            $firstTime = filter_var($request->query('firstTime', $request->post('firstTime', '0')), FILTER_VALIDATE_BOOLEAN);

            
            $export = new CompoundCalculatorExport($invested, $firstReward, $signals, $days, $firstTime);

            \Log::info('Compound export requested', compact('invested','firstReward','signals','days','firstTime'));

            return Excel::download($export, 'compound_calculation.xlsx');
        } catch (\Throwable $e) {
            \Log::error('Compound export failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed', 'message' => $e->getMessage()], 500);
        }
    }
}
