<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('stage', 30); // verification | approval
            $table->string('status', 30); // submitted | verified | rejected | changes_requested | approved
            $table->json('checklist')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('acted_at')->useCurrent();
            $table->timestamps();
            $table->index(['property_id', 'stage', 'id']);
            $table->index(['stage', 'status', 'acted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_verifications');
    }
};
