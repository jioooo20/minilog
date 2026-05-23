<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsSeeder extends Seeder
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
                'dept_id' => $row['dept_id'],
                'dept_code' => $row['dept_code'],
                'dept_name' => $row['dept_name'],
                'manager_name' => $row['manager_name'] ?? null,
                'is_active' => (bool) $row['is_active'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }, $data['departments'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('departments')->upsert(
            $rows,
            ['dept_id'],
            ['dept_code', 'dept_name', 'manager_name', 'is_active', 'created_at', 'updated_at']
        );
    }
}
