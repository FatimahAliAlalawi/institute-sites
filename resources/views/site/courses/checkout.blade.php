@extends('layouts.site')

@section('title', 'إتمام الاشتراك — '.$course->title)

@if ($moyasarOrder)
    @push('head')
        <link rel="stylesheet" href="https://cdn.moyasar.com/mpf/{{ \App\Support\Moyasar::JS_VERSION }}/moyasar.css">
    @endpush
@endif

@section('content')
    @include('partials.page-head', ['title' => 'إتمام الاشتراك', 'crumb' => $course->title, 'subtitle' => $course->title])

    <section class="section">
        <div class="container" style="max-width:860px">
            @if ($errors->has('payment'))
                <div class="alert alert-error">{{ $errors->first('payment') }}</div>
            @endif
            @if ($enrollment?->status === 'pending')
                <div class="alert alert-success">استلمنا إيصالك السابق وهو قيد المراجعة. يمكنك رفع إيصال جديد أو الدفع إلكترونيًا.</div>
            @endif

            {{-- ملخص الطلب --}}
            <div class="form-card" style="margin-bottom:20px">
                <h3 style="margin-top:0">ملخص الطلب</h3>
                <div class="bank-box" style="border-style:solid;margin-bottom:14px">
                    <dl>
                        <dt>الدورة</dt><dd>{{ $course->title }}</dd>
                        <dt>السعر</dt>
                        <dd>
                            {{ number_format($quote['subtotal'], 2) }} ر.س
                            @if ($course->has_discount)<del class="muted" style="font-weight:400">{{ number_format((float) $course->price, 2) }}</del> <span class="pill done">عرض</span>@endif
                        </dd>
                        @if ($quote['coupon'])
                            <dt>خصم الكوبون ({{ $quote['coupon']->code }})</dt><dd style="color:#16794a">− {{ number_format($quote['discount'], 2) }} ر.س</dd>
                        @endif
                        <dt>الإجمالي</dt><dd style="font-size:22px;color:var(--primary)">{{ number_format($quote['total'], 2) }} ر.س</dd>
                    </dl>
                    @if ($course->has_discount && $course->sale_ends_at)
                        <p class="muted" style="margin:8px 0 0"><i class="fa-regular fa-clock"></i> العرض ينتهي {{ $course->sale_ends_at->translatedFormat('j F Y — g:i a') }}</p>
                    @endif
                </div>

                <form method="post" action="{{ route('courses.coupon', $course) }}" style="display:flex;gap:8px;flex-wrap:wrap">
                    @csrf
                    @if ($quote['coupon'])
                        <input type="hidden" name="remove" value="1">
                        <span class="pill done" style="padding:8px 14px"><i class="fa-solid fa-tag"></i> {{ $quote['coupon']->code }}</span>
                        <button class="btn btn-outline" type="submit" style="padding:8px 16px">إزالة الكوبون</button>
                    @else
                        <input type="text" name="code" value="{{ old('code') }}" placeholder="عندك كود خصم؟" dir="ltr" style="flex:1;min-width:180px;text-transform:uppercase">
                        <button class="btn btn-outline" type="submit">تطبيق</button>
                    @endif
                </form>
                @error('code')<div class="error">{{ $message }}</div>@enderror
            </div>

            @if ($quote['total'] <= 0)
                <div class="form-card center">
                    <h3>الكوبون يغطي كامل السعر 🎉</h3>
                    <form method="post" action="{{ route('courses.free', $course) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">تفعيل الدورة الآن</button>
                    </form>
                </div>
            @else
                {{-- الدفع الإلكتروني --}}
                @if ($moyasarOrder)
                    <div class="form-card" style="margin-bottom:20px">
                        <h3 style="margin-top:0"><i class="fa-regular fa-credit-card"></i> الدفع الإلكتروني</h3>
                        <p class="muted" style="margin-top:-6px">مدى · فيزا · ماستركارد{{ in_array('applepay', \App\Support\Moyasar::methods()) ? ' · Apple Pay' : '' }}{{ in_array('stcpay', \App\Support\Moyasar::methods()) ? ' · STC Pay' : '' }} — تفعيل فوري للدورة بعد الدفع. (رقم الطلب {{ $moyasarOrder->number }})</p>
                        <div class="mysr-form"></div>
                    </div>
                    @push('scripts')
                        <script src="https://cdn.moyasar.com/mpf/{{ \App\Support\Moyasar::JS_VERSION }}/moyasar.js"></script>
                        <script>
                            Moyasar.init({
                                element: '.mysr-form',
                                language: 'ar',
                                amount: {{ (int) round($moyasarOrder->total * 100) }},
                                currency: 'SAR',
                                description: @json('طلب '.$moyasarOrder->number.' — '.$course->title),
                                publishable_api_key: @json(setting('moyasar_publishable_key')),
                                callback_url: @json(route('payments.moyasar.callback', $moyasarOrder)),
                                methods: @json(\App\Support\Moyasar::methods()),
                                metadata: { order_id: '{{ $moyasarOrder->id }}', order_number: @json($moyasarOrder->number) },
                                apple_pay: { country: 'SA', label: @json(setting('site_name')), validate_merchant_url: 'https://api.moyasar.com/v1/applepay/initiate' }
                            });
                        </script>
                    @endpush
                @endif

                {{-- التحويل البنكي --}}
                @if ($bankEnabled)
                    <div class="form-card">
                        <h3 style="margin-top:0"><i class="fa-solid fa-building-columns"></i> {{ $moyasarOrder ? 'أو: ' : '' }}التحويل البنكي</h3>
                        <div class="bank-box">
                            <dl>
                                @if (setting('bank_name'))<dt>البنك</dt><dd>{{ setting('bank_name') }}</dd>@endif
                                @if (setting('bank_account_name'))<dt>اسم الحساب</dt><dd>{{ setting('bank_account_name') }}</dd>@endif
                                @if (setting('bank_iban'))<dt>رقم الآيبان</dt><dd dir="ltr" style="text-align:right">{{ setting('bank_iban') }}</dd>@endif
                                @if (setting('bank_account_no'))<dt>رقم الحساب</dt><dd dir="ltr" style="text-align:right">{{ setting('bank_account_no') }}</dd>@endif
                                <dt>المبلغ</dt><dd>{{ number_format($quote['total'], 2) }} ر.س</dd>
                            </dl>
                            @if (! setting('bank_iban') && ! setting('bank_account_no'))
                                <p class="muted" style="margin:10px 0 0">بيانات الحساب البنكي ستُضاف قريبًا — تواصل معنا لإتمام الدفع.</p>
                            @endif
                            @if (setting('payment_instructions'))<p class="prose" style="margin:12px 0 0">{{ setting('payment_instructions') }}</p>@endif
                        </div>
                        <form class="form" method="post" enctype="multipart/form-data" action="{{ route('courses.pay', $course) }}">
                            @csrf
                            <div>
                                <label for="receipt">صورة الإيصال أو ملف PDF <span class="req">*</span></label>
                                <input type="file" id="receipt" name="receipt" accept="image/*,application/pdf" required>
                                @error('receipt')<div class="error">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label for="note">ملاحظات (اختياري)</label>
                                <textarea id="note" name="note" style="min-height:70px" placeholder="مثال: اسم المحوِّل أو تاريخ التحويل">{{ old('note') }}</textarea>
                            </div>
                            <button class="btn btn-primary btn-block" type="submit">إرسال الإيصال وطلب التفعيل</button>
                        </form>
                    </div>
                @endif

                @if (! $moyasarOrder && ! $bankEnabled)
                    <div class="alert alert-error">الدفع غير متاح حاليًا — تواصل معنا لإتمام الاشتراك.</div>
                @endif
            @endif
        </div>
    </section>
@endsection
