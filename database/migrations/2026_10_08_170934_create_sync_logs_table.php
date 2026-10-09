<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_target_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('trigger');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('repositories_created')->default(0);
            $table->unsignedInteger('repositories_updated')->default(0);
            $table->unsignedInteger('repositories_deleted')->default(0);
            $table->unsignedInteger('readmes_queued')->default(0);
            $table->unsignedInteger('readmes_synced')->default(0);
            $table->unsignedInteger('readmes_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};