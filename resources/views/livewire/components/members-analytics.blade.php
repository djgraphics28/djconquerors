<div class="mb-6 bg-white dark:bg-gray-800 rounded-lg p-4 border">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Members Analytics</h3>
        <div class="flex items-center space-x-2">
            <button wire:click.prevent="setFilter('today')" class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200">Today</button>
            <button wire:click.prevent="setFilter('last_week')" class="px-2 py-1 text-sm rounded bg-indigo-600 text-white">Last Week</button>
            <button wire:click.prevent="setFilter('last_month')" class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200">Last Month</button>
        </div>
    </div>

    <div class="flex items-center gap-3 mb-4">
        <label class="text-sm text-gray-600 dark:text-gray-300">From</label>
        <select wire:model="fromMonth" class="border rounded px-2 py-1 text-sm">
            @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}">{{ \Carbon\Carbon::create(0, $m, 1)->format('F') }}</option>
            @endfor
        </select>
        <label class="text-sm text-gray-600 dark:text-gray-300">To</label>
        <select wire:model="toMonth" class="border rounded px-2 py-1 text-sm">
            @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}">{{ \Carbon\Carbon::create(0, $m, 1)->format('F') }}</option>
            @endfor
        </select>
        <div class="text-sm text-gray-500">Year: {{ $year }}</div>
    </div>

    <canvas id="membersChart" height="120"></canvas>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let ctx = document.getElementById('membersChart').getContext('2d');
            window.membersChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Members Joined',
                        data: [],
                        borderColor: 'rgba(59,130,246,1)',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        fill: true,
                        tension: 0.2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { x: { display: true }, y: { beginAtZero: true, precision: 0 } }
                }
            });

            window.addEventListener('membersDataUpdated', function(e) {
                const detail = e.detail || e;
                window.membersChart.data.labels = detail.labels || [];
                window.membersChart.data.datasets[0].data = detail.values || [];
                window.membersChart.update();
            });
        });
    </script>
</div>
