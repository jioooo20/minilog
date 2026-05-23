<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = SeederData::read();
        $rows = array_map(static function (array $row): array {
            return [
                'notif_id' => $row['notif_id'],
                'user_id' => $row['user_id'],
                'incident_id' => $row['incident_id'],
                'type' => $row['type'],
                'message' => $row['message'],
                'is_read' => (bool) $row['is_read'],
                'created_at' => $row['created_at'],
                'read_at' => $row['read_at'] ?? null,
            ];
        }, $data['notifications'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('notifications')->upsert(
            $rows,
            ['notif_id'],
            ['user_id', 'incident_id', 'type', 'message', 'is_read', 'created_at', 'read_at']
        );
    }
}
