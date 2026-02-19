<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_requisitions', function (Blueprint $table) {
            $table->string('reference_no')->nullable()->after('id');
            $table->string('requisition_type')->default('cash')->after('branch');
            $table->string('project_name')->nullable()->after('requisition_type');
            $table->string('project_code')->nullable()->after('project_name');
            $table->string('category')->nullable()->after('project_code');
            $table->string('cost_center')->nullable()->after('category');
            $table->string('budget_code')->nullable()->after('cost_center');
            $table->boolean('requires_additional_approval')->default(false)->after('budget_code');

            $table->timestamp('stage1_approved_at')->nullable()->after('submitted_at');
            $table->foreignId('stage1_approved_by_id')->nullable()->after('stage1_approved_at')->constrained('users')->nullOnDelete();
            $table->text('stage1_comment')->nullable()->after('stage1_approved_by_id');

            $table->foreignId('processed_by_id')->nullable()->after('decided_by_id')->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable()->after('processed_by_id');
            $table->string('payment_method')->nullable()->after('processed_at');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->date('payment_date')->nullable()->after('payment_reference');
            $table->text('finance_comment')->nullable()->after('payment_date');

            $table->string('purchase_status')->nullable()->after('finance_comment');
            $table->string('delivery_status')->nullable()->after('purchase_status');
            $table->timestamp('fulfilled_at')->nullable()->after('delivery_status');
            $table->foreignId('fulfilled_by_id')->nullable()->after('fulfilled_at')->constrained('users')->nullOnDelete();
            $table->text('fulfilment_notes')->nullable()->after('fulfilled_by_id');
            $table->decimal('actual_amount', 15, 2)->nullable()->after('fulfilment_notes');
            $table->text('variance_reason')->nullable()->after('actual_amount');

            $table->timestamp('requester_confirmed_at')->nullable()->after('variance_reason');
            $table->timestamp('closed_at')->nullable()->after('requester_confirmed_at');
            $table->foreignId('closed_by_id')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->text('closure_comment')->nullable()->after('closed_by_id');

            $table->unsignedInteger('approval_turnaround_hours')->nullable()->after('closure_comment');

            $table->unique('reference_no');
            $table->index('requisition_type');
            $table->index('category');
            $table->index('project_name');
            $table->index('cost_center');
        });

        DB::table('cash_requisitions')
            ->select(['id', 'created_at', 'amount'])
            ->orderBy('id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    $createdAt = $row->created_at ? Carbon::parse($row->created_at) : now();
                    DB::table('cash_requisitions')
                        ->where('id', $row->id)
                        ->update([
                            'reference_no' => sprintf('REQ-%s-%06d', $createdAt->format('Ymd'), $row->id),
                            'project_name' => 'General Operations',
                            'category' => 'operations',
                            'cost_center' => 'GEN-OPS',
                            'requires_additional_approval' => ((float) $row->amount) >= (float) config('requisition.stage2_threshold', 10000),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('cash_requisitions', function (Blueprint $table) {
            $table->dropForeign(['stage1_approved_by_id']);
            $table->dropForeign(['processed_by_id']);
            $table->dropForeign(['fulfilled_by_id']);
            $table->dropForeign(['closed_by_id']);

            $table->dropUnique(['reference_no']);
            $table->dropIndex(['requisition_type']);
            $table->dropIndex(['category']);
            $table->dropIndex(['project_name']);
            $table->dropIndex(['cost_center']);

            $table->dropColumn([
                'reference_no',
                'requisition_type',
                'project_name',
                'project_code',
                'category',
                'cost_center',
                'budget_code',
                'requires_additional_approval',
                'stage1_approved_at',
                'stage1_approved_by_id',
                'stage1_comment',
                'processed_by_id',
                'processed_at',
                'payment_method',
                'payment_reference',
                'payment_date',
                'finance_comment',
                'purchase_status',
                'delivery_status',
                'fulfilled_at',
                'fulfilled_by_id',
                'fulfilment_notes',
                'actual_amount',
                'variance_reason',
                'requester_confirmed_at',
                'closed_at',
                'closed_by_id',
                'closure_comment',
                'approval_turnaround_hours',
            ]);
        });
    }
};
