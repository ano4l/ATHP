<?php

namespace App\Filament\Pages;

use App\Enums\RequisitionStatus;
use App\Models\CashRequisition;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.reports';

    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->dateFrom = now()->subYear()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getViewData(): array
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        return [
            'summary' => $this->getSummary($from, $to),
            'byBranch' => $this->getByBranch($from, $to),
            'byType' => $this->getByType($from, $to),
            'byCategory' => $this->getByCategory($from, $to),
            'byProject' => $this->getByProject($from, $to),
            'outstanding' => $this->getOutstanding(),
            'aging' => $this->getAging(),
            'overTime' => $this->getOverTime($from, $to),
        ];
    }

    protected function getSummary(Carbon $from, Carbon $to): array
    {
        $baseQuery = CashRequisition::query()->whereBetween('created_at', [$from, $to]);

        $pendingStatuses = [
            RequisitionStatus::SUBMITTED->value,
            RequisitionStatus::STAGE1_APPROVED->value,
            RequisitionStatus::MODIFICATION_REQUESTED->value,
        ];

        $approvedPipelineStatuses = [
            RequisitionStatus::APPROVED->value,
            RequisitionStatus::PROCESSING->value,
            RequisitionStatus::OUTSTANDING->value,
            RequisitionStatus::PAID->value,
            RequisitionStatus::FULFILLED->value,
            RequisitionStatus::CLOSED->value,
        ];

        $statusBreakdown = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($row): array {
                $key = $this->enumValue($row->status);

                return [$key => (int) $row->count];
            })
            ->toArray();

        return [
            'overall_count' => (clone $baseQuery)->count(),
            'overall_total' => (float) (clone $baseQuery)->sum('amount'),
            'pending_count' => (clone $baseQuery)->whereIn('status', $pendingStatuses)->count(),
            'approved_pipeline_count' => (clone $baseQuery)->whereIn('status', $approvedPipelineStatuses)->count(),
            'fulfilled_count' => (clone $baseQuery)
                ->whereIn('status', [RequisitionStatus::FULFILLED->value, RequisitionStatus::CLOSED->value])
                ->count(),
            'closed_count' => (clone $baseQuery)->where('status', RequisitionStatus::CLOSED->value)->count(),
            'denied_count' => (clone $baseQuery)->where('status', RequisitionStatus::DENIED->value)->count(),
            'avg_approval_turnaround' => round(
                (float) ((clone $baseQuery)
                    ->whereNotNull('approval_turnaround_hours')
                    ->avg('approval_turnaround_hours') ?? 0),
                2
            ),
            'status_breakdown' => $statusBreakdown,
        ];
    }

    protected function getByBranch(Carbon $from, Carbon $to): array
    {
        return CashRequisition::whereBetween('created_at', [$from, $to])
            ->select('branch', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('branch')
            ->get()
            ->map(fn ($r) => [
                'branch' => $this->enumLabel($r->branch),
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->toArray();
    }

    protected function getByType(Carbon $from, Carbon $to): array
    {
        return CashRequisition::whereBetween('created_at', [$from, $to])
            ->select('requisition_type', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('requisition_type')
            ->get()
            ->map(fn ($r) => [
                'type' => $this->enumLabel($r->requisition_type),
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->toArray();
    }

    protected function getByCategory(Carbon $from, Carbon $to): array
    {
        return CashRequisition::whereBetween('created_at', [$from, $to])
            ->select('category', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('category')
            ->get()
            ->map(fn ($r) => [
                'category' => $this->enumLabel($r->category),
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->toArray();
    }

    protected function getByProject(Carbon $from, Carbon $to): array
    {
        return CashRequisition::whereBetween('created_at', [$from, $to])
            ->whereNotNull('project_name')
            ->select('project_name', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('project_name')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'project' => $r->project_name,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->toArray();
    }

    protected function getOutstanding(): array
    {
        $outstandingStatuses = [
            RequisitionStatus::SUBMITTED->value,
            RequisitionStatus::STAGE1_APPROVED->value,
            RequisitionStatus::MODIFICATION_REQUESTED->value,
            RequisitionStatus::APPROVED->value,
            RequisitionStatus::PROCESSING->value,
            RequisitionStatus::OUTSTANDING->value,
        ];

        return CashRequisition::with('requester')
            ->whereIn('status', $outstandingStatuses)
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->map(fn (CashRequisition $r) => [
                'reference_no' => $r->reference_no ?? ('#' . $r->id),
                'requester' => $r->requester?->name ?? '-',
                'amount' => $r->formattedAmount(),
                'status' => $this->enumLabel($r->status),
                'days_open' => (int) $r->created_at->diffInDays(now()),
                'needed_by' => $r->needed_by?->format('d M Y') ?? '-',
            ])
            ->toArray();
    }

    protected function getAging(): array
    {
        $openStatuses = [
            RequisitionStatus::SUBMITTED->value,
            RequisitionStatus::STAGE1_APPROVED->value,
            RequisitionStatus::MODIFICATION_REQUESTED->value,
            RequisitionStatus::APPROVED->value,
            RequisitionStatus::PROCESSING->value,
            RequisitionStatus::OUTSTANDING->value,
            RequisitionStatus::PAID->value,
            RequisitionStatus::FULFILLED->value,
        ];

        $buckets = [
            '0-7 days' => 0,
            '8-14 days' => 0,
            '15-30 days' => 0,
            '31-60 days' => 0,
            '60+ days' => 0,
        ];

        CashRequisition::whereIn('status', $openStatuses)
            ->select('created_at')
            ->get()
            ->each(function (CashRequisition $r) use (&$buckets): void {
                $age = (int) $r->created_at->diffInDays(now());
                match (true) {
                    $age <= 7 => $buckets['0-7 days']++,
                    $age <= 14 => $buckets['8-14 days']++,
                    $age <= 30 => $buckets['15-30 days']++,
                    $age <= 60 => $buckets['31-60 days']++,
                    default => $buckets['60+ days']++,
                };
            });

        return collect($buckets)
            ->map(fn ($count, $bucket) => ['bucket' => $bucket, 'count' => $count])
            ->values()
            ->toArray();
    }

    protected function getOverTime(Carbon $from, Carbon $to): array
    {
        return CashRequisition::whereBetween('created_at', [$from, $to])
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => $r->month, 'count' => $r->count, 'total' => (float) $r->total])
            ->toArray();
    }

    public function exportCsv(): StreamedResponse
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        $data = CashRequisition::with('requester')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Reference No',
                'Branch',
                'Requisition Type',
                'Category',
                'Requisition For',
                'Project Name',
                'Amount',
                'Actual Amount',
                'Currency',
                'Status',
                'Requester',
                'Submitted At',
                'Approved At',
                'Closed At',
                'Created At',
            ]);
            foreach ($data as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->reference_no,
                    $this->enumLabel($r->branch),
                    $this->enumLabel($r->requisition_type),
                    $this->enumLabel($r->category),
                    $this->enumLabel($r->requisition_for),
                    $r->project_name,
                    $r->amount,
                    $r->actual_amount,
                    $r->currency,
                    $this->enumLabel($r->status),
                    $r->requester?->name,
                    $r->submitted_at?->format('Y-m-d H:i:s'),
                    $r->decided_at?->format('Y-m-d H:i:s'),
                    $r->closed_at?->format('Y-m-d H:i:s'),
                    $r->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 'requisitions-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function enumLabel(mixed $state): string
    {
        if (is_object($state) && method_exists($state, 'label')) {
            return $state->label();
        }

        $value = $this->enumValue($state);

        return $value === '' ? '-' : str($value)->replace('_', ' ')->title()->toString();
    }

    protected function enumValue(mixed $state): string
    {
        if ($state instanceof \BackedEnum) {
            return (string) $state->value;
        }

        return is_scalar($state) ? (string) $state : '';
    }
}
