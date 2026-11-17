<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->string('property_code', 40)->unique();
            $table->string('name', 180);
            $table->string('category', 32)->index();
            $table->string('market_type', 16)->index();
            $table->string('division', 100)->nullable()->index();
            $table->string('district', 100)->nullable()->index();
            $table->string('area', 150)->index();
            $table->text('detailed_address')->nullable();
            $table->string('developer', 180)->nullable()->index();
            $table->string('property_status', 32)->index();
            $table->decimal('apartment_size_sqft', 12, 2)->nullable();
            $table->decimal('land_size', 12, 2)->nullable();
            $table->string('land_size_unit', 16)->nullable();
            $table->unsignedSmallInteger('building_ground_plus')->nullable();
            $table->unsignedSmallInteger('units_per_floor')->nullable();
            $table->string('available_floor', 100)->nullable();
            $table->string('facing', 16)->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('balconies')->nullable();
            $table->boolean('drawing_room')->default(false);
            $table->boolean('dining_space')->default(false);
            $table->unsignedTinyInteger('parking_spaces')->default(0);
            $table->string('gas_connection', 16)->nullable();
            $table->boolean('has_lift')->default(false);
            $table->boolean('has_generator')->default(false);
            $table->unsignedSmallInteger('handover_year')->nullable();
            $table->string('handover_status', 20)->nullable();
            $table->string('paper_status', 24)->nullable();
            $table->string('legal_verification', 24)->nullable()->index();
            $table->string('mortgage_status', 24)->nullable();
            $table->string('ownership_type', 20)->nullable();
            $table->string('inventory_status', 20)->default('available')->index();
            $table->decimal('asking_price', 16, 2)->nullable();
            $table->boolean('is_negotiable')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['category', 'inventory_status']);
            $table->index(['area', 'asking_price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
