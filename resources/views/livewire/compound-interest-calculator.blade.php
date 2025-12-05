<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
    <div id="compound-calculator"
        class="p-4 sm:p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-lg shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg sm:text-xl font-semibold">Compound Interest Calculator</h2>
            <div class="flex items-center space-x-2">
                {{-- <button id="themeToggle" class="px-3 py-1 bg-gray-200 dark:bg-gray-800 rounded text-sm">Toggle
                    Theme</button> --}}
            </div>
        </div>

        <form id="calcForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4" onsubmit="return false;">
            <label class="flex flex-col text-sm">
                <span class="text-xs text-gray-500 dark:text-gray-400 mb-1">Invested Amount</span>
                <input id="invested" type="number" step="0.01" min="0" value="1000"
                    class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring" />
            </label>

            <label class="flex flex-col text-sm">
                <span class="text-xs text-gray-500 dark:text-gray-400 mb-1">First Recharge Reward</span>
                <input id="firstReward" type="number" step="0.01" min="0" value="0"
                    class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring" />
            </label>

            <label class="flex flex-col text-sm">
                <span class="text-xs text-gray-500 dark:text-gray-400 mb-1">Signals per Day</span>
                <input id="signalsPerDay" type="number" min="1" step="1" value="2"
                    class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring" />
            </label>

            <label class="flex flex-col text-sm">
                <span class="text-xs text-gray-500 dark:text-gray-400 mb-1">Number of Days</span>
                <input id="days" type="number" min="1" step="1" value="30"
                    class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring" />
            </label>
        </form>

        <div class="flex items-center justify-between mb-3">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Each signal: 1% of assets × random rate 50-52% (0.50-0.52)
            </div>
            <div>
                <button id="computeBtn"
                    class="px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-500">Compute</button>
            </div>
        </div>

        <div id="results" class="overflow-x-auto">
            <!-- Table will be injected here -->
        </div>

        <style>
            /* lightweight animation for updated cells */
            .pulse-update {
                animation: pulseUpdate 700ms ease-in-out;
            }

            @keyframes pulseUpdate {
                0% {
                    background-color: rgba(99, 102, 241, 0.15);
                }

                50% {
                    background-color: rgba(99, 102, 241, 0.10);
                }

                100% {
                    background-color: transparent;
                }
            }

            /* responsive small text */
            .small {
                font-size: 0.85rem;
            }

            /* compact table styling */
            .compact-table td, .compact-table th {
                padding: 0.25rem 0.5rem;
            }

            .signal-column {
                min-width: 130px;
            }

            .bg-gain {
                background-color: rgba(34, 197, 94, 0.1);
            }

            .dark .bg-gain {
                background-color: rgba(34, 197, 94, 0.15);
            }
        </style>

        <script>
            (function() {
                const investedEl = document.getElementById('invested');
                const firstRewardEl = document.getElementById('firstReward');
                const signalsPerDayEl = document.getElementById('signalsPerDay');
                const daysEl = document.getElementById('days');
                const computeBtn = document.getElementById('computeBtn');
                const results = document.getElementById('results');
                const themeToggle = document.getElementById('themeToggle');

                function formatAmount(n) {
                    if (!isFinite(n)) return '-';
                    return Number(n).toLocaleString(undefined, {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 2
                    });
                }

                function randRate() {
                    // random between 0.50 and 0.52
                    return 0.50 + Math.random() * 0.02;
                }

                function compute() {
                    const invested = parseFloat(investedEl.value) || 0;
                    const firstReward = parseFloat(firstRewardEl.value) || 0;
                    const signalsPerDay = Math.max(1, parseInt(signalsPerDayEl.value) || 2);
                    const totalDays = Math.max(1, parseInt(daysEl.value) || 30);

                    // Start building the table
                    let tableHtml = '<table class="min-w-full table-auto text-sm compact-table">';

                    // Build header
                    tableHtml += '<thead><tr class="text-left text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">';
                    tableHtml += '<th class="px-2 py-2">Day</th>';
                    tableHtml += '<th class="px-2 py-2 small">Total Assets<br>Start of Day</th>';

                    // Add columns for each signal
                    for (let s = 1; s <= signalsPerDay; s++) {
                        tableHtml += `<th class="px-2 py-2 small signal-column">Signal ${s}<br><span class="text-xs">(1% × rate)</span></th>`;
                        tableHtml += `<th class="px-2 py-2 small">After Signal ${s}</th>`;
                    }

                    tableHtml += '<th class="px-2 py-2 small">End of Day</th>';
                    tableHtml += '</tr></thead><tbody>';

                    // Calculate for each day
                    let currentAssets = invested + firstReward;
                    let totalGain = 0;

                    for (let day = 1; day <= totalDays; day++) {
                        let row = `<tr class="border-b border-gray-100 dark:border-gray-800">`;

                        // Day number
                        row += `<td class="px-2 py-2 font-medium text-center">${day}</td>`;

                        // Assets at start of day
                        const dayStartAssets = currentAssets;
                        row += `<td class="px-2 py-2 small text-right">${formatAmount(dayStartAssets)}</td>`;

                        let dailyGain = 0;

                        // Process each signal for the day
                        for (let signalNum = 1; signalNum <= signalsPerDay; signalNum++) {
                            // Take 1% of current assets for this signal
                            const signalAmount = currentAssets * 0.01;

                            // Get random rate between 50-52%
                            const rate = randRate();

                            // Calculate gain (1% of assets × rate)
                            const gain = signalAmount * rate;
                            dailyGain += gain;

                            // Signal column - show details
                            row += `<td class="px-2 py-2 small text-center cell-content bg-gain">
                                <div class="font-semibold">${(rate * 100).toFixed(2)}%</div>
                                <div class="text-xs">
                                    <div>1% of: ${formatAmount(signalAmount)}</div>
                                    <div class="text-green-600 dark:text-green-400">Gain: +${formatAmount(gain)}</div>
                                </div>
                            </td>`;

                            // Update assets after signal (add the gain)
                            currentAssets += gain;

                            // After signal column
                            row += `<td class="px-2 py-2 small text-right cell-content">${formatAmount(currentAssets)}</td>`;
                        }

                        totalGain += dailyGain;

                        // End of day total
                        row += `<td class="px-2 py-2 small text-right font-semibold bg-gray-50 dark:bg-gray-800/30">
                            ${formatAmount(currentAssets)}
                            <div class="text-xs text-green-600 dark:text-green-400 font-normal">
                                +${formatAmount(dailyGain)}
                            </div>
                        </td>`;

                        row += `</tr>`;

                        // Add a summary row for the day
                        const dayGainPercent = (dailyGain / dayStartAssets) * 100;
                        row += `<tr class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800/50">
                            <td colspan="${3 + (signalsPerDay * 2)}" class="px-2 py-1 text-right">
                                Day ${day} Total Gain: <span class="text-green-600 dark:text-green-400 font-semibold">+${formatAmount(dailyGain)}</span> (${dayGainPercent.toFixed(3)}%)
                            </td>
                            <td class="px-2 py-1"></td>
                        </tr>`;

                        tableHtml += row;
                    }

                    tableHtml += '</tbody></table>';
                    results.innerHTML = tableHtml;

                    // Add animation to cells
                    const cells = results.querySelectorAll('.cell-content');
                    cells.forEach((c, idx) => {
                        c.classList.add('pulse-update');
                        setTimeout(() => c.classList.remove('pulse-update'), 800 + (idx % 5) * 50);
                    });

                    // Add final summary
                    const initialInvestment = invested + firstReward;
                    const totalGainPercent = (totalGain / initialInvestment) * 100;

                    const summary = document.createElement('div');
                    summary.className = 'mt-4 p-4 bg-gradient-to-r from-indigo-50 to-green-50 dark:from-indigo-900/20 dark:to-green-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800';
                    summary.innerHTML = `
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Investment Summary</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <div class="p-3 bg-white dark:bg-gray-800 rounded border">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Initial Investment</div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">${formatAmount(initialInvestment)}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-gray-800 rounded border">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Final Amount (Day ${totalDays})</div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">${formatAmount(currentAssets)}</div>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-100 dark:border-green-800">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Total Gain</div>
                                <div class="text-lg font-bold text-green-700 dark:text-green-400">+${formatAmount(totalGain)}</div>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-100 dark:border-green-800">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Total Return %</div>
                                <div class="text-lg font-bold text-green-700 dark:text-green-400">${totalGainPercent.toFixed(2)}%</div>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            Calculation: For each signal, 1% of current assets × (50-52% random rate). Total signals: ${totalDays * signalsPerDay} signals over ${totalDays} days.
                        </div>
                    `;
                    results.appendChild(summary);
                }

                computeBtn.addEventListener('click', compute);

                // Initialize computation on page load
                compute();

                // Theme toggle functionality
                function toggleTheme() {
                    const root = document.getElementById('compound-calculator');
                    root.classList.toggle('dark');
                }
                themeToggle.addEventListener('click', toggleTheme);

            })();
        </script>
    </div>
</div>
