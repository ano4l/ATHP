<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('branch');
            $table->string('requisition_for');
            $table->string('client_ref')->nullable();
            $table->string('order_ref')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->text('purpose');
            $table->date('needed_by');
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_comment')->nullable();
            $table->timestamps();

            $table->index('requester_id');
            $table->index('status');
            $table->index('branch');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_requisitions');
    }
};
