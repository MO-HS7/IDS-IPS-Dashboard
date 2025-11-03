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
            // Add packet-level fields for analytics and demo data
            $table->string('source_ip')->nullable()->after('user_id');
            $table->string('destination_ip')->nullable()->after('source_ip');
            $table->integer('source_port')->nullable()->after('destination_ip');
            $table->integer('destination_port')->nullable()->after('source_port');
            $table->string('protocol')->nullable()->after('destination_port');
            $table->integer('packet_size')->nullable()->after('protocol');
            $table->bigInteger('file_size')->nullable()->after('packet_size');
            $table->timestamp('timestamp')->nullable()->after('file_size');
            $table->boolean('processed')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('network_logs', function (Blueprint $table) {
            $table->dropColumn([
                'source_ip',
                'destination_ip',
                'source_port',
                'destination_port',
                'protocol',
                'packet_size',
                'file_size',
                'timestamp',
                'processed',
            ]);
        });
    }
};
