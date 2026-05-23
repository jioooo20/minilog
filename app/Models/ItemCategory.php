<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    use HasFactory;

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'category_code',
        'category_name',
        'description',
        'is_active',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }
}
