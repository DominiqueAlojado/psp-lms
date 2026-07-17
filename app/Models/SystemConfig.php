<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $fillable = [
        'key',
        'module',
        'label',
        'description',
        'type',
        'value',
        'is_public',
        'is_editable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_editable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
