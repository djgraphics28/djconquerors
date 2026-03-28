<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Team Export</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; padding: 24px; }
        h1 { font-size: 18px; font-weight: 700; margin-bottom: 4px; color: #111827; }
        p.meta { font-size: 10px; color: #6b7280; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #1e40af; color: #fff; }
        thead th { padding: 9px 12px; text-align: left; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:nth-child(odd) { background: #ffffff; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .level-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; background: #dbeafe; color: #1d4ed8; }
        .no-manager { color: #9ca3af; font-style: italic; }
        .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>
    <h1>Team Member Export</h1>
    <p class="meta">Generated: {{ now()->format('F j, Y g:i A') }} &nbsp;&bull;&nbsp; Total records: {{ count($rows) }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Riscoin ID</th>
                <th>Manager Level</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['riscoin_id'] }}</td>
                    <td>
                        @if ($row['manager_level'] === 'not yet manager')
                            <span class="no-manager">not yet manager</span>
                        @else
                            <span class="level-badge">{{ $row['manager_level'] }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">Sorted by highest manager level to lowest</p>
</body>
</html>
