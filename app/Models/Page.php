<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;

/** صفحة مخصصة ينشئها المدير (من المحرر المرئي) */
class Page extends Model
{
    use HasSlug;

    protected $fillable = ['title', 'slug', 'subtitle', 'content', 'meta_description', 'cover', 'show_header', 'is_published'];

    protected $casts = [
        'show_header' => 'boolean',
        'is_published' => 'boolean',
    ];
}
