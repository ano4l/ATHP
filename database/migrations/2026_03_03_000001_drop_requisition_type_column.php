<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_requisitions', function (Blueprint $table) {
            $table->dropIndex(['requisition_type']);
            $table->dropColumn('requisition_type');
        });
    }

    public function down(): void
    {
        Schema::table('cash_requisitions', function (Blueprint $table) {
            $table->string('requisition_type')->default('cash')->after('branch');
            $table->index('requisition_type');
        });
    }
};
