<?php

use App\Enums\RequisitionCategory;

return [
    'stage2_threshold' => (float) env('REQUISITION_STAGE2_THRESHOLD', 10000),

    'duplicate_lookback_days' => (int) env('REQUISITION_DUPLICATE_LOOKBACK_DAYS', 30),

    'attachment_max_mb' => (int) env('REQUISITION_ATTACHMENT_MAX_MB', 10),

    'attachment_required_categories' => [
        RequisitionCategory::PROCUREMENT->value,
        RequisitionCategory::EMERGENCY->value,
    ],
];
