<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasUuids;

    protected $fillable = ['filename', 'path', 'mime_type', 'size', 'alt'];

    public function getUrlAttribute(): string
    {
        return '/storage/media/' . $this->filename;
    }
}
