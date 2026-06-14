<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClickLog extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $fillable = ['site_id', 'ip_address', 'user_agent', 'clicked_at'];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * Get the prunable model query.
     * Removes click logs older than 90 days.
     */
    public function prunable()
    {
        return static::where('clicked_at', '<', now()->subDays(90));
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
