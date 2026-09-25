<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'icon', 'sort_order', 'is_active', 'parent_id'])]
class Category extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * 父分类关系
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 子分类关系
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->ordered();
    }

    /**
     * 只获取激活的分类
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 按排序和名称排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * 只获取根分类（无父分类）
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
