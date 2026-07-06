<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasUuids, HasTranslations, LogsActivity;

    public array $translatable = ['title', 'nav_label'];

    protected $fillable = [
        'title', 'nav_label', 'slug', 'route_path', 'show_in_nav',
        'show_in_footer', 'nav_order', 'footer_order', 'featured_on_home',
        'layout', 'content_blocks', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'show_in_nav' => 'boolean',
            'show_in_footer' => 'boolean',
            'featured_on_home' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeInNav(Builder $query): Builder
    {
        return $query->where('show_in_nav', true)->orderBy('nav_order');
    }

    public function scopeInFooter(Builder $query): Builder
    {
        return $query->where('show_in_footer', true)->orderBy('footer_order');
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if ($page->show_in_footer === null) {
                $page->show_in_footer = false;
            }
            if ($page->footer_order === null) {
                $page->footer_order = 100;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
