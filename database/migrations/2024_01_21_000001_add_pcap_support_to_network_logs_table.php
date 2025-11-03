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
        if (!Schema::hasTable('network_logs')) {
            // Table does not exist yet; skip to avoid failing due to migration order.
            return;
        }

        Schema::table('network_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('network_logs', 'file_type')) {
                $table->string('file_type', 20)->default('csv')->after('file_path'); // csv, pcap, pcapng
            }
            if (!Schema::hasColumn('network_logs', 'file_size')) {
                $table->bigInteger('file_size')->nullable()->after('file_type'); // in bytes
            }
            if (!Schema::hasColumn('network_logs', 'packet_count')) {
                $table->integer('packet_count')->default(0)->after('file_size');
            }
            if (!Schema::hasColumn('network_logs', 'capture_start_time')) {
                $table->timestamp('capture_start_time')->nullable()->after('packet_count');
            }
            if (!Schema::hasColumn('network_logs', 'capture_end_time')) {
                $table->timestamp('capture_end_time')->nullable()->after('capture_start_time');
            }
            if (!Schema::hasColumn('network_logs', 'capture_duration')) {
                $table->integer('capture_duration')->nullable()->after('capture_end_time'); // in seconds
            }
            if (!Schema::hasColumn('network_logs', 'file_metadata')) {
                $table->json('file_metadata')->nullable()->after('capture_duration');
            }
            if (!Schema::hasColumn('network_logs', 'processing_error')) {
                $table->text('processing_error')->nullable()->after('file_metadata');
            }
            if (!Schema::hasColumn('network_logs', 'processing_progress')) {
                $table->integer('processing_progress')->default(0)->after('processing_error'); // 0-100
            }

            // Indexes (attempt to create; if they exist, MySQL will error only in some versions; handled on fresh DBs)
            $table->index('file_type');
            $table->index(['status', 'processing_progress']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('network_logs')) {
            return;
        }

        Schema::table('network_logs', function (Blueprint $table) {
            // Use explicit index names based on Laravel conventions for safety
            try {
                $table->dropIndex('network_logs_file_type_index');
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                $table->dropIndex('network_logs_status_processing_progress_index');
            } catch (\Throwable $e) {
                // ignore
            }

            $columns = [
                'file_type',
                'file_size',
                'packet_count',
                'capture_start_time',
                'capture_end_time',
                'capture_duration',
                'file_metadata',
                'processing_error',
                'processing_progress',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('network_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
