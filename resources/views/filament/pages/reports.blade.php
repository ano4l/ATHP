<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Date Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <form wire:submit.prevent="$refresh" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                    <input type="date" wire:model="dateFrom"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                    <input type="date" wire:model="dateTo"
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
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Requisitions</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $summary['overall_count'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Amount</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($summary['overall_total'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Queue</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ $summary['pending_count'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">In Approved Pipeline</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $summary['approved_pipeline_count'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fulfilled</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $summary['fulfilled_count'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Approval (hrs)</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($summary['avg_approval_turnaround'] ?? 0, 2) }}</p>
            </div>
        </div>

        {{-- Status Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Status Breakdown</h3>
            @php
                $statusBreakdown = $summary['status_breakdown'] ?? [];
            @endphp

            @if(count($statusBreakdown) === 0)
                <p class="text-sm text-gray-400">No status data in selected period.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($statusBreakdown as $status => $count)
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                            {{ \Illuminate\Support\Str::of($status)->replace('_', ' ')->title() }}: {{ $count }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">By Category</h3>
                @if(count($byCategory) === 0)
                    <p class="text-sm text-gray-400 text-center py-8">No data available</p>
                @else
                    <canvas id="categoryChart" height="250"></canvas>
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

        {{-- Spend by Project --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Spend by Project (Top 15)</h3>
                @if(count($byProject) === 0)
                    <p class="text-sm text-gray-400 text-center py-8">No data available</p>
                @else
                    <canvas id="projectChart" height="300"></canvas>
                @endif
            </div>

            {{-- Requisition Aging --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Requisition Aging (Open Items)</h3>
                @if(count(array_filter($aging, fn ($a) => $a['count'] > 0)) === 0)
                    <p class="text-sm text-gray-400 text-center py-8">No open requisitions</p>
                @else
                    <canvas id="agingChart" height="300"></canvas>
                @endif
            </div>
        </div>

        {{-- Outstanding Requisitions Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Outstanding Requisitions</h3>
            @if(count($outstanding) === 0)
                <p class="text-sm text-gray-400">No outstanding requisitions.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-3 py-2">Reference</th>
                                <th class="px-3 py-2">Requester</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Days Open</th>
                                <th class="px-3 py-2">Needed By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($outstanding as $item)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="px-3 py-2 font-medium">{{ $item['reference_no'] }}</td>
                                    <td class="px-3 py-2">{{ $item['requester'] }}</td>
                                    <td class="px-3 py-2">{{ $item['amount'] }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                            {{ $item['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 {{ $item['days_open'] > 14 ? 'text-red-600 font-semibold' : '' }}">{{ $item['days_open'] }}</td>
                                    <td class="px-3 py-2">{{ $item['needed_by'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const COLORS = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
        let chartInstances = {};

        function destroyCharts() {
            Object.values(chartInstances).forEach(c => c.destroy());
            chartInstances = {};
        }

        function initCharts() {
            destroyCharts();

            const byBranch = @json($byBranch);
            const byType = @json($byType);
            const byCategory = @json($byCategory);
            const byProject = @json($byProject);
            const aging = @json($aging);
            const overTime = @json($overTime);

            if (byBranch.length > 0 && document.getElementById('branchChart')) {
                chartInstances.branch = new Chart(document.getElementById('branchChart'), {
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

            if (byType.length > 0 && document.getElementById('typeChart')) {
                chartInstances.type = new Chart(document.getElementById('typeChart'), {
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

            if (byCategory.length > 0 && document.getElementById('categoryChart')) {
                chartInstances.category = new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: {
                        labels: byCategory.map(d => d.category),
                        datasets: [{
                            label: 'Count',
                            data: byCategory.map(d => d.count),
                            backgroundColor: '#10b981',
                            borderRadius: 4
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            }

            if (byProject.length > 0 && document.getElementById('projectChart')) {
                chartInstances.project = new Chart(document.getElementById('projectChart'), {
                    type: 'bar',
                    data: {
                        labels: byProject.map(d => d.project),
                        datasets: [{
                            label: 'Total Spend',
                            data: byProject.map(d => d.total),
                            backgroundColor: '#8b5cf6',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true } }
                    }
                });
            }

            const agingWithData = aging.filter(d => d.count > 0);
            if (agingWithData.length > 0 && document.getElementById('agingChart')) {
                const agingColors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#991b1b'];
                chartInstances.aging = new Chart(document.getElementById('agingChart'), {
                    type: 'bar',
                    data: {
                        labels: aging.map(d => d.bucket),
                        datasets: [{
                            label: 'Requisitions',
                            data: aging.map(d => d.count),
                            backgroundColor: agingColors,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }

            if (overTime.length > 0 && document.getElementById('timeChart')) {
                chartInstances.time = new Chart(document.getElementById('timeChart'), {
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
        }

        document.addEventListener('DOMContentLoaded', initCharts);
        document.addEventListener('livewire:navigated', initCharts);
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', () => { setTimeout(initCharts, 100); });
        }
    </script>
</x-filament-panels::page>
