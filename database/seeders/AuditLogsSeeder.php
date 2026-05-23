<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = SeederData::read();
        $rows = array_map(static function (array $row): array {
            return [
                'log_id' => $row['log_id'],
                'incident_id' => $row['incident_id'],
                'user_id' => $row['user_id'],
                'action' => $row['action'],
                'action_details' => $row['action_details'] ?? null,
                'old_value' => $row['old_value'] ?? null,
                'new_value' => $row['new_value'] ?? null,
                'ip_address' => $row['ip_address'] ?? null,
                'created_at' => $row['created_at'],
            ];
        }, $data['audit_logs'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('audit_logs')->upsert(
            $rows,
            ['log_id'],
            ['incident_id', 'user_id', 'action', 'action_details', 'old_value', 'new_value', 'ip_address', 'created_at']
        );
    }
}
