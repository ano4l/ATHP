<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Date Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <form wire:submit.prevent="$refresh" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                    <input type="date" wire:model.defer="dateFrom"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                    <input type="date" wire:model.defer="dateTo"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <button type="submit"
                        class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">
                    Apply
                </button>
                <button type="button" wire:click="exportCsv"
                        class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Export CSV
                </button>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Requisitions</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $summary['overall']['count'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Amount</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($summary['overall']['total'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Approved</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $summary['approved']['count'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ $summary['submitted']['count'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">By Branch</h3>
                @if(count($byBranch) === 0)
                    <p class="text-sm text-gray-400 text-center py-8">No data available</p>
                @else
                    <canvas id="branchChart" height="250"></canvas>
                @endif
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">By Type</h3>
                @if(count($byType) === 0)
                    <p class="text-sm text-gray-400 text-center py-8">No data available</p>
                @else
                    <canvas id="typeChart" height="250"></canvas>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Requisitions Over Time</h3>
            @if(count($overTime) === 0)
                <p class="text-sm text-gray-400 text-center py-8">No data available</p>
            @else
                <canvas id="timeChart" height="120"></canvas>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const COLORS = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
            const byBranch = @json($byBranch);
            const byType = @json($byType);
            const overTime = @json($overTime);

            if (byBranch.length > 0) {
                new Chart(document.getElementById('branchChart'), {
                    type: 'bar',
                    data: {
                        labels: byBranch.map(d => d.branch),
                        datasets: [{
                            label: 'Count',
                            data: byBranch.map(d => d.count),
                            backgroundColor: '#3b82f6',
                            borderRadius: 4
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            }

            if (byType.length > 0) {
                new Chart(document.getElementById('typeChart'), {
                    type: 'doughnut',
                    data: {
                        labels: byType.map(d => d.type),
                        datasets: [{
                            data: byType.map(d => d.count),
                            backgroundColor: COLORS.slice(0, byType.length)
                        }]
                    },
                    options: { responsive: true }
                });
            }

            if (overTime.length > 0) {
                new Chart(document.getElementById('timeChart'), {
                    type: 'line',
                    data: {
                        labels: overTime.map(d => d.month),
                        datasets: [
                            { label: 'Count', data: overTime.map(d => d.count), borderColor: '#3b82f6', tension: 0.3, fill: false },
                            { label: 'Amount', data: overTime.map(d => d.total), borderColor: '#10b981', tension: 0.3, fill: false, yAxisID: 'y1' }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Count' } },
                            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Amount' } }
                        }
                    }
                });
            }
        });
    </script>
</x-filament-panels::page>
