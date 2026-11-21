@extends('layouts.admin')

@section('title', 'الطالب: '.$student->name)

@section('actions')
    <a href="{{ route('admin.students.index') }}" class="btn">رجوع للقائمة</a>
@endsection

@section('content')
    <div class="cols">
        <div>
            <div class="card">
                <h2>الدورات المسجّل بها</h2>
                @if ($enrollments->isEmpty())
                    <div class="empty">غير مسجّل في أي دورة.</div>
                @else
                    <table>
                        <tr><th>الدورة</th><th>الحالة</th><th>التقدم</th><th>الدفع</th></tr>
                        @foreach ($enrollments as $e)
                            <tr>
                                <td><b>{{ $e->course->title }}</b><div class="sub">{{ $e->created_at->format('Y-m-d') }}</div></td>
                                <td>
                                    <span class="badge {{ ['active' => 'b-accepted', 'pending' => 'b-contacted', 'cancelled' => 'b-rejected'][$e->status] ?? 'b-off' }}">{{ $e->status_label }}</span>
                                    @if ($e->completed_at)<span class="badge b-new">مكتملة</span>@endif
                                </td>
                                <td>{{ $e->progress['percent'] }}% <span class="sub">({{ $e->progress['done'] }}/{{ $e->progress['total'] }})</span></td>
                                <td class="sub">{{ $e->method_label }} @if ($e->amount > 0)· {{ number_format((float) $e->amount, 2) }} ر.س @endif</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
                @if ($courses->isNotEmpty())
                    <form method="post" action="{{ route('admin.students.enroll', $student) }}" class="actions" style="margin-top:16px">
                        @csrf
                        <select name="course_id" style="max-width:320px" required>
                            <option value="">— اختر دورة —</option>
                            @foreach ($courses as $c)<option value="{{ $c->id }}">{{ $c->title }}</option>@endforeach
                        </select>
                        <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-plus"></i> تسجيل يدوي في دورة</button>
                    </form>
                @endif
            </div>

            <div class="card">
                <h2>نتائج الاختبارات</h2>
                @forelse ($attempts as $a)
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)">
                        <span>{{ $a->quiz?->title }} <span class="sub">{{ $a->created_at->format('Y-m-d') }}</span></span>
                        <span><b>{{ $a->score }}%</b> <span class="badge {{ $a->passed ? 'b-accepted' : 'b-rejected' }}">{{ $a->passed ? 'ناجح' : 'لم يجتز' }}</span></span>
                    </div>
                @empty
                    <div class="empty">لا توجد محاولات.</div>
                @endforelse
            </div>

            <div class="card">
                <h2>الشهادات</h2>
                @forelse ($certificates as $c)
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)">
                        <span>{{ $c->course?->title }}</span>
                        <a href="{{ route('certificates.show', $c->code) }}" target="_blank" dir="ltr">{{ $c->code }}</a>
                    </div>
                @empty
                    <div class="empty">لا توجد شهادات.</div>
                @endforelse
            </div>
        </div>

        <div>
            <form class="card" method="post" action="{{ route('admin.students.update', $student) }}">
                @csrf @method('PUT')
                <h2>بيانات الحساب</h2>
                <div class="field" style="margin-bottom:10px"><label>الاسم</label><input type="text" name="name" value="{{ old('name', $student->name) }}" required></div>
                <div class="field" style="margin-bottom:10px"><label>البريد الإلكتروني</label><input type="email" name="email" value="{{ old('email', $student->email) }}" required dir="ltr"></div>
                <div class="field" style="margin-bottom:10px"><label>الجوال</label><input type="text" name="phone" value="{{ old('phone', $student->phone) }}" dir="ltr"></div>
                <div class="field" style="margin-bottom:10px"><label>رقم الهوية / الإقامة</label><input type="text" name="national_id" value="{{ old('national_id', $student->national_id) }}" dir="ltr" maxlength="10"></div>
                <div class="field" style="margin-bottom:10px"><label>تاريخ الميلاد</label><input type="date" name="birth_date" value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}"></div>
                <div class="field" style="margin-bottom:10px">
                    <label>الجنس</label>
                    <select name="gender"><option value="">—</option>@foreach (\App\Models\User::GENDERS as $g)<option @selected(old('gender', $student->gender) === $g)>{{ $g }}</option>@endforeach</select>
                </div>
                <div class="field" style="margin-bottom:10px"><label>المؤهل</label><input type="text" name="qualification" value="{{ old('qualification', $student->qualification) }}"></div>
                <div class="field" style="margin-bottom:10px"><label>كلمة مرور جديدة</label><input type="text" name="password" minlength="8" dir="ltr" placeholder="اتركها فارغة لعدم التغيير"></div>
                @php $ownDays = \App\Support\DailyRelease::parse($student->study_days); @endphp
                <div class="field" style="margin-bottom:10px">
                    <label>أيام الدراسة (للدورات المسجلة)</label>
                    <div class="checks" style="flex-direction:column;align-items:flex-start;gap:6px">
                        <label><input type="radio" name="days_mode" value="global" @checked(! $ownDays)> الإعداد العام ({{ \App\Support\DailyRelease::label(\App\Support\DailyRelease::globalDays()) }})</label>
                        <label><input type="radio" name="days_mode" value="custom" @checked((bool) $ownDays)> أيام خاصة بهذا المتدرب:</label>
                    </div>
                    <div class="checks" style="margin-top:6px">
                        @foreach (\App\Support\DailyRelease::DAYS as $num => $dayName)
                            <label style="font-weight:500"><input type="checkbox" name="study_days[]" value="{{ $num }}" @checked(in_array($num, $ownDays ?: \App\Support\DailyRelease::globalDays(), true))> {{ $dayName }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="checks"><label><input type="checkbox" name="is_active" value="1" @checked($student->is_active)> الحساب مفعّل</label></div>
                <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ</button></div>
                @if ($student->extra)
                    <dl class="info" style="margin-top:12px">
                        @include('admin.partials.extra-answers', ['form' => 'signup', 'extra' => $student->extra])
                    </dl>
                @endif
                <div class="sub" style="margin-top:10px">تاريخ التسجيل: {{ $student->created_at->format('Y-m-d') }} · آخر دخول: {{ $student->last_login_at?->format('Y-m-d H:i') ?? '—' }}</div>
            </form>
            <div class="card">
                @include('admin.partials.delete-button', ['action' => route('admin.students.destroy', $student), 'label' => 'حذف الطالب نهائيًا', 'confirm' => 'سيتم حذف الطالب واشتراكاته وشهاداته ونتائجه. متأكد؟'])
            </div>
        </div>
    </div>
@endsection
