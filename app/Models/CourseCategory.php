<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    protected $fillable = ['name', 'slug', 'sort'];

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            $category->slug = Post::makeSlug($category->slug ?: $category->name) ?: 'cat-'.uniqid();
        });
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }
}
