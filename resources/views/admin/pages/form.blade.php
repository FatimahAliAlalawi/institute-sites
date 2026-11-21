@extends('layouts.admin')

@section('title', $page->exists ? 'تعديل صفحة: '.$page->title : 'صفحة جديدة')

@section('actions')
    @if ($page->exists)<a href="{{ route('pages.show', $page) }}" target="_blank" class="btn"><i class="fa-regular fa-eye"></i> عرض</a>@endif
    <a href="{{ route('admin.pages.index') }}" class="btn">رجوع</a>
@endsection

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
    <style>
        #editor { min-height: 420px; font-family: inherit; font-size: 16px; background: #fff; }
        .ql-toolbar.ql-snow, .ql-container.ql-snow { border-color: var(--line); }
        .ql-toolbar.ql-snow { border-radius: 10px 10px 0 0; background: #faf9fd; }
        .ql-container.ql-snow { border-radius: 0 0 10px 10px; }
        .ql-editor { text-align: right; direction: rtl; line-height: 1.9; }
        .ql-editor.ql-blank::before { right: 15px; left: auto; }
    </style>

    <form method="post" enctype="multipart/form-data" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" id="pageForm">
        @csrf
        @if ($page->exists) @method('PUT') @endif
        <div class="card">
            <div class="form-grid">
                <div class="field"><label>عنوان الصفحة *</label><input type="text" name="title" value="{{ old('title', $page->title) }}" required></div>
                <div class="field"><label>الرابط المختصر</label><input type="text" name="slug" value="{{ old('slug', $page->slug) }}" placeholder="يُولّد تلقائيًا من العنوان"><div class="hint">رابط الصفحة: /p/الرابط</div></div>
                <div class="field full"><label>نص تحت العنوان (اختياري)</label><input type="text" name="subtitle" value="{{ old('subtitle', $page->subtitle) }}"></div>
            </div>
        </div>

        <div class="card">
            <h2>محتوى الصفحة</h2>
            <p class="sub" style="margin-top:-6px">اكتب كما في Word: عناوين، قوائم، روابط، صور (تُرفع مباشرة)، وفيديو يوتيوب. يُحفظ المحتوى بعد تنظيفه من أي كود غير آمن.</p>
            <div id="editor">{!! \App\Support\HtmlSanitizer::clean(old('content', $page->content)) !!}</div>
            <textarea name="content" id="contentInput" hidden>{{ old('content', $page->content) }}</textarea>
        </div>

        <div class="card">
            <div class="form-grid">
                @include('admin.partials.image-field', ['name' => 'cover', 'label' => 'صورة الغلاف (اختيارية)', 'value' => $page->cover])
                <div class="field"><label>وصف للبحث ومشاركة الرابط</label><textarea name="meta_description" style="min-height:70px">{{ old('meta_description', $page->meta_description) }}</textarea></div>
                <div class="field full checks">
                    <label><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))> منشورة</label>
                    <label><input type="checkbox" name="show_header" value="1" @checked(old('show_header', $page->show_header))> إظهار شريط العنوان الملوّن أعلى الصفحة</label>
                    @unless ($page->exists)<label><input type="checkbox" name="add_to_menu" value="1" checked> إضافتها للقائمة العلوية</label>@endunless
                </div>
            </div>
        </div>
        <div class="form-foot sticky-foot"><button class="btn btn-primary" type="submit"><i class="fa-regular fa-floppy-disk"></i> حفظ الصفحة</button></div>
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
    <script>
        (function () {
            var quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'اكتب محتوى الصفحة هنا...',
                modules: {
                    toolbar: {
                        container: [
                            [{ header: [2, 3, 4, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
                            [{ align: [] }, { direction: 'rtl' }],
                            ['blockquote', 'link', 'image', 'video'],
                            ['clean']
                        ],
                        handlers: {
                            image: function () {
                                var input = document.createElement('input');
                                input.type = 'file';
                                input.accept = 'image/png,image/jpeg,image/webp,image/gif';
                                input.onchange = function () {
                                    var data = new FormData();
                                    data.append('image', input.files[0]);
                                    data.append('_token', document.querySelector('input[name=_token]').value);
                                    fetch(@json(route('admin.pages.upload')), { method: 'POST', body: data, headers: { 'Accept': 'application/json' } })
                                        .then(function (r) { return r.json(); })
                                        .then(function (res) {
                                            if (!res.url) { alert('تعذّر رفع الصورة'); return; }
                                            var range = quill.getSelection(true);
                                            quill.insertEmbed(range.index, 'image', res.url, 'user');
                                        })
                                        .catch(function () { alert('تعذّر رفع الصورة'); });
                                };
                                input.click();
                            }
                        }
                    }
                }
            });
            document.getElementById('pageForm').addEventListener('submit', function () {
                document.getElementById('contentInput').value = quill.root.innerHTML;
            });
        })();
    </script>
@endsection
