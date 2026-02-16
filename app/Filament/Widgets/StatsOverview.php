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
            $stats[] = Stat::make('Pending Requisitions', CashRequisition::where('status', RequisitionStatus::SUBMITTED)->count())
                ->description('Awaiting approval')
                ->color('warning')
                ->icon('heroicon-o-clock');

            $stats[] = Stat::make('Pending Leaves', LeaveRequest::where('status', LeaveStatus::SUBMITTED)->count())
                ->description('Awaiting approval')
                ->color('warning')
                ->icon('heroicon-o-clock');

            $totalAmount = CashRequisition::where('status', RequisitionStatus::APPROVED)->sum('amount');
            $stats[] = Stat::make('Total Approved', '$' . number_format($totalAmount, 2))
                ->description('All approved requisitions')
                ->color('success')
                ->icon('heroicon-o-banknotes');

            $stats[] = Stat::make('Total Requisitions', CashRequisition::count())
                ->description('All time')
                ->color('info')
                ->icon('heroicon-o-document-text');
        } else {
            $stats[] = Stat::make('My Requisitions', CashRequisition::where('requester_id', $user->id)->count())
                ->icon('heroicon-o-banknotes')
                ->color('info');

            $stats[] = Stat::make('My Leaves', LeaveRequest::where('employee_id', $user->id)->count())
                ->icon('heroicon-o-calendar-days')
                ->color('info');

            $approvedDays = LeaveRequest::where('employee_id', $user->id)
                ->where('status', LeaveStatus::APPROVED)
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
