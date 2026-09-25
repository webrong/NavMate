<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// user_id is intentionally not fillable — ownership comes from
// $user->links()->create(), never from client input
#[Fillable(['title', 'url', 'description', 'favicon_url', 'sort_order'])]
class UserLink extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
