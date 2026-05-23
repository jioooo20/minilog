<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
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
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
        'is_active' => 'boolean',
        'installation_date' => 'date',
        'last_calibration_date' => 'date',
        'calibration_due_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'item_id');
    }

    public function componentIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'component_item_id');
    }
}
