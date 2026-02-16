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
            'overTime' => $this->getOverTime($from, $to),
        ];
    }

    protected function getSummary(Carbon $from, Carbon $to): array
    {
        $rows = CashRequisition::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('status')
            ->get();

        $summary = [];
        $totalCount = 0;
        $totalAmount = 0;

        foreach ($rows as $row) {
            $summary[$row->status->value] = [
                'count' => $row->count,
                'total' => (float) $row->total,
            ];
            $totalCount += $row->count;
            $totalAmount += (float) $row->total;
        }

        $summary['overall'] = ['count' => $totalCount, 'total' => $totalAmount];
        return $summary;
    }

    protected function getByBranch(Carbon $from, Carbon $to): array
    {
        return CashRequisition::whereBetween('created_at', [$from, $to])
            ->select('branch', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('branch')
            ->get()
            ->map(fn ($r) => ['branch' => $r->branch->label(), 'count' => $r->count, 'total' => (float) $r->total])
            ->toArray();
    }

    protected function getByType(Carbon $from, Carbon $to): array
    {
        return CashRequisition::whereBetween('created_at', [$from, $to])
            ->select('requisition_for', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('requisition_for')
            ->get()
            ->map(fn ($r) => ['type' => $r->requisition_for->label(), 'count' => $r->count, 'total' => (float) $r->total])
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
            fputcsv($handle, ['ID', 'Branch', 'Requisition For', 'Amount', 'Currency', 'Purpose', 'Status', 'Requester', 'Created At']);
            foreach ($data as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->branch->label(),
                    $r->requisition_for->label(),
                    $r->amount,
                    $r->currency,
                    $r->purpose,
                    $r->status->label(),
                    $r->requester->name,
                    $r->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 'requisitions-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
