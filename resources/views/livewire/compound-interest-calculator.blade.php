<?php

use Livewire\Volt\Component;
use App\Models\CalculatorUsageLog;

new class extends Component {
    public function logCalculation($calculationData)
    {
        try {
            CalculatorUsageLog::create([
                'user_id' => auth()->id(),
                'calculator_type' => 'compound_interest',
                'invested_amount' => $calculationData['initial_investment'] ?? null,
                'first_reward' => $calculationData['first_reward'] ?? null,
                'signals_per_day' => $calculationData['signals_per_day'] ?? null,
                'number_of_days' => $calculationData['days'] ?? null,
                'is_first_time' => $calculationData['is_first_time'] ?? false,
                'final_amount' => $calculationData['final_amount'] ?? null,
                'calculation_data' => $calculationData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Calculator usage log failed: ' . $e->getMessage());
        }
    }
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

        <!-- Mobile drawer trigger -->
        <div class="sm:hidden mb-3">
            <button id="openDrawer" class="px-3 py-2 bg-indigo-600 text-white rounded">Open Form</button>
        </div>

        <!-- Form (inline on desktop, drawer on mobile) -->
        <div id="drawer" class="fixed inset-0 z-40 transform translate-y-full transition-transform duration-300 sm:hidden">
            <div class="absolute inset-0 bg-black/40" id="drawerBackdrop"></div>
            <div class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-900 p-4 rounded-t-lg shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-base font-semibold">Calculator</h3>
                    <button id="closeDrawer" class="text-sm px-2 py-1">Close</button>
                </div>

                <form id="calcFormDrawer" class="grid grid-cols-1 gap-3 mb-2" onsubmit="return false;">
                    <label class="flex flex-col text-base">
                        <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">Invested Amount or Current Assets</span>
                        <input id="investedDrawer" type="number" step="0.01" min="0" value="1000"
                            class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring text-base" />
                    </label>

                    <label class="flex flex-col text-base">
                        <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">First Recharge Reward (this is for new invite)</span>
                        <input id="firstRewardDrawer" type="number" step="0.01" min="0" value="0"
                            class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring text-base" />
                    </label>

                    <label class="flex flex-col text-base">
                        <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">Signals per Day</span>
                        <input id="signalsPerDayDrawer" type="number" min="1" step="1" value="2"
                            class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring text-base" />
                    </label>

                    <label class="flex flex-col text-base">
                        <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">Number of Days</span>
                        <input id="daysDrawer" type="number" min="1" step="1" value="30"
                            class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring text-base" />
                    </label>

                    <label class="flex items-center space-x-2 text-base">
                        <input id="firstTimeToggleDrawer" type="checkbox" class="h-4 w-4" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">First Time Investor</span>
                    </label>

                    <div class="mt-3 flex items-center justify-end space-x-2">
                        <button id="computeBtnDrawer" class="px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-500">Calculate</button>
                        <button id="exportBtnDrawer" class="px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-500">Export</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inline form for larger screens -->
        <form id="calcForm" class="hidden sm:grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4" onsubmit="return false;">
            <label class="flex flex-col text-base">
                <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">Invested Amount or Current Assets</span>
                <input id="invested" type="number" step="0.01" min="0" value="1000"
                    class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring text-base" />
            </label>

            <label class="flex flex-col text-base">
                <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">First Recharge Reward (this is for new invite)</span>
                <input id="firstReward" type="number" step="0.01" min="0" value="0"
                    class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring text-base" />
            </label>

            <label class="flex flex-col text-base">
                <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">Signals per Day</span>
                <input id="signalsPerDay" type="number" min="1" step="1" value="2"
                    class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring text-base" />
            </label>

            <label class="flex flex-col text-base">
                <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">Number of Days</span>
                <input id="days" type="number" min="1" step="1" value="30"
                    class="px-3 py-2 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring text-base" />
            </label>

            <label class="flex items-center space-x-2 text-base">
                <input id="firstTimeToggle" type="checkbox" class="h-4 w-4" />
                <span class="text-sm text-gray-600 dark:text-gray-300">First Time Investor</span>
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

        <!-- Custom Confirmation Modal -->
        <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4 transform transition-all">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Calculate Compound Interest</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Proceed with calculation?</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Investment:</span>
                            <span class="font-semibold text-gray-900 dark:text-white ml-1" id="modalInvested"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">First Reward:</span>
                            <span class="font-semibold text-gray-900 dark:text-white ml-1" id="modalFirstReward"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Signals/Day:</span>
                            <span class="font-semibold text-gray-900 dark:text-white ml-1" id="modalSignals"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Days:</span>
                            <span class="font-semibold text-gray-900 dark:text-white ml-1" id="modalDays"></span>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button id="confirmCancel" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-colors">
                        Cancel
                    </button>
                    <button id="confirmCalculate" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition-colors shadow-lg shadow-indigo-500/50">
                        Calculate
                    </button>
                </div>
            </div>
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
                // Element helpers that prefer inline form but fall back to drawer
                function el(id) { return document.getElementById(id) || document.getElementById(id + 'Drawer'); }

                const investedEl = el('invested');
                const firstRewardEl = el('firstReward');
                const signalsPerDayEl = el('signalsPerDay');
                const daysEl = el('days');
                const computeBtn = document.getElementById('computeBtn');
                // create export button if missing
                let exportBtn = document.getElementById('exportBtn');
                if (!exportBtn) {
                    exportBtn = document.createElement('button');
                    exportBtn.id = 'exportBtn';
                    exportBtn.className = 'px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-500 ml-2';
                    exportBtn.textContent = 'Export to Excel';
                    // append near compute button
                    const computeParent = document.getElementById('computeBtn').parentElement;
                    if (computeParent) computeParent.appendChild(exportBtn);
                }
                const results = document.getElementById('results');
                const openDrawer = document.getElementById('openDrawer');
                const closeDrawer = document.getElementById('closeDrawer');
                const drawer = document.getElementById('drawer');
                const drawerBackdrop = document.getElementById('drawerBackdrop');
                const firstTimeToggle = document.getElementById('firstTimeToggle');
                const firstTimeToggleDrawer = document.getElementById('firstTimeToggleDrawer');

                // Confirmation modal elements
                const confirmModal = document.getElementById('confirmModal');
                const confirmCancel = document.getElementById('confirmCancel');
                const confirmCalculate = document.getElementById('confirmCalculate');
                const modalInvested = document.getElementById('modalInvested');
                const modalFirstReward = document.getElementById('modalFirstReward');
                const modalSignals = document.getElementById('modalSignals');
                const modalDays = document.getElementById('modalDays');

                let pendingCalculation = null;

                // viewport helpers to toggle desktop vs mobile controls
                function isMobileViewport() {
                    return window.matchMedia('(max-width: 639px)').matches;
                }

                function updateControlVisibility() {
                    const desktopCompute = document.getElementById('computeBtn');
                    const desktopExport = document.getElementById('exportBtn');
                    const drawerCompute = document.getElementById('computeBtnDrawer');
                    const drawerExport = document.getElementById('exportBtnDrawer');

                    if (isMobileViewport()) {
                        if (desktopCompute) desktopCompute.style.display = 'none';
                        if (desktopExport) desktopExport.style.display = 'none';
                        if (drawerCompute) drawerCompute.style.display = '';
                        if (drawerExport) drawerExport.style.display = '';
                    } else {
                        if (desktopCompute) desktopCompute.style.display = '';
                        if (desktopExport) desktopExport.style.display = '';
                        if (drawerCompute) drawerCompute.style.display = 'none';
                        if (drawerExport) drawerExport.style.display = 'none';
                    }
                }

                // initial visibility and resize listener
                updateControlVisibility();
                window.addEventListener('resize', updateControlVisibility);

                // Sync drawer and inline inputs when both present
                function sync(id) {
                    const a = document.getElementById(id);
                    const b = document.getElementById(id + 'Drawer');
                    if (!a || !b) return;
                    a.addEventListener('input', () => b.value = a.value);
                    b.addEventListener('input', () => a.value = b.value);
                }

                ['invested','firstReward','signalsPerDay','days'].forEach(sync);
                // Sync toggles
                if (firstTimeToggle && firstTimeToggleDrawer) {
                    firstTimeToggle.addEventListener('change', ()=> firstTimeToggleDrawer.checked = firstTimeToggle.checked);
                    firstTimeToggleDrawer.addEventListener('change', ()=> firstTimeToggle.checked = firstTimeToggleDrawer.checked);
                }

                // when first-time toggle changes adjust signalsPerDay UI
                function updateFirstTimeUI() {
                    const ft = (firstTimeToggle && firstTimeToggle.checked) || (firstTimeToggleDrawer && firstTimeToggleDrawer.checked);
                    // force default 2 signals when first time enabled
                    if (ft) {
                        if (signalsPerDayEl) signalsPerDayEl.value = 2;
                        const drawerSignals = document.getElementById('signalsPerDayDrawer');
                        if (drawerSignals) drawerSignals.value = 2;
                        // disable both inputs to avoid confusion
                        if (signalsPerDayEl) signalsPerDayEl.setAttribute('disabled','true');
                        if (drawerSignals) drawerSignals.setAttribute('disabled','true');
                    } else {
                        // re-enable
                        if (signalsPerDayEl) signalsPerDayEl.removeAttribute('disabled');
                        const drawerSignals = document.getElementById('signalsPerDayDrawer');
                        if (drawerSignals) drawerSignals.removeAttribute('disabled');
                    }
                }

                if (firstTimeToggle) firstTimeToggle.addEventListener('change', updateFirstTimeUI);
                if (firstTimeToggleDrawer) firstTimeToggleDrawer.addEventListener('change', updateFirstTimeUI);

                // Drawer open/close
                if (openDrawer) openDrawer.addEventListener('click', () => { drawer.classList.remove('translate-y-full'); drawer.classList.add('translate-y-0'); });
                if (closeDrawer) closeDrawer.addEventListener('click', () => { drawer.classList.add('translate-y-full'); drawer.classList.remove('translate-y-0'); });
                if (drawerBackdrop) drawerBackdrop.addEventListener('click', () => { drawer.classList.add('translate-y-full'); drawer.classList.remove('translate-y-0'); });

                // Wire drawer compute/export buttons to main handlers (mobile)
                const computeBtnDrawer = document.getElementById('computeBtnDrawer');
                if (computeBtnDrawer) computeBtnDrawer.addEventListener('click', onCalculate);
                const exportBtnDrawer = document.getElementById('exportBtnDrawer');
                if (exportBtnDrawer) exportBtnDrawer.addEventListener('click', exportExcel);

                function formatAmount(n) {
                    if (!isFinite(n)) return '-';
                    return Number(n).toLocaleString(undefined, {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 2
                    });
                }

                function randRate() {
                    return 0.50 + Math.random() * 0.02;
                }

                // Show custom confirmation modal
                function onCalculate() {
                    const invested = parseFloat(investedEl.value) || 0;
                    const firstReward = parseFloat(firstRewardEl.value) || 0;
                    const defaultSignals = Math.max(1, parseInt(signalsPerDayEl.value) || 2);
                    const totalDays = Math.max(1, parseInt(daysEl.value) || 30);

                    // Update modal with values
                    modalInvested.textContent = formatAmount(invested);
                    modalFirstReward.textContent = formatAmount(firstReward);
                    modalSignals.textContent = defaultSignals;
                    modalDays.textContent = totalDays;

                    // Store calculation parameters
                    pendingCalculation = { invested, firstReward, defaultSignals, totalDays };

                    // Show modal
                    confirmModal.classList.remove('hidden');
                    confirmModal.classList.add('flex');
                }

                // Handle modal cancel
                if (confirmCancel) {
                    confirmCancel.addEventListener('click', () => {
                        confirmModal.classList.add('hidden');
                        confirmModal.classList.remove('flex');
                        pendingCalculation = null;
                    });
                }

                // Handle modal confirm - proceed with calculation
                if (confirmCalculate) {
                    confirmCalculate.addEventListener('click', () => {
                        confirmModal.classList.add('hidden');
                        confirmModal.classList.remove('flex');
                        if (pendingCalculation) {
                            compute(pendingCalculation.invested, pendingCalculation.firstReward, pendingCalculation.defaultSignals, pendingCalculation.totalDays, true);
                            pendingCalculation = null;
                        }
                    });
                }

                // Close modal on backdrop click
                if (confirmModal) {
                    confirmModal.addEventListener('click', (e) => {
                        if (e.target === confirmModal) {
                            confirmModal.classList.add('hidden');
                            confirmModal.classList.remove('flex');
                            pendingCalculation = null;
                        }
                    });
                }

                function compute(invested = null, firstReward = null, defaultSignals = null, totalDays = null, shouldLog = false) {
                    // If parameters not provided, read from inputs
                    if (invested === null) invested = parseFloat(investedEl.value) || 0;
                    if (firstReward === null) firstReward = parseFloat(firstRewardEl.value) || 0;
                    if (defaultSignals === null) defaultSignals = Math.max(1, parseInt(signalsPerDayEl.value) || 2);
                    if (totalDays === null) totalDays = Math.max(1, parseInt(daysEl.value) || 30);

                    const firstTime = (firstTimeToggle && firstTimeToggle.checked) || (firstTimeToggleDrawer && firstTimeToggleDrawer.checked);

                    // decide maximum columns (we'll render up to 5 signals for flexibility)
                    const maxSignals = firstTime ? 5 : defaultSignals;

                    let tableHtml = `<table class="min-w-full table-auto text-base compact-table">`;
                    tableHtml += '<thead><tr class="text-left text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">';
                    tableHtml += '<th class="px-2 py-2">Day</th>';
                    tableHtml += '<th class="px-2 py-2 small">Total Assets<br>Start of Day</th>';

                    for (let s = 1; s <= maxSignals; s++) {
                        tableHtml += `<th class="px-2 py-2 small signal-column">Signal ${s}<br><span class="text-xs">(1% × rate)</span></th>`;
                        tableHtml += `<th class="px-2 py-2 small">After Signal ${s}</th>`;
                    }

                    tableHtml += '<th class="px-2 py-2 small">End of Day</th>';
                    tableHtml += '</tr></thead><tbody>';

                    let currentAssets = invested + firstReward;
                    let totalGain = 0;

                    for (let day = 1; day <= totalDays; day++) {
                        // determine signals for this day
                        let daySignals;
                        if (firstTime) {
                            // First Time Investor: Day1 => 2, Day2-6 => 5, Day7+ => 2
                            if (day === 1) daySignals = 2;
                            else if (day >= 2 && day <= 6) daySignals = 5;
                            else daySignals = 2;
                        } else {
                            daySignals = defaultSignals;
                        }

                        let row = `<tr class="border-b border-gray-100 dark:border-gray-800">`;
                        row += `<td class="px-2 py-2 font-medium text-center">${day}</td>`;
                        const dayStartAssets = currentAssets;
                        row += `<td class="px-2 py-2 small text-right">${formatAmount(dayStartAssets)}</td>`;

                        let dailyGain = 0;

                        for (let s = 1; s <= maxSignals; s++) {
                            if (s <= daySignals) {
                                const signalAmount = currentAssets * 0.01;
                                const rate = randRate();
                                const gain = signalAmount * rate;
                                dailyGain += gain;

                                row += `<td class="px-2 py-2 small text-center cell-content bg-gain">
                                    <div class="font-semibold">${(rate * 100).toFixed(2)}%</div>
                                    <div class="text-xs">
                                        <div>1% of: ${formatAmount(signalAmount)}</div>
                                        <div class="text-green-600 dark:text-green-400">Gain: +${formatAmount(gain)}</div>
                                    </div>
                                </td>`;

                                currentAssets += gain;

                                row += `<td class="px-2 py-2 small text-right cell-content">${formatAmount(currentAssets)}</td>`;
                            } else {
                                // empty cells to keep columns aligned
                                row += `<td class="px-2 py-2 small text-center">-</td>`;
                                row += `<td class="px-2 py-2 small text-right">-</td>`;
                            }
                        }

                        totalGain += dailyGain;

                        row += `<td class="px-2 py-2 small text-right font-semibold bg-gray-50 dark:bg-gray-800/30">
                            ${formatAmount(currentAssets)}
                            <div class="text-xs text-green-600 dark:text-green-400 font-normal">
                                +${formatAmount(dailyGain)}
                            </div>
                        </td>`;

                        row += `</tr>`;

                        const dayGainPercent = (dailyGain / dayStartAssets) * 100;
                        row += `<tr class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800/50">
                            <td colspan="${3 + (maxSignals * 2)}" class="px-2 py-1 text-right">
                                Day ${day} Total Gain: <span class="text-green-600 dark:text-green-400 font-semibold">+${formatAmount(dailyGain)}</span> (${dayGainPercent.toFixed(3)}%)
                            </td>
                            <td class="px-2 py-1"></td>
                        </tr>`;

                        tableHtml += row;
                    }

                    tableHtml += '</tbody></table>';

                    // render table
                    results.innerHTML = tableHtml;

                    const cells = results.querySelectorAll('.cell-content');
                    cells.forEach((c, idx) => {
                        c.classList.add('pulse-update');
                        setTimeout(() => c.classList.remove('pulse-update'), 800 + (idx % 5) * 50);
                    });

                    // summary
                    const initialInvestment = invested + firstReward;
                    const totalGainPercent = (totalGain / initialInvestment) * 100;

                    const summary = document.createElement('div');
                    summary.className = 'mt-4 p-4 bg-gradient-to-r from-indigo-50 to-green-50 dark:from-indigo-900/20 dark:to-green-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800';
                    summary.innerHTML = `
                        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">Investment Summary</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-base">
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
                    `;
                    results.appendChild(summary);

                    // Log the calculation to database only if confirmed by user
                    if (shouldLog) {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                            || document.querySelector('input[name="_token"]')?.value
                            || '{{ csrf_token() }}';

                        fetch('{{ route("calculator.log") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                initial_investment: invested,
                                first_reward: firstReward,
                                signals_per_day: defaultSignals,
                                days: totalDays,
                                is_first_time: firstTime,
                                final_amount: currentAssets,
                                total_gain: totalGain,
                                total_gain_percent: totalGainPercent,
                                max_signals_used: maxSignals
                            })
                        }).catch(e => console.log('Logging skipped:', e.message));
                    }
                }

                if (computeBtn) computeBtn.addEventListener('click', onCalculate);

                // Export to Excel (SpreadsheetML) with formulas so users can tweak inputs
                function colLetter(n) {
                    let s = '';
                    while (n > 0) { let m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); }
                    return s;
                }

                function exportExcel() {
                    const invested = parseFloat(investedEl.value) || 0;
                    const firstReward = parseFloat(firstRewardEl.value) || 0;
                    const defaultSignals = Math.max(1, parseInt(signalsPerDayEl.value) || 2);
                    const totalDays = Math.max(1, parseInt(daysEl.value) || 30);
                    const firstTime = (firstTimeToggle && firstTimeToggle.checked) || (firstTimeToggleDrawer && firstTimeToggleDrawer.checked);

                    const params = new URLSearchParams({
                        invested: invested,
                        firstReward: firstReward,
                        signals: defaultSignals,
                        days: totalDays,
                        firstTime: firstTime ? '1' : '0',
                    });

                    // Use GET to trigger download (avoids CSRF 419 issues in some setups)
                    const url = '{{ route("compound-calculator.export") }}' + '?' + params.toString();
                    window.location.href = url;
                }

                if (exportBtn) exportBtn.addEventListener('click', exportExcel);

                // initialize - run compute on load to show default calculation (without logging)
                compute(null, null, null, null, false);
            })();
        </script>
    </div>
</div>
