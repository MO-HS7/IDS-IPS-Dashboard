<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('timeline')->nullable(); // Store investigation timeline events
            $table->json('evidence')->nullable(); // Store file paths, links, etc.
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('created_by');
        });

        // Pivot table for investigations and alerts
        Schema::create('alert_investigation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts')->onDelete('cascade');
            $table->foreignId('investigation_id')->constrained('investigations')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['alert_id', 'investigation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_investigation');
        Schema::dropIfExists('investigations');
    }
};
