<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('days');
            $table->text('notes')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_comment')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
