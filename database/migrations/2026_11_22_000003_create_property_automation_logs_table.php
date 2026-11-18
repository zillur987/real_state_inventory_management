<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_automation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('event_type', 50);
            $table->string('title', 180);
            $table->text('message')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['event_type', 'processed_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('property_automation_logs'); }
};
