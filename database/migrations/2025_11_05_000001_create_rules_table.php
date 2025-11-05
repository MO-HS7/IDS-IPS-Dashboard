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
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('signature'); // Snort rule signature
            $table->enum('category', [
                'malware',
                'exploit',
                'dos',
                'scan',
                'policy',
                'trojan',
                'web-attack',
                'misc',
                'custom'
            ])->default('custom');
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('sid')->unique(); // Snort Signature ID
            $table->integer('rev')->default(1); // Revision number
            $table->string('protocol')->nullable(); // tcp, udp, icmp, ip
            $table->string('source_ip')->nullable();
            $table->string('source_port')->nullable();
            $table->string('destination_ip')->nullable();
            $table->string('destination_port')->nullable();
            $table->enum('action', ['alert', 'log', 'pass', 'drop', 'reject'])->default('alert');
            $table->json('metadata')->nullable(); // Additional rule metadata
            $table->integer('alert_count')->default(0); // How many times this rule triggered
            $table->timestamp('last_triggered_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('enabled');
            $table->index('category');
            $table->index('severity');
            $table->index('sid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
