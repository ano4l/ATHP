<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('action');
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('actor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
