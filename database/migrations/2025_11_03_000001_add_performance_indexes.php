<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add performance indexes to network_logs and alerts tables
     */
    public function up(): void
    {
        Schema::table('network_logs', function (Blueprint $table) {
            // Index for status queries (frequently used for filtering)
            $table->index('status', 'idx_network_logs_status');
            
            // Index for upload_date queries (used in analytics and time-based filtering)
            $table->index('upload_date', 'idx_network_logs_upload_date');
            
            // Composite index for user-specific status queries
            $table->index(['user_id', 'status'], 'idx_network_logs_user_status');
            
            // Index for created_at (used in sorting and time-based queries)
            $table->index('created_at', 'idx_network_logs_created_at');
        });

        Schema::table('alerts', function (Blueprint $table) {
            // Index for detected_at (frequently used for time-based queries)
            $table->index('detected_at', 'idx_alerts_detected_at');
            
            // Index for status (used in filtering and dashboard queries)
            $table->index('status', 'idx_alerts_status');
            
            // Index for source_ip (used for IP-based analysis)
            $table->index('source_ip', 'idx_alerts_source_ip');
            
            // Index for severity (used in filtering critical alerts)
            $table->index('severity', 'idx_alerts_severity');
            
            // Index for attack_type (used in attack type distribution)
            $table->index('attack_type', 'idx_alerts_attack_type');
            
            // Composite index for status and severity queries
            $table->index(['status', 'severity'], 'idx_alerts_status_severity');
            
            // Composite index for time-based and severity queries
            $table->index(['detected_at', 'severity'], 'idx_alerts_detected_severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('network_logs', function (Blueprint $table) {
            $table->dropIndex('idx_network_logs_status');
            $table->dropIndex('idx_network_logs_upload_date');
            $table->dropIndex('idx_network_logs_user_status');
            $table->dropIndex('idx_network_logs_created_at');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex('idx_alerts_detected_at');
            $table->dropIndex('idx_alerts_status');
            $table->dropIndex('idx_alerts_source_ip');
            $table->dropIndex('idx_alerts_severity');
            $table->dropIndex('idx_alerts_attack_type');
            $table->dropIndex('idx_alerts_status_severity');
            $table->dropIndex('idx_alerts_detected_severity');
        });
    }
};
