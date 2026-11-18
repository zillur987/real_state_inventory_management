<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_code', 40)->unique();
            $table->string('name', 180)->index();
            $table->string('phone', 40);
            $table->string('email', 180)->nullable()->index();
            $table->string('profession', 150)->nullable();
            $table->text('detailed_address')->nullable();
            $table->text('legal_status_note')->nullable();
            $table->decimal('expected_price', 16, 2)->nullable();
            $table->decimal('final_negotiated_price', 16, 2)->nullable();
            $table->foreignId('relationship_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_type', 20)->default('individual')->index();
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
