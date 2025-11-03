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
        Schema::create('live_monitoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('interface');
            $table->string('status')->default('active'); // active, stopped, error
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('stopped_at')->nullable();
            $table->integer('packets_captured')->default(0);
            $table->integer('threats_detected')->default(0);
            $table->json('statistics')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('session_id');
        });
        
        Schema::create('live_captured_packets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_monitoring_sessions')->onDelete('cascade');
            $table->timestamp('captured_at')->useCurrent();
            $table->string('source_ip', 45);
            $table->string('destination_ip', 45);
            $table->integer('source_port')->nullable();
            $table->integer('destination_port')->nullable();
            $table->string('protocol', 20);
            $table->integer('packet_length');
            $table->text('payload_preview')->nullable();
            $table->string('flags')->nullable();
            $table->decimal('threat_score', 5, 2)->default(0.00);
            $table->boolean('is_threat')->default(false);
            $table->string('threat_type')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index(['session_id', 'captured_at']);
            $table->index(['source_ip', 'destination_ip']);
            $table->index('is_threat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_captured_packets');
        Schema::dropIfExists('live_monitoring_sessions');
    }
};
