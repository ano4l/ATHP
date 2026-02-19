<?php

namespace App\Filament\Widgets;

use App\Enums\LeaveStatus;
use App\Enums\RequisitionStatus;
use App\Models\CashRequisition;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $stats = [];

        if ($isAdmin) {
            $pendingApprovals = CashRequisition::whereIn('status', [
                RequisitionStatus::SUBMITTED->value,
                RequisitionStatus::STAGE1_APPROVED->value,
                RequisitionStatus::MODIFICATION_REQUESTED->value,
            ])->count();

            $processingQueue = CashRequisition::whereIn('status', [
                RequisitionStatus::APPROVED->value,
                RequisitionStatus::PROCESSING->value,
                RequisitionStatus::OUTSTANDING->value,
                RequisitionStatus::PAID->value,
            ])->count();

            $portfolioValue = CashRequisition::whereIn('status', [
                RequisitionStatus::APPROVED->value,
                RequisitionStatus::PROCESSING->value,
                RequisitionStatus::OUTSTANDING->value,
                RequisitionStatus::PAID->value,
                RequisitionStatus::FULFILLED->value,
                RequisitionStatus::CLOSED->value,
            ])->sum('amount');

            $stats[] = Stat::make('Pending Approvals', $pendingApprovals)
                ->description('Stage 1 / final approval queue')
                ->color('warning')
                ->icon('heroicon-o-clock');

            $stats[] = Stat::make('In Processing Pipeline', $processingQueue)
                ->description('Approved to paid/outstanding')
                ->color('info')
                ->icon('heroicon-o-cog-6-tooth');

            $stats[] = Stat::make('Pending Leaves', LeaveRequest::where('status', LeaveStatus::SUBMITTED->value)->count())
                ->description('Awaiting approval')
                ->color('warning')
                ->icon('heroicon-o-clock');

            $stats[] = Stat::make('Pipeline Value', '$' . number_format((float) $portfolioValue, 2))
                ->description('Approved through closed')
                ->color('success')
                ->icon('heroicon-o-banknotes');
        } else {
            $stats[] = Stat::make('My Requisitions', CashRequisition::where('requester_id', $user->id)->count())
                ->icon('heroicon-o-banknotes')
                ->color('info');

            $myOpen = CashRequisition::where('requester_id', $user->id)
                ->whereNotIn('status', [RequisitionStatus::DENIED->value, RequisitionStatus::CLOSED->value])
                ->count();

            $stats[] = Stat::make('My Open Requisitions', $myOpen)
                ->description('Not closed/denied')
                ->icon('heroicon-o-folder-open')
                ->color('warning');

            $stats[] = Stat::make('My Leaves', LeaveRequest::where('employee_id', $user->id)->count())
                ->icon('heroicon-o-calendar-days')
                ->color('info');

            $approvedDays = LeaveRequest::where('employee_id', $user->id)
                ->where('status', LeaveStatus::APPROVED->value)
                ->sum('days');
            $stats[] = Stat::make('Approved Leave Days', $approvedDays)
                ->description('Total approved')
                ->icon('heroicon-o-check-circle')
                ->color('success');

            $totalAmount = CashRequisition::where('requester_id', $user->id)->sum('amount');
            $stats[] = Stat::make('Total Requested', '$' . number_format($totalAmount, 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('primary');
        }

        return $stats;
    }
}
