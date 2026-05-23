<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemCategoriesSeeder extends Seeder
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
                'category_id' => $row['category_id'],
                'category_code' => $row['category_code'],
                'category_name' => $row['category_name'],
                'description' => $row['description'] ?? null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }, $data['item_categories'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('item_categories')->upsert(
            $rows,
            ['category_id'],
            ['category_code', 'category_name', 'description', 'created_at', 'updated_at']
        );
    }
}
