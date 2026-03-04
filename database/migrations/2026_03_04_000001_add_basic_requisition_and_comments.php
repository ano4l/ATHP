<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_requisitions', function (Blueprint $table) {
            $table->boolean('is_basic_requisition')->default(false)->after('requires_additional_approval');
        });

        Schema::create('requisition_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('cash_requisitions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('context')->nullable(); // e.g. 'approval', 'modification', 'general', 'fulfilment'
            $table->timestamps();

            $table->index('requisition_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_comments');

        Schema::table('cash_requisitions', function (Blueprint $table) {
            $table->dropColumn('is_basic_requisition');
        });
    }
};
