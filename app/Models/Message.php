<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'subject', 'body', 'is_read', 'ip', 'extra'];

    protected $casts = [
        'is_read' => 'boolean',
        'extra' => 'array',
    ];
}
