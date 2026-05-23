<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = SeederData::read();
        $rows = array_map(static function (array $row): array {
            $createdAt = $row['created_at'] ?? now()->toDateTimeString();
            $updatedAt = $row['updated_at'] ?? $createdAt;

            return [
                'item_id' => $row['item_id'],
                'asset_tag' => $row['asset_tag'],
                'serial_number' => $row['serial_number'] ?? null,
                'item_name' => $row['item_name'],
                'brand' => $row['brand'] ?? null,
                'model' => $row['model'] ?? null,
                'description' => $row['description'] ?? null,
                'category_id' => $row['category_id'] ?? null,
                'item_type' => $row['item_type'] ?? null,
                'location_id' => $row['location_id'] ?? null,
                'dept_id' => $row['dept_id'] ?? null,
                'status' => $row['status'],
                'installation_date' => $row['installation_date'] ?? null,
                'last_calibration_date' => $row['last_calibration_date'] ?? null,
                'calibration_due_date' => $row['calibration_due_date'] ?? null,
                'is_critical' => (bool) $row['is_critical'],
                'created_by' => $row['created_by'] ?? null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        }, $data['items'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('items')->upsert(
            $rows,
            ['item_id'],
            [
                'asset_tag',
                'serial_number',
                'item_name',
                'brand',
                'model',
                'description',
                'category_id',
                'item_type',
                'location_id',
                'dept_id',
                'status',
                'installation_date',
                'last_calibration_date',
                'calibration_due_date',
                'is_critical',
                'created_by',
                'created_at',
                'updated_at',
            ]
        );
    }
}
