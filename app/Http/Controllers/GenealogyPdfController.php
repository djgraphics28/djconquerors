<?php

namespace App\Http\Controllers;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GenealogyPdfController extends Controller
{
    public function generate(Request $request, $riscoinId = null)
    {
        // Large trees need more headroom
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        if ($riscoinId) {
            $rootUser = User::where('riscoin_id', $riscoinId)
                ->select(['id', 'name', 'riscoin_id', 'invested_amount', 'is_active'])
                ->firstOrFail();
        } else {
            $rootUser = User::where('id', Auth::id())
                ->select(['id', 'name', 'riscoin_id', 'invested_amount', 'is_active'])
                ->firstOrFail();
        }

        // BFS flat list — one DB query per level, no recursion, minimal memory
        $nodes = $this->buildFlatTree($rootUser);

        $totalMembers    = count($nodes) - 1;
        $totalInvestment = array_sum(array_column($nodes, 'invested_amount'));

        $pdf = Pdf::loadView('pdf.genealogy', [
            'rootUser'        => $rootUser,
            'nodes'           => $nodes,
            'totalMembers'    => $totalMembers,
            'totalInvestment' => $totalInvestment,
            'generatedAt'     => now()->format('F j, Y g:i A'),
        ])
        ->setPaper('a3', 'landscape')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'defaultFont'          => 'DejaVu Sans',
            'dpi'                  => 96,
        ]);

        return $pdf->stream("genealogy-{$rootUser->riscoin_id}.pdf");
    }

    /**
     * BFS level-by-level traversal.
     * One DB query per depth level — no recursion, no User model objects kept in memory.
     * Returns a flat array of scalar-only rows suitable for @foreach in the PDF view.
     */
    private function buildFlatTree(User $rootUser): array
    {
        $nodes = [[
            'name'            => $rootUser->name,
            'riscoin_id'      => $rootUser->riscoin_id,
            'invested_amount' => (float) ($rootUser->invested_amount ?? 0),
            'is_active'       => (bool) $rootUser->is_active,
            'level'           => 0,
            'direct_children' => 0,
        ]];

        // riscoin_id → array index, so we can back-fill direct_children counts
        $indexMap = [$rootUser->riscoin_id => 0];

        $parentIds = [$rootUser->riscoin_id];
        $level     = 1;

        while (!empty($parentIds)) {
            $children = User::whereIn('inviters_code', $parentIds)
                ->select(['name', 'riscoin_id', 'inviters_code', 'invested_amount', 'is_active'])
                ->orderBy('inviters_code')
                ->orderBy('name')
                ->get();

            if ($children->isEmpty()) {
                break;
            }

            // Update direct_children on the parent rows
            foreach ($children->groupBy('inviters_code') as $parentRiscoinId => $group) {
                if (isset($indexMap[$parentRiscoinId])) {
                    $nodes[$indexMap[$parentRiscoinId]]['direct_children'] = $group->count();
                }
            }

            $nextParentIds = [];
            foreach ($children as $child) {
                $idx = count($nodes);
                $indexMap[$child->riscoin_id] = $idx;
                $nodes[] = [
                    'name'            => $child->name,
                    'riscoin_id'      => $child->riscoin_id,
                    'invested_amount' => (float) ($child->invested_amount ?? 0),
                    'is_active'       => (bool) $child->is_active,
                    'level'           => $level,
                    'direct_children' => 0,
                ];
                $nextParentIds[] = $child->riscoin_id;
            }

            $parentIds = $nextParentIds;
            $level++;
        }

        return $nodes;
    }
}
