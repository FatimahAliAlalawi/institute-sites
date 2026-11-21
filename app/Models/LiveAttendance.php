<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveAttendance extends Model
{
    protected $fillable = ['live_session_id', 'user_id', 'joined_at', 'join_count'];

    protected $casts = ['joined_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(LiveSession::class, 'live_session_id');
    }
}
