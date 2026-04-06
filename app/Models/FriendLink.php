<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FriendLink extends Model
{
    protected $fillable = ['name', 'url', 'logo', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
