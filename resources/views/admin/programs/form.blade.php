@extends('layouts.admin')

@section('title', $program->exists ? 'تعديل: '.$program->title : 'برنامج جديد')

@section('actions')
    @if ($program->exists)
        <a href="{{ route('admin.programs.ad-card', $program) }}" target="_blank" class="btn"><i class="fa-solid fa-bullhorn"></i> بطاقة الإعلان و QR</a>
        <a href="{{ route('programs.show', $program) }}" target="_blank" class="btn"><i class="fa-regular fa-eye"></i> عرض في الموقع</a>
    @endif
@endsection

@section('content')
    <form method="post" enctype="multipart/form-data" action="{{ $program->exists ? route('admin.programs.update', $program) : route('admin.programs.store') }}">
        @csrf
        @if ($program->exists) @method('PUT') @endif

        <div class="card">
            <h2>المعلومات الأساسية</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="title">اسم البرنامج *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $program->title) }}" required>
                </div>
                <div class="field">
                    <label for="category">التصنيف *</label>
                    <select id="category" name="category">
                        @foreach (\App\Models\Program::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $program->category) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field full">
                    <label for="summary">وصف مختصر</label>
                    <input type="text" id="summary" name="summary" value="{{ old('summary', $program->summary) }}" maxlength="500">
                    <div class="hint">يظهر في بطاقة البرنامج وتحت العنوان.</div>
                </div>
                <div class="field full">
                    <label for="description">الفكرة العامة</label>
                    <textarea id="description" name="description">{{ old('description', $program->description) }}</textarea>
                </div>
                <div class="field full">
                    <label for="goal">الهدف من البرنامج</label>
                    <textarea id="goal" name="goal">{{ old('goal', $program->goal) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>التفاصيل</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="target_audience">الفئة المستهدفة</label>
                    <input type="text" id="target_audience" name="target_audience" value="{{ old('target_audience', $program->target_audience) }}">
                </div>
                <div class="field">
                    <label for="duration">مدة التنفيذ</label>
                    <input type="text" id="duration" name="duration" value="{{ old('duration', $program->duration) }}" placeholder="مثال: 10 أيام (أربع ساعات يوميًا)">
                </div>
                <div class="field">
                    <label for="hours">عدد الساعات</label>
                    <input type="text" id="hours" name="hours" value="{{ old('hours', $program->hours) }}" placeholder="مثال: 40 ساعة">
                </div>
                <div class="field">
                    <label for="seats">عدد المقاعد / المستفيدين</label>
                    <input type="number" id="seats" name="seats" min="0" value="{{ old('seats', $program->seats) }}">
                    <div class="hint">يُغلق التسجيل تلقائيًا عند اكتمالها.@if ($program->exists) محجوز الآن: {{ $program->seats_taken }}.@endif</div>
                </div>
                <div class="field">
                    <label for="location">المكان</label>
                    <input type="text" id="location" name="location" value="{{ old('location', $program->location) }}">
                </div>
                <div class="field">
                    <label for="mode">نوع التدريب</label>
                    <select id="mode" name="mode">
                        @foreach (\App\Models\Program::MODES as $mode)
                            <option @selected(old('mode', $program->mode) === $mode)>{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="price">الرسوم (ريال)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="{{ old('price', $program->price) }}">
                    <div class="hint">اكتب 0 ليظهر "مجاني"، أو اتركه فارغًا لإخفاء الرسوم.</div>
                </div>
                <div class="field">
                    <label for="start_date">تاريخ البدء</label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $program->start_date?->format('Y-m-d')) }}">
                </div>
                <div class="field full">
                    <label for="certificate">الشهادة</label>
                    <input type="text" id="certificate" name="certificate" value="{{ old('certificate', $program->certificate) }}">
                </div>
                <div class="field full">
                    <label for="steps">مسار التنفيذ (الخطوات)</label>
                    <textarea id="steps" name="steps" class="tall" placeholder="حدّد مسارك | ترشيح المستفيدين وتقييم الميول&#10;تعلّم | التدريب على المهارات">{{ old('steps', $program->steps) }}</textarea>
                    <div class="hint">كل خطوة في سطر بالشكل: <b>عنوان الخطوة | وصفها</b></div>
                </div>
                <div class="field full">
                    <label for="outcomes">المخرجات المتوقعة</label>
                    <textarea id="outcomes" name="outcomes">{{ old('outcomes', $program->outcomes) }}</textarea>
                    <div class="hint">كل مخرج في سطر.</div>
                </div>
            </div>
        </div>

        @php($approver = isset($currentAdmin) && $currentAdmin->allows('approvals'))
        <div class="card">
            <h2>الخطة التدريبية وبيانات الإعلان المعتمد</h2>
            <p class="sub" style="margin-top:-6px">هذه البيانات تظهر في صفحة «خطة الأفراد» وفي بطاقة الإعلان. اسم البرنامج أعلاه هو <b>الاسم المعتمد</b> للدورة.</p>
            <div class="form-grid">
                <div class="field">
                    <label for="accreditation_no">رقم الاعتماد</label>
                    <input type="text" id="accreditation_no" name="accreditation_no" value="{{ old('accreditation_no', $program->accreditation_no) }}" dir="ltr" placeholder="رقم اعتماد المؤسسة العامة للتدريب">
                </div>
                <div class="field">
                    <label for="sale_price">السعر بعد الخصم (ريال)</label>
                    <input type="number" id="sale_price" name="sale_price" step="0.01" min="0" value="{{ old('sale_price', $program->sale_price) }}">
                    <div class="hint">اتركه فارغًا إن لم يوجد خصم. يجب أن يكون أقل من الرسوم.</div>
                </div>
                <div class="field">
                    <label for="offer_ends_at">تاريخ انتهاء العرض / التسجيل</label>
                    <input type="date" id="offer_ends_at" name="offer_ends_at" value="{{ old('offer_ends_at', $program->offer_ends_at?->format('Y-m-d')) }}">
                    <div class="hint">بعد هذا التاريخ يختفي الخصم تلقائيًا من الموقع.</div>
                </div>
                <div class="field">
                    <label for="contact_phone">رقم التواصل في الإعلان</label>
                    <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $program->contact_phone) }}" dir="ltr" placeholder="{{ setting('whatsapp') ?: '05XXXXXXXX' }}">
                    <div class="hint">إن تُرك فارغًا يُستخدم واتساب المعهد من الإعدادات.</div>
                </div>
                <div class="field">
                    <label for="plan_status">الخطة التدريبية (3 أشهر)</label>
                    <select id="plan_status" name="plan_status">
                        @foreach (\App\Models\Program::APPROVAL_STATUSES as $key => $label)
                            @if ($approver || in_array($key, ['none', 'pending'], true) || $key === ($program->plan_status ?: 'none'))
                                <option value="{{ $key }}" @selected(old('plan_status', $program->plan_status ?: 'none') === $key)>{{ $key === 'pending' && ! $approver ? 'إرسال للإدارة للاعتماد' : $label }}</option>
                            @endif
                        @endforeach
                    </select>
                    @if ($program->plan_approved_at)<div class="hint">اعتمدها {{ $program->plan_approved_by }} — {{ $program->plan_approved_at->format('Y-m-d') }}</div>@endif
                </div>
                <div class="field">
                    <label for="ad_status">الإعلان</label>
                    <select id="ad_status" name="ad_status">
                        @foreach (\App\Models\Program::APPROVAL_STATUSES as $key => $label)
                            @if ($approver || in_array($key, ['none', 'pending'], true) || $key === ($program->ad_status ?: 'none'))
                                <option value="{{ $key }}" @selected(old('ad_status', $program->ad_status ?: 'none') === $key)>{{ $key === 'pending' && ! $approver ? 'إرسال للإدارة للاعتماد' : ($key === 'none' ? 'لا يوجد إعلان' : $label) }}</option>
                            @endif
                        @endforeach
                    </select>
                    @if ($program->ad_approved_at)<div class="hint">اعتمده {{ $program->ad_approved_by }} — {{ $program->ad_approved_at->format('Y-m-d') }}</div>@endif
                </div>
                @if ($approver)
                    <div class="field full">
                        <label for="approval_note">ملاحظة الإدارة</label>
                        <input type="text" id="approval_note" name="approval_note" value="{{ old('approval_note', $program->approval_note) }}" maxlength="1000" placeholder="سبب الرفض أو المطلوب تعديله">
                    </div>
                @elseif ($program->approval_note)
                    <div class="field full"><div class="alert alert-error" style="margin:0"><b>ملاحظة الإدارة:</b> {{ $program->approval_note }}</div></div>
                @endif
                <div class="field full hint">
                    @if ($approver)
                        لديك صلاحية الاعتماد. تظهر الدورة في «خطة الأفراد» فقط إذا كانت الخطة <b>معتمدة</b> ولها تاريخ بدء.
                    @else
                        الاعتماد للإدارة فقط. أي تعديل على بيانات معتمدة (الاسم، التاريخ، السعر، الخصم، رقم الاعتماد...) يعيدها تلقائيًا لانتظار الاعتماد.
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <h2>الصورة والنشر</h2>
            <div class="form-grid">
                @include('admin.partials.image-field', ['name' => 'image', 'label' => 'صورة البرنامج', 'value' => $program->image])
                <div class="field">
                    <label for="external_link">رابط تسجيل خارجي (اختياري)</label>
                    <input type="url" id="external_link" name="external_link" value="{{ old('external_link', $program->external_link) }}" placeholder="https://forms.gle/...">
                    <div class="hint">إن وُضع رابط، زر "سجّل الآن" يفتحه بدل نموذج الموقع.</div>
                </div>
                <div class="field">
                    <label for="slug">الرابط المختصر</label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $program->slug) }}">
                    <div class="hint">يُولَّد تلقائيًا من الاسم إن تُرك فارغًا.</div>
                </div>
                <div class="field">
                    <label for="sort">الترتيب</label>
                    <input type="number" id="sort" name="sort" value="{{ old('sort', $program->sort ?? 0) }}">
                    <div class="hint">الأصغر يظهر أولًا.</div>
                </div>
                <div class="field full checks">
                    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $program->is_active))> منشور في الموقع</label>
                    <label><input type="checkbox" name="registration_open" value="1" @checked(old('registration_open', $program->registration_open))> التسجيل مفتوح</label>
                    <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $program->is_featured))> يظهر في الصفحة الرئيسية</label>
                </div>
            </div>
        </div>

        <div class="form-foot sticky-foot">
            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ</button>
            <a href="{{ route('admin.programs.index') }}" class="btn">رجوع</a>
        </div>
    </form>
@endsection
