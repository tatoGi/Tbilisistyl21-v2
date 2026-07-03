<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasUuids, HasTranslations;

    public array $translatable = ['title', 'body'];

    protected $fillable = ['title', 'body', 'content_blocks', 'slug', 'status', 'featured'];

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'featured' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
