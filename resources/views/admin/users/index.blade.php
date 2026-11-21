@extends('layouts.admin')

@section('title', 'المستخدمون والصلاحيات')

@section('actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> مستخدم جديد</a>
    <a href="{{ route('admin.roles.create') }}" class="btn"><i class="fa-solid fa-shield-halved"></i> دور جديد</a>
@endsection

@section('content')
    <div class="card">
        <h2>مستخدمو لوحة التحكم</h2>
        <p class="sub" style="margin-top:-6px">كل مستخدم يدخل بحسابه الخاص ويرى فقط الأقسام المسموحة لدوره. المدير العام يرى كل شيء.</p>
        <div class="table-wrap">
            <table>
                <tr><th>الاسم</th><th>اسم الدخول</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th></th></tr>
                @foreach ($users as $u)
                    <tr>
                        <td><b>{{ $u->name }}</b> @if ($u->id === $currentAdmin->id)<span class="badge b-new">أنت</span>@endif<div class="sub">{{ $u->email }}</div></td>
                        <td dir="ltr" style="text-align:right">{{ $u->username }}</td>
                        <td>@if ($u->is_super)<span class="badge b-accepted">مدير عام</span>@else{{ $u->role?->name ?? '—' }}@endif</td>
                        <td><span class="badge {{ $u->is_active ? 'b-on' : 'b-rejected' }}">{{ $u->is_active ? 'فعّال' : 'موقوف' }}</span></td>
                        <td class="sub">{{ $u->last_login_at?->diffForHumans() ?? 'لم يدخل بعد' }}</td>
                        <td>
                            @if (! $u->is_super || $currentAdmin->is_super)
                                <div class="actions">
                                    <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                    @if ($u->id !== $currentAdmin->id)
                                        @include('admin.partials.delete-button', ['action' => route('admin.users.destroy', $u), 'label' => '', 'confirm' => 'حذف حساب '.$u->name.'؟'])
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <div class="card">
        <h2>الأدوار والصلاحيات</h2>
        <div class="table-wrap">
            <table>
                <tr><th>الدور</th><th>الصلاحيات</th><th>المستخدمون</th><th></th></tr>
                <tr>
                    <td><b>مدير عام</b><div class="sub">دور ثابت</div></td>
                    <td class="sub">كل الصلاحيات، بما فيها إدارة المستخدمين</td>
                    <td>{{ $users->where('is_super', true)->count() }}</td>
                    <td></td>
                </tr>
                @foreach ($roles as $role)
                    <tr>
                        <td><b>{{ $role->name }}</b><div class="sub">{{ $role->description }}</div></td>
                        <td>
                            @foreach ((array) $role->permissions as $p)
                                <span class="badge b-off" style="margin:2px">{{ \App\Support\Permissions::label($p) }}</span>
                            @endforeach
                        </td>
                        <td>{{ $role->admins_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                @include('admin.partials.delete-button', ['action' => route('admin.roles.destroy', $role), 'label' => '', 'confirm' => 'حذف دور '.$role->name.'؟'])
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
