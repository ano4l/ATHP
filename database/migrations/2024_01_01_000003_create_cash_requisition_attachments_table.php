<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_requisition_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('cash_requisitions')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedInteger('file_size');
            $table->string('storage_path');
            $table->foreignId('uploaded_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('requisition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_requisition_attachments');
    }
};
