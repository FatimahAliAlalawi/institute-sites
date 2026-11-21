<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/** مستخدم لوحة التحكم */
class Admin extends Model
{
    protected $fillable = ['name', 'username', 'email', 'password', 'role_id', 'is_super', 'is_active', 'last_login_at'];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'is_super' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /** هل يملك المستخدم هذه الصلاحية؟ المدير العام يملك كل الصلاحيات */
    public function allows(?string $permission): bool
    {
        if ($permission === null || $this->is_super) {
            return true;
        }

        return in_array($permission, (array) ($this->role?->permissions ?? []), true);
    }

    /** مدرب فقط: يملك التحضير ولا يملك إدارة البرامج، فيرى دوراته فقط */
    public function isTrainerOnly(): bool
    {
        return ! $this->allows('programs') && $this->allows('course_attendance');
    }

    public function canOpenRoute(?string $routeName): bool
    {
        return $this->allows(Permissions::requiredFor($routeName));
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->is_super ? 'مدير عام (كل الصلاحيات)' : ($this->role?->name ?? 'بدون دور');
    }
}
