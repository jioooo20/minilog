<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = SeederData::read();
        $rows = array_map(static function (array $row): array {
            $createdAt = $row['created_at'] ?? now()->toDateTimeString();

            return [
                'location_id' => $row['location_id'],
                'location_code' => $row['location_code'],
                'location_name' => $row['location_name'],
                'location_type' => $row['location_type'],
                'is_active' => (bool) $row['is_active'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }, $data['locations'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('locations')->upsert(
            $rows,
            ['location_id'],
            ['location_code', 'location_name', 'location_type', 'is_active', 'created_at', 'updated_at']
        );
    }
}
