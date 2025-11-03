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
        Schema::table('network_logs', function (Blueprint $table) {
            // Add only the missing columns
            if (!Schema::hasColumn('network_logs', 'timestamp')) {
                $table->timestamp('timestamp')->nullable()->after('packet_size');
            }
            if (!Schema::hasColumn('network_logs', 'processed')) {
                $table->boolean('processed')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('network_logs', function (Blueprint $table) {
            if (Schema::hasColumn('network_logs', 'timestamp')) {
                $table->dropColumn('timestamp');
            }
            if (Schema::hasColumn('network_logs', 'processed')) {
                $table->dropColumn('processed');
            }
        });
    }
};
