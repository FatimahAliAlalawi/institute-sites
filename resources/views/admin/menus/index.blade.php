@extends('layouts.admin')

@section('title', 'القوائم')

@section('content')
    @foreach (\App\Models\MenuItem::LOCATIONS as $location => $locationLabel)
        <div class="card">
            <h2>{{ $locationLabel }}</h2>
            @php $list = $items[$location] ?? collect(); @endphp
            @if ($list->isEmpty())
                <div class="empty" style="padding:14px">لا توجد روابط.</div>
            @else
                <table>
                    @foreach ($list as $item)
                        <tr @unless ($item->is_active) style="opacity:.5" @endunless>
                            <td style="width:80px">
                                <div class="actions" style="flex-wrap:nowrap">
                                    <form method="post" action="{{ route('admin.menus.move', $item) }}">@csrf<input type="hidden" name="dir" value="up"><button class="btn btn-sm" @disabled($loop->first)>▲</button></form>
                                    <form method="post" action="{{ route('admin.menus.move', $item) }}">@csrf<input type="hidden" name="dir" value="down"><button class="btn btn-sm" @disabled($loop->last)>▼</button></form>
                                </div>
                            </td>
                            <td>
                                <form method="post" action="{{ route('admin.menus.update', $item) }}" class="actions">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="location" value="{{ $item->location }}">
                                    <input type="hidden" name="type" value="{{ $item->type }}">
                                    <input type="hidden" name="{{ ['route' => 'route', 'page' => 'page_id', 'url' => 'url'][$item->type] }}" value="{{ $item->target }}">
                                    <input type="text" name="label" value="{{ $item->label }}" style="max-width:200px" required>
                                    <span class="sub">{{ $item->target_label }}</span>
                                    <label class="checks" style="font-weight:500"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> ظاهر</label>
                                    <label class="checks" style="font-weight:500"><input type="checkbox" name="new_tab" value="1" @checked($item->new_tab)> نافذة جديدة</label>
                                    <button class="btn btn-sm" type="submit">حفظ</button>
                                </form>
                            </td>
                            <td style="width:50px">@include('admin.partials.delete-button', ['action' => route('admin.menus.destroy', $item), 'label' => ''])</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    @endforeach

    <form class="card" method="post" action="{{ route('admin.menus.store') }}" data-type="{{ old('type', 'page') }}" id="addMenu">
        @csrf
        <h2><i class="fa-solid fa-plus"></i> إضافة رابط</h2>
        <div class="form-grid">
            <div class="field">
                <label>القائمة</label>
                <select name="location">@foreach (\App\Models\MenuItem::LOCATIONS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            </div>
            <div class="field"><label>النص الظاهر *</label><input type="text" name="label" value="{{ old('label') }}" required></div>
            <div class="field">
                <label>نوع الرابط</label>
                <select name="type" onchange="this.form.dataset.type=this.value">
                    <option value="page" @selected(old('type', 'page') === 'page')>صفحة مخصصة</option>
                    <option value="route" @selected(old('type') === 'route')>صفحة من الموقع</option>
                    <option value="url" @selected(old('type') === 'url')>رابط خارجي / مخصص</option>
                </select>
            </div>
            <div class="field t-page">
                <label>الصفحة</label>
                <select name="page_id"><option value="">— اختر —</option>@foreach ($pages as $p)<option value="{{ $p->id }}">{{ $p->title }}{{ $p->is_published ? '' : ' (مسودة)' }}</option>@endforeach</select>
                @if ($pages->isEmpty())<div class="hint">لا توجد صفحات — <a href="{{ route('admin.pages.create') }}">أنشئ صفحة</a>.</div>@endif
            </div>
            <div class="field t-route">
                <label>صفحة الموقع</label>
                <select name="route">@foreach (\App\Models\MenuItem::ROUTES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            </div>
            <div class="field t-url"><label>الرابط</label><input type="text" name="url" value="{{ old('url') }}" dir="ltr" placeholder="https://..."></div>
            <div class="field checks" style="align-self:end"><label><input type="checkbox" name="new_tab" value="1"> فتح في نافذة جديدة</label></div>
        </div>
        <div class="form-foot"><button class="btn btn-primary" type="submit">إضافة</button></div>
    </form>
    <style>
        #addMenu:not([data-type="page"]) .t-page, #addMenu:not([data-type="route"]) .t-route, #addMenu:not([data-type="url"]) .t-url { display: none; }
    </style>
@endsection
