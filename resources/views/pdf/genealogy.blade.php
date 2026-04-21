<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Genealogy – {{ $rootUser->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #1f2937;
            background: #fff;
        }

        /* ── Header ─────────────────────────────────────────── */
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 50%, #7c3aed 100%);
            color: #fff;
            padding: 14px 20px;
            margin-bottom: 12px;
        }
        .header h1 { font-size: 18px; font-weight: 700; margin-bottom: 3px; }
        .header p  { font-size: 10px; opacity: .85; }

        .stats-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .stats-bar td {
            text-align: center;
            padding: 8px 14px;
            border-right: 1px solid #e2e8f0;
        }
        .stats-bar td:last-child { border-right: none; }
        .stats-label { font-size: 7px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
        .stats-value { font-size: 14px; font-weight: 700; color: #2563eb; margin-top: 2px; }

        /* ── Tree node cards ─────────────────────────────────── */
        .node-row {
            margin-bottom: 4px;
        }
        .node-card {
            display: inline-block;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 5px 8px;
            background: #fff;
            min-width: 130px;
            max-width: 200px;
            vertical-align: top;
        }
        .node-card.root {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .node-card.active { border-left: 3px solid #16a34a; }
        .node-card.inactive { border-left: 3px solid #dc2626; }

        .node-name { font-size: 9px; font-weight: 700; color: #1e3a5f; margin-bottom: 1px; }
        .node-id   { font-size: 7.5px; color: #6b7280; font-family: monospace; }
        .node-amount { font-size: 8px; color: #16a34a; font-weight: 600; margin-top: 2px; }
        .node-status {
            display: inline-block;
            font-size: 6.5px;
            padding: 1px 4px;
            border-radius: 3px;
            margin-top: 2px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .status-active   { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fee2e2; color: #b91c1c; }
        .node-direct { font-size: 7px; color: #6366f1; margin-top: 1px; }

        /* ── Indented tree layout ────────────────────────────── */
        .tree-wrapper { padding: 0 4px; }
        .tree-indent { padding-left: 18px; border-left: 1px dashed #cbd5e1; margin-left: 9px; }

        /* ── Level indicator ─────────────────────────────────── */
        .level-pill {
            display: inline-block;
            font-size: 6.5px;
            padding: 1px 5px;
            border-radius: 10px;
            margin-right: 4px;
            font-weight: 700;
            vertical-align: middle;
        }
        .lvl-0 { background: #dbeafe; color: #1d4ed8; }
        .lvl-1 { background: #fef3c7; color: #92400e; }
        .lvl-2 { background: #d1fae5; color: #065f46; }
        .lvl-3 { background: #ede9fe; color: #5b21b6; }
        .lvl-4 { background: #fee2e2; color: #991b1b; }
        .lvl-other { background: #f1f5f9; color: #475569; }

        /* ── Footer ──────────────────────────────────────────── */
        .footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 7.5px;
            color: #9ca3af;
            text-align: center;
        }

        /* ── Page break helpers ──────────────────────────────── */
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    {{-- ── Header ── --}}
    <div class="header">
        <h1>Genealogy Tree — {{ $rootUser->name }}</h1>
        <p>
            Riscoin ID: {{ $rootUser->riscoin_id }}
            &nbsp;|&nbsp; Generated: {{ $generatedAt }}
        </p>
    </div>

    {{-- ── Summary stats ── --}}
    <table class="stats-bar" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div class="stats-label">Root Member</div>
                <div class="stats-value" style="color:#2563eb;">{{ $rootUser->name }}</div>
            </td>
            <td>
                <div class="stats-label">Direct Members</div>
                <div class="stats-value" style="color:#4f46e5;">{{ $nodes[0]['direct_children'] }}</div>
            </td>
            <td>
                <div class="stats-label">Total Downline</div>
                <div class="stats-value" style="color:#4f46e5;">{{ $totalMembers }}</div>
            </td>
            <td>
                <div class="stats-label">Team Capital</div>
                <div class="stats-value" style="color:#16a34a;">${{ number_format($totalInvestment, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- ── Tree (flat BFS list, no recursion) ── --}}
    <div class="tree-wrapper">
        @foreach($nodes as $i => $node)
            @php
                $levelPillClass = match(true) {
                    $node['level'] === 0 => 'lvl-0',
                    $node['level'] === 1 => 'lvl-1',
                    $node['level'] === 2 => 'lvl-2',
                    $node['level'] === 3 => 'lvl-3',
                    $node['level'] === 4 => 'lvl-4',
                    default              => 'lvl-other',
                };
            @endphp
            <div class="node-row" style="padding-left: {{ $node['level'] * 18 }}px;">
                <table cellspacing="0" cellpadding="0" style="margin-bottom:3px;">
                    <tr>
                        <td style="vertical-align:middle; padding-right:4px;">
                            @if($i > 0)
                                <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#94a3b8; vertical-align:middle; margin-right:4px;"></span>
                            @endif
                        </td>
                        <td>
                            <div class="node-card {{ $i === 0 ? 'root' : '' }} {{ $node['is_active'] ? 'active' : 'inactive' }}">
                                <div>
                                    <span class="level-pill {{ $levelPillClass }}">L{{ $node['level'] }}</span>
                                    <span class="node-name">{{ $node['name'] }}</span>
                                </div>
                                <div class="node-id">{{ $node['riscoin_id'] ?? '—' }}</div>
                                <div class="node-amount">${{ number_format($node['invested_amount'], 2) }}</div>
                                <table cellspacing="0" cellpadding="0" style="margin-top:2px; width:100%;">
                                    <tr>
                                        <td>
                                            <span class="node-status {{ $node['is_active'] ? 'status-active' : 'status-inactive' }}">
                                                {{ $node['is_active'] ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        @if($node['direct_children'] > 0)
                                        <td style="text-align:right;">
                                            <span class="node-direct">{{ $node['direct_children'] }} direct</span>
                                        </td>
                                        @endif
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>

    <div class="footer">
        This document is confidential and generated for internal use only.
        &nbsp;|&nbsp; DJ Conquerors &nbsp;|&nbsp; {{ $generatedAt }}
    </div>

</body>
</html>
