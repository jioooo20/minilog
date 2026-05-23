<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    /** @use HasFactory<\Database\Factories\IncidentFactory> */
    use HasFactory;

    protected $primaryKey = 'incident_id';

    public function getRouteKeyName(): string
    {
        return 'incident_id';
    }

    protected $fillable = [
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
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'investigating_started_at' => 'datetime',
        'repair_started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'hypothesis_approved' => 'boolean',
        'hypothesis_approved_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'incident_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'incident_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'incident_id');
    }
}
