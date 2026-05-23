<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttachmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = SeederData::read();
        $rows = array_map(static function (array $row): array {
            return [
                'attachment_id' => $row['attachment_id'],
                'incident_id' => $row['incident_id'],
                'uploaded_by' => $row['uploaded_by'],
                'file_name' => $row['file_name'],
                'file_path' => $row['file_path'],
                'file_size' => $row['file_size'] ?? null,
                'mime_type' => $row['mime_type'] ?? null,
                'description' => $row['description'] ?? null,
                'uploaded_at' => $row['uploaded_at'],
            ];
        }, $data['attachments'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('attachments')->upsert(
            $rows,
            ['attachment_id'],
            ['incident_id', 'uploaded_by', 'file_name', 'file_path', 'file_size', 'mime_type', 'description', 'uploaded_at']
        );
    }
}
