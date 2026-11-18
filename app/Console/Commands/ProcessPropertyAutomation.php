<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ProcessPropertyAutomation extends Command
{
    protected $signature = 'pims:process-property-automation';
    protected $description = 'Create inventory expiry and aging alerts for PIMS properties.';

    public function handle(): int
    {
        $now = now();
        DB::table('properties')->where('inventory_status', 'available')->whereNotNull('inventory_expires_at')
            ->where('inventory_expires_at', '<=', $now->copy()->addDays(7))->orderBy('id')->chunkById(200, function ($items) use ($now): void {
                foreach ($items as $property) {
                    $expired = $property->inventory_expires_at < $now;
                    $type = $expired ? 'inventory_expired' : 'inventory_expiring';
                    $exists = DB::table('property_automation_logs')->where('property_id', $property->id)->where('event_type', $type)
                        ->whereDate('created_at', $now->toDateString())->exists();
                    if (!$exists) DB::table('property_automation_logs')->insert([
                        'property_id' => $property->id, 'event_type' => $type,
                        'title' => $expired ? 'Inventory confirmation expired' : 'Inventory confirmation due soon',
                        'message' => 'Reconfirm property availability and asking price with the inventory source.',
                        'due_at' => $property->inventory_expires_at, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            });

        DB::table('properties')->whereIn('inventory_status', ['available', 'reserved'])->where('created_at', '<=', $now->copy()->subDays(30))
            ->whereNotIn('id', function ($query) use ($now): void {
                $query->select('property_id')->from('property_automation_logs')->where('event_type', 'inventory_aging')
                    ->where('created_at', '>=', $now->copy()->subDays(1));
            })->orderBy('id')->chunkById(200, function ($items) use ($now): void {
                foreach ($items as $property) {
                    $ageDays = $now->diffInDays($property->created_at);
                    $bucket = $ageDays >= 90 ? '90+' : ($ageDays >= 60 ? '60+' : '30+');
                    DB::table('property_automation_logs')->insert([
                        'property_id' => $property->id, 'event_type' => 'inventory_aging',
                        'title' => "Inventory aging: {$bucket} days", 'message' => 'Review listing freshness, asking price and owner follow-up.',
                        'due_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            });
        $this->info('Property automation alerts processed.');
        return self::SUCCESS;
    }
}
