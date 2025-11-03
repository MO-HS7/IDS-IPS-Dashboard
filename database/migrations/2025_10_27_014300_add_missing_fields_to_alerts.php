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
        Schema::table('alerts', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('alerts', 'confidence_score')) {
                $table->decimal('confidence_score', 5, 2)->nullable()->after('destination_ip');
            }
            if (!Schema::hasColumn('alerts', 'status')) {
                $table->string('status')->default('new')->after('confidence_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            if (Schema::hasColumn('alerts', 'confidence_score')) {
                $table->dropColumn('confidence_score');
            }
            if (Schema::hasColumn('alerts', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
