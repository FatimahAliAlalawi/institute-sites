@extends('layouts.admin')

@section('title', 'طلب تسجيل #'.$registration->id)

@section('actions')
    <a href="{{ route('admin.registrations.index') }}" class="btn">رجوع للقائمة</a>
@endsection

@section('content')
    <div class="cols">
        <div class="card">
            <h2>بيانات المتقدم</h2>
            <dl class="info">
                <dt>الاسم</dt><dd>{{ $registration->name }}</dd>
                <dt>الجوال</dt><dd dir="ltr" style="text-align:right"><a href="tel:{{ $registration->phone }}">{{ $registration->phone }}</a></dd>
                <dt>البريد الإلكتروني</dt><dd>{{ $registration->email ?: '—' }}</dd>
                <dt>المدينة</dt><dd>{{ $registration->city ?: '—' }}</dd>
                <dt>الجنس</dt><dd>{{ $registration->gender ?: '—' }}</dd>
                <dt>المؤهل</dt><dd>{{ $registration->qualification ?: '—' }}</dd>
                <dt>الحالة الوظيفية</dt><dd>{{ $registration->employment_status ?: '—' }}</dd>
                <dt>البرنامج</dt>
                <dd>
                    @if ($registration->program)
                        <a href="{{ route('admin.programs.edit', $registration->program) }}">{{ $registration->program->title }}</a>
                    @else
                        تسجيل اهتمام عام
                    @endif
                </dd>
                @if ($program && $program->seats)
                    <dt>المقاعد</dt>
                    <dd>
                        محجوز {{ $program->seat_registrations_count }} من {{ $program->seats }}
                        @if ($program->seat_registrations_count >= $program->seats)<span class="badge b-rejected">مكتملة</span>@else<span class="badge b-on">باقي {{ $program->seats - $program->seat_registrations_count }}</span>@endif
                    </dd>
                @endif
                <dt>ملاحظات المتقدم</dt><dd>{{ $registration->notes ?: '—' }}</dd>
                @include('admin.partials.extra-answers', ['form' => 'registration', 'extra' => $registration->extra])
                <dt>تاريخ الطلب</dt><dd>{{ $registration->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
            <div class="actions" style="margin-top:18px">
                <a href="{{ $registration->whatsapp_link }}" target="_blank" rel="noopener" class="btn"><i class="fa-brands fa-whatsapp"></i> مراسلة واتساب</a>
                @if ($registration->email)
                    <a href="mailto:{{ $registration->email }}" class="btn"><i class="fa-regular fa-envelope"></i> مراسلة بالبريد</a>
                @endif
                @if ($registration->program)
                    <a href="{{ route('admin.programs.ad-card', $registration->program) }}" target="_blank" class="btn"><i class="fa-solid fa-bullhorn"></i> بيانات الدورة للرد</a>
                    <a href="{{ route('admin.programs.ops', $registration->program) }}" class="btn"><i class="fa-solid fa-list-check"></i> تشغيل الدورة</a>
                @endif
            </div>
        </div>

        <div>
            <form class="card" method="post" action="{{ route('admin.registrations.update', $registration) }}">
                @csrf
                @method('PUT')
                <h2>المتابعة</h2>
                <div class="field" style="margin-bottom:12px">
                    <label for="status">الحالة</label>
                    <select id="status" name="status">
                        @foreach (\App\Models\Registration::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $registration->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="hint">«مقبول» تعني دفع واستلم التأكيد والفاتورة، وتحجز له مقعدًا.</div>
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label for="program_id">الدورة</label>
                    <select id="program_id" name="program_id">
                        <option value="">تسجيل عام</option>
                        @foreach (\App\Models\Program::active()->orderByRaw('start_date is null')->orderBy('start_date')->get(['id', 'title', 'start_date']) as $p)
                            <option value="{{ $p->id }}" @selected((int) old('program_id', $registration->program_id) === $p->id)>{{ $p->title }} — {{ $p->start_date ? 'تبدأ '.$p->start_date->format('Y-m-d') : 'بدون تاريخ بداية' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label for="follow_up_at">موعد المتابعة القادمة</label>
                    <input type="date" id="follow_up_at" name="follow_up_at" value="{{ old('follow_up_at', $registration->follow_up_at?->format('Y-m-d')) }}">
                    <div class="hint">يُضبط بعد يومين تلقائيًا عند «تم التواصل»، ويُمسح عند إغلاق الطلب.</div>
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label for="source">المصدر</label>
                    <select id="source" name="source">
                        <option value="">—</option>
                        @foreach (\App\Models\Registration::SOURCES as $src)
                            <option @selected(old('source', $registration->source) === $src)>{{ $src }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label for="invoice_no">رقم الفاتورة</label>
                    <input type="text" id="invoice_no" name="invoice_no" value="{{ old('invoice_no', $registration->invoice_no) }}" dir="ltr">
                    <div class="hint">كتابة رقم الفاتورة = اعتمادها: يُقبل المتدرب ويُضاف للدورة المختارة تلقائيًا (لازم تكون الدورة محددة ولها تاريخ بداية).</div>
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label for="attendance">تأكيد الحضور (قبل الدورة بيومين)</label>
                    <select id="attendance" name="attendance">
                        <option value="">لم يُرسل التذكير بعد</option>
                        @foreach (\App\Models\Registration::ATTENDANCE as $key => $label)
                            <option value="{{ $key }}" @selected(old('attendance', $registration->attendance) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label for="coupon_code">كود الخصم المرسل بعد الدورة</label>
                    <input type="text" id="coupon_code" name="coupon_code" value="{{ old('coupon_code', $registration->coupon_code) }}" dir="ltr">
                </div>
                <div class="field">
                    <label for="admin_notes">ملاحظات داخلية</label>
                    <textarea id="admin_notes" name="admin_notes">{{ old('admin_notes', $registration->admin_notes) }}</textarea>
                </div>
                <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ</button></div>
            </form>
            <div class="card">
                @include('admin.partials.delete-button', ['action' => route('admin.registrations.destroy', $registration), 'label' => 'حذف الطلب'])
            </div>
        </div>
    </div>
@endsection
