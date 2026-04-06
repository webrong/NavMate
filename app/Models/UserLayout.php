<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLayout extends Model
{
    protected $fillable = ['user_id', 'layout_data'];

    protected function casts(): array
    {
        return [
            'layout_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
