<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IncidentService
{
    public function createIncident(array $payload, User $reporter, ?string $ipAddress = null): Incident
    {
        return DB::transaction(function () use ($payload, $reporter, $ipAddress): Incident {
            $detectedAt = $payload['detected_at'] ?? now();
            $incidentCode = $payload['incident_code'] ?? $this->generateIncidentCode($detectedAt);

            $incident = Incident::create([
                'incident_code' => $incidentCode,
                'title' => $payload['title'],
                'description' => $payload['description'],
                'item_id' => $payload['item_id'],
                'component_item_id' => $payload['component_item_id'] ?? null,
                'location_id' => $payload['location_id'],
                'severity' => $payload['severity'],
                'status' => 'open',
                'detected_at' => $detectedAt,
                'reported_by' => $reporter->id,
            ]);

            $this->writeAudit(
                $incident,
                $reporter,
                'create_incident',
                'Incident created',
                [],
                ['status' => 'open', 'severity' => $incident->severity],
                $ipAddress
            );

            $this->notifyNewIncident($incident);

            return $incident;
        });
    }

    public function assignSelf(Incident $incident, User $engineer, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['open']);

        return DB::transaction(function () use ($incident, $engineer, $ipAddress): Incident {
            $incident->fill([
                'handled_by' => $engineer->id,
                'status' => 'investigating',
                'investigating_started_at' => $incident->investigating_started_at ?? now(),
            ])->save();

            $this->writeAudit(
                $incident,
                $engineer,
                'assign_self',
                'Engineer assigned to incident',
                ['status' => 'open'],
                ['status' => 'investigating'],
                $ipAddress
            );

            return $incident->refresh();
        });
    }

    public function proposeHypothesis(Incident $incident, User $engineer, array $payload, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['investigating']);

        return DB::transaction(function () use ($incident, $engineer, $payload, $ipAddress): Incident {
            $previousValues = [
                'investigation_notes' => $incident->investigation_notes,
                'root_cause_hypothesis' => $incident->root_cause_hypothesis,
                'status' => $incident->status,
            ];

            $incident->fill([
                'root_cause_hypothesis' => $payload['root_cause_hypothesis'],
                'investigation_notes' => $payload['investigation_notes'] ?? null,
                'status' => 'awaiting_approval',
                'hypothesis_review_notes' => null,
            ])->save();

            $this->writeAudit(
                $incident,
                $engineer,
                'propose_root_cause',
                'Hypothesis submitted',
                $previousValues,
                [
                    'investigation_notes' => $incident->investigation_notes,
                    'root_cause_hypothesis' => $incident->root_cause_hypothesis,
                    'status' => 'awaiting_approval',
                ],
                $ipAddress
            );

            $this->notifyByRoles(['supervisor'], $incident, 'approval_needed', 'Incident awaiting hypothesis approval');

            return $incident->refresh();
        });
    }

    public function saveInvestigationDraft(Incident $incident, User $engineer, array $payload, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['investigating']);

        return DB::transaction(function () use ($incident, $engineer, $payload, $ipAddress): Incident {
            $previousValues = [
                'investigation_notes' => $incident->investigation_notes,
                'root_cause_hypothesis' => $incident->root_cause_hypothesis,
            ];

            $incident->fill([
                'investigation_notes' => array_key_exists('investigation_notes', $payload)
                    ? $payload['investigation_notes']
                    : $incident->investigation_notes,
                'root_cause_hypothesis' => array_key_exists('root_cause_hypothesis', $payload)
                    ? $payload['root_cause_hypothesis']
                    : $incident->root_cause_hypothesis,
            ])->save();

            $this->writeAudit(
                $incident,
                $engineer,
                'update_investigation_draft',
                'Investigation draft saved',
                $previousValues,
                [
                    'investigation_notes' => $incident->investigation_notes,
                    'root_cause_hypothesis' => $incident->root_cause_hypothesis,
                ],
                $ipAddress
            );

            return $incident->refresh();
        });
    }

    public function approveHypothesis(Incident $incident, User $supervisor, ?string $reviewNotes = null, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['awaiting_approval']);

        return DB::transaction(function () use ($incident, $supervisor, $reviewNotes, $ipAddress): Incident {
            $incident->fill([
                'hypothesis_approved' => true,
                'hypothesis_review_notes' => $reviewNotes,
                'approved_by' => $supervisor->id,
                'hypothesis_approved_at' => now(),
                'status' => 'repairing',
            ])->save();

            $previousValues = [
                'investigation_notes' => $incident->investigation_notes,
                'root_cause_hypothesis' => $incident->root_cause_hypothesis,
                'status' => 'awaiting_approval',
            ];

            $newValues = [
                'hypothesis_review_notes' => $incident->hypothesis_review_notes,
                'hypothesis_approved' => $incident->hypothesis_approved,
                'approved_by' => $supervisor->id,
                'hypothesis_approved_at' => $incident->hypothesis_approved_at ? $incident->hypothesis_approved_at->toDateTimeString() : null,
                'status' => 'repairing',
            ];

            $this->writeAudit(
                $incident,
                $supervisor,
                'approve_hypothesis',
                'Hypothesis approved',
                $previousValues,
                $newValues,
                $ipAddress
            );

            $this->notifyHandler($incident, 'hypothesis_approved', 'Hypothesis approved, proceed with repair');

            return $incident->refresh();
        });
    }

    public function rejectHypothesis(Incident $incident, User $supervisor, string $reviewNotes, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['awaiting_approval']);

        return DB::transaction(function () use ($incident, $supervisor, $reviewNotes, $ipAddress): Incident {
            $incident->fill([
                'hypothesis_approved' => false,
                'hypothesis_review_notes' => $reviewNotes,
                'approved_by' => null,
                'hypothesis_approved_at' => null,
                'status' => 'investigating',
            ])->save();

            $previousValues = [
                'investigation_notes' => $incident->investigation_notes,
                'root_cause_hypothesis' => $incident->root_cause_hypothesis,
                'status' => 'awaiting_approval',
            ];

            $newValues = [
                'hypothesis_review_notes' => $incident->hypothesis_review_notes,
                'hypothesis_approved' => $incident->hypothesis_approved,
                'status' => 'investigating',
            ];

            $this->writeAudit(
                $incident,
                $supervisor,
                'reject_hypothesis',
                'Hypothesis rejected',
                $previousValues,
                $newValues,
                $ipAddress
            );

            $this->notifyHandler($incident, 'approval_needed', 'Hypothesis rejected, please revise');

            return $incident->refresh();
        });
    }

    public function completeRepair(Incident $incident, User $engineer, array $payload, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['repairing']);

        return DB::transaction(function () use ($incident, $engineer, $payload, $ipAddress): Incident {
            $previousValues = [
                'corrective_actions' => $incident->corrective_actions,
                'parts_replaced' => $incident->parts_replaced,
                'repair_started_at' => optional($incident->repair_started_at)->toDateTimeString(),
                'resolved_at' => optional($incident->resolved_at)->toDateTimeString(),
                'status' => $incident->status,
            ];

            $incident->fill([
                'corrective_actions' => $payload['corrective_actions'] ?? null,
                'parts_replaced' => $payload['parts_replaced'] ?? null,
                'repair_started_at' => $incident->repair_started_at ?? now(),
                'resolved_at' => now(),
                'status' => 'verifying',
            ])->save();

            $newValues = [
                'corrective_actions' => $incident->corrective_actions,
                'parts_replaced' => $incident->parts_replaced,
                'repair_started_at' => optional($incident->repair_started_at)->toDateTimeString(),
                'resolved_at' => optional($incident->resolved_at)->toDateTimeString(),
                'status' => 'verifying',
            ];

            $this->writeAudit(
                $incident,
                $engineer,
                'complete_repair',
                'Repair completed',
                $previousValues,
                $newValues,
                $ipAddress
            );

            $this->notifyReporter($incident, 'verification_needed', 'Repair completed, please verify');

            return $incident->refresh();
        });
    }

    public function saveRepairDraft(Incident $incident, User $engineer, array $payload, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['repairing']);

        return DB::transaction(function () use ($incident, $engineer, $payload, $ipAddress): Incident {
            $previousValues = [
                'corrective_actions' => $incident->corrective_actions,
                'parts_replaced' => $incident->parts_replaced,
            ];

            $incident->fill([
                'corrective_actions' => array_key_exists('corrective_actions', $payload)
                    ? $payload['corrective_actions']
                    : $incident->corrective_actions,
                'parts_replaced' => array_key_exists('parts_replaced', $payload)
                    ? $payload['parts_replaced']
                    : $incident->parts_replaced,
            ])->save();

            $this->writeAudit(
                $incident,
                $engineer,
                'update_repair_draft',
                'Repair draft saved',
                $previousValues,
                [
                    'corrective_actions' => $incident->corrective_actions,
                    'parts_replaced' => $incident->parts_replaced,
                ],
                $ipAddress
            );

            return $incident->refresh();
        });
    }

    public function verifyIncident(Incident $incident, User $operator, bool $passed, ?string $notes, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['verifying']);

        return DB::transaction(function () use ($incident, $operator, $passed, $notes, $ipAddress): Incident {
            $previousValues = [
                'verification_notes' => $incident->verification_notes,
                'verified_by' => $incident->verified_by,
                'verified_at' => optional($incident->verified_at)->toDateTimeString(),
                'status' => $incident->status,
            ];

            if ($passed) {
                $incident->fill([
                    'verification_notes' => $notes,
                    'verified_by' => $operator->id,
                    'verified_at' => now(),
                ])->save();

                $this->writeAudit(
                    $incident,
                    $operator,
                    'verify_incident',
                    'Incident verified',
                    $previousValues,
                    [
                        'verification_notes' => $incident->verification_notes,
                        'verified_by' => $operator->id,
                        'verified_at' => optional($incident->verified_at)->toDateTimeString(),
                        'status' => 'verifying',
                    ],
                    $ipAddress
                );
            } else {
                $incident->fill([
                    'verification_notes' => $notes,
                    'verified_by' => null,
                    'verified_at' => null,
                    'status' => 'repairing',
                ])->save();

                $this->writeAudit(
                    $incident,
                    $operator,
                    'verification_failed',
                    'Verification failed',
                    $previousValues,
                    [
                        'verification_notes' => $incident->verification_notes,
                        'verified_by' => null,
                        'verified_at' => null,
                        'status' => 'repairing',
                    ],
                    $ipAddress
                );

                $this->notifyHandler($incident, 'approval_needed', 'Verification failed, resume repair');
            }

            return $incident->refresh();
        });
    }

    public function requestClosing(Incident $incident, User $engineer, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['verifying']);
        $this->assertVerified($incident);
        $this->assertClosingNotRequested($incident);

        return DB::transaction(function () use ($incident, $engineer, $ipAddress): Incident {
            $this->writeAudit(
                $incident,
                $engineer,
                'request_closing',
                'Closing requested',
                [],
                [],
                $ipAddress
            );

            $this->notifyByRoles(['supervisor'], $incident, 'closing_requested', 'Closing requested for incident');

            return $incident->refresh();
        });
    }

    private function assertClosingNotRequested(Incident $incident): void
    {
        $alreadyRequested = $incident->auditLogs()
            ->where('action', 'request_closing')
            ->exists();

        if ($alreadyRequested) {
            abort(409, 'Closing has already been requested for this incident.');
        }
    }

    private function assertVerified(Incident $incident): void
    {
        if (!$incident->verified_at) {
            abort(403, 'Incident must be verified before requesting closing.');
        }
    }

    public function closeIncident(Incident $incident, User $supervisor, string $closingNotes, ?string $ipAddress = null): Incident
    {
        $this->assertStatus($incident, ['verifying']);

        return DB::transaction(function () use ($incident, $supervisor, $closingNotes, $ipAddress): Incident {
            $incident->fill([
                'status' => 'closed',
                'closed_by' => $supervisor->id,
                'closed_at' => now(),
                'closing_notes' => $closingNotes,
            ])->save();

            $this->writeAudit(
                $incident,
                $supervisor,
                'close_incident',
                'Incident closed',
                ['status' => 'verifying'],
                ['status' => 'closed'],
                $ipAddress
            );

            $this->notifyReporter($incident, 'closed', 'Incident closed');

            return $incident->refresh();
        });
    }

    private function notifyNewIncident(Incident $incident): void
    {
        $roles = ['engineer'];

        if (in_array($incident->severity, ['High', 'Critical'], true)) {
            $roles[] = 'supervisor';
        }

        $this->notifyByRoles($roles, $incident, 'new_incident', 'New incident reported');
    }

    private function notifyHandler(Incident $incident, string $type, string $message): void
    {
        if (!$incident->handled_by) {
            return;
        }

        $this->notifyUsers([$incident->handled_by], $incident, $type, $message);
    }

    private function notifyReporter(Incident $incident, string $type, string $message): void
    {
        $this->notifyUsers([$incident->reported_by], $incident, $type, $message);
    }

    /**
     * @param array<int, string> $roles
     */
    private function notifyByRoles(array $roles, Incident $incident, string $type, string $message): void
    {
        $userIds = User::query()
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        if ($userIds === []) {
            return;
        }

        $this->notifyUsers($userIds, $incident, $type, $message);
    }

    /**
     * @param array<int, int> $userIds
     */
    private function notifyUsers(array $userIds, Incident $incident, string $type, string $message): void
    {
        $now = now()->toDateTimeString();

        $rows = array_map(static function (int $userId) use ($incident, $type, $message, $now): array {
            return [
                'user_id' => $userId,
                'incident_id' => $incident->incident_id,
                'type' => $type,
                'message' => $message,
                'is_read' => false,
                'created_at' => $now,
                'read_at' => null,
            ];
        }, $userIds);

        Notification::insert($rows);
    }

    private function writeAudit(
        Incident $incident,
        User $user,
        string $action,
        ?string $details,
        array $oldValue,
        array $newValue,
        ?string $ipAddress
    ): void {
        AuditLog::create([
            'incident_id' => $incident->incident_id,
            'user_id' => $user->id,
            'action' => $action,
            'action_details' => $details,
            'old_value' => $oldValue === [] ? null : json_encode($oldValue),
            'new_value' => $newValue === [] ? null : json_encode($newValue),
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    /**
     * @param array<int, string> $allowed
     */
    private function assertStatus(Incident $incident, array $allowed): void
    {
        if (!in_array($incident->status, $allowed, true)) {
            $allowedList = implode(', ', $allowed);
            throw new RuntimeException("Invalid status '{$incident->status}', allowed: {$allowedList}.");
        }
    }

    private function generateIncidentCode($detectedAt): string
    {
        $date = $detectedAt instanceof \DateTimeInterface
            ? $detectedAt
            : new \DateTimeImmutable((string) $detectedAt);

        $prefix = $date->format('Ymd');
        $count = Incident::query()
            ->whereDate('detected_at', $date->format('Y-m-d'))
            ->count();

        $sequence = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "INC-{$prefix}-{$sequence}";
    }
}
