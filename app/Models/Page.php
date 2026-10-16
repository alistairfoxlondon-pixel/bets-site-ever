<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'meta_title',
        'meta_description',
        'status',
        'updated_by',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
