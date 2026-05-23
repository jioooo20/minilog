<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IncidentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = SeederData::read();
        $rows = array_map(static function (array $row): array {
            $createdAt = $row['detected_at'] ?? now()->toDateTimeString();
            $updatedAt = $row['closed_at'] ?? $createdAt;

            return [
                'incident_id' => $row['incident_id'],
                'incident_code' => $row['incident_code'],
                'title' => $row['title'],
                'description' => $row['description'],
                'item_id' => $row['item_id'],
                'component_item_id' => $row['component_item_id'] ?? null,
                'location_id' => $row['location_id'],
                'severity' => $row['severity'],
                'status' => $row['status'],
                'detected_at' => $row['detected_at'],
                'investigating_started_at' => $row['investigating_started_at'] ?? null,
                'repair_started_at' => $row['repair_started_at'] ?? null,
                'resolved_at' => $row['resolved_at'] ?? null,
                'closed_at' => $row['closed_at'] ?? null,
                'reported_by' => $row['reported_by'],
                'handled_by' => $row['handled_by'] ?? null,
                'closed_by' => $row['closed_by'] ?? null,
                'approved_by' => $row['approved_by'] ?? null,
                'verified_by' => $row['verified_by'] ?? null,
                'root_cause_hypothesis' => $row['root_cause_hypothesis'] ?? null,
                'investigation_notes' => $row['investigation_notes'] ?? null,
                'hypothesis_approved' => (bool) $row['hypothesis_approved'],
                'hypothesis_review_notes' => $row['hypothesis_review_notes'] ?? null,
                'hypothesis_approved_at' => $row['hypothesis_approved_at'] ?? null,
                'corrective_actions' => $row['corrective_actions'] ?? null,
                'parts_replaced' => $row['parts_replaced'] ?? null,
                'verification_notes' => $row['verification_notes'] ?? null,
                'verified_at' => $row['verified_at'] ?? null,
                'closing_notes' => $row['closing_notes'] ?? null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        }, $data['incidents'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('incidents')->upsert(
            $rows,
            ['incident_id'],
            [
                'incident_code',
                'title',
                'description',
                'item_id',
                'component_item_id',
                'location_id',
                'severity',
                'status',
                'detected_at',
                'investigating_started_at',
                'repair_started_at',
                'resolved_at',
                'closed_at',
                'reported_by',
                'handled_by',
                'closed_by',
                'approved_by',
                'verified_by',
                'root_cause_hypothesis',
                'investigation_notes',
                'hypothesis_approved',
                'hypothesis_review_notes',
                'hypothesis_approved_at',
                'corrective_actions',
                'parts_replaced',
                'verification_notes',
                'verified_at',
                'closing_notes',
                'created_at',
                'updated_at',
            ]
        );
    }
}
