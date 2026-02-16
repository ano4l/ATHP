<?php

namespace App\Models;

use App\Enums\LeaveReason;
use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'reason',
        'start_date',
        'end_date',
        'days',
        'notes',
        'status',
        'decided_at',
        'decided_by_id',
        'decision_comment',
    ];

    protected function casts(): array
    {
        return [
            'reason' => LeaveReason::class,
            'status' => LeaveStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    public static function countBusinessDays(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        $days = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }
        return $days;
    }
}
