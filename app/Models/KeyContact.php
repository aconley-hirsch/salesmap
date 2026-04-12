<?php

namespace App\Models;

use Database\Factories\KeyContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'title', 'email', 'phone', 'notes', 'group_name', 'group_order', 'sort_order', 'is_active'])]
class KeyContact extends Model
{
    /** @use HasFactory<KeyContactFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('group_order')->orderBy('sort_order');
    }
}
