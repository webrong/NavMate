<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Storage::url($value) : null,
        );
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function layout(): HasOne
    {
        return $this->hasOne(UserLayout::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(UserLink::class)->orderBy('sort_order');
    }
}
