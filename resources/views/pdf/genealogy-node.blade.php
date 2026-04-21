@php
    $user      = $node['user'];
    $level     = $node['level'];
    $children  = $node['children'];
    $isActive  = (bool) $user->is_active;
    $levelPillClass = match($level) {
        0 => 'lvl-0',
        1 => 'lvl-1',
        2 => 'lvl-2',
        3 => 'lvl-3',
        4 => 'lvl-4',
        default => 'lvl-other',
    };
@endphp

<div class="node-row">
    {{-- ── Node card ── --}}
    <table cellspacing="0" cellpadding="0" style="margin-bottom:3px;">
        <tr>
            <td style="vertical-align:middle; padding-right:4px;">
                {{-- Connector dot --}}
                @if(!$isRoot)
                    <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#94a3b8; vertical-align:middle; margin-right:4px;"></span>
                @endif
            </td>
            <td>
                <div class="node-card {{ $isRoot ? 'root' : '' }} {{ $isActive ? 'active' : 'inactive' }}">
                    <div>
                        <span class="level-pill {{ $levelPillClass }}">L{{ $level }}</span>
                        <span class="node-name">{{ $user->name }}</span>
                    </div>
                    <div class="node-id">{{ $user->riscoin_id ?? '—' }}</div>
                    <div class="node-amount">${{ number_format($user->invested_amount ?? 0, 2) }}</div>
                    <table cellspacing="0" cellpadding="0" style="margin-top:2px; width:100%;">
                        <tr>
                            <td>
                                <span class="node-status {{ $isActive ? 'status-active' : 'status-inactive' }}">
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            @if(count($children) > 0)
                            <td style="text-align:right;">
                                <span class="node-direct">{{ count($children) }} direct</span>
                            </td>
                            @endif
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Children (recursive) ── --}}
    @if(count($children) > 0)
        <div class="tree-indent">
            @foreach($children as $child)
                @include('pdf.genealogy-node', ['node' => $child, 'isRoot' => false])
            @endforeach
        </div>
    @endif
</div>
