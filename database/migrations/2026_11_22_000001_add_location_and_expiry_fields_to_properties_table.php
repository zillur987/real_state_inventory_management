<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('area');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('inventory_expires_at')->nullable()->index();
            $table->timestamp('availability_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropIndex(['inventory_expires_at']);
            $table->dropColumn(['latitude', 'longitude', 'inventory_expires_at', 'availability_confirmed_at']);
        });
    }
};
