<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Registration;
use App\Models\Setting;
use App\Support\RegistrationImporter;
use App\Support\SheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** نقل المتقدمين من نموذج Google المعتمد (ربط مؤقت يُحدَّث تلقائيًا) أو رفع ملف Excel */
class RegistrationImportController extends Controller
{
    public function show(Request $request)
    {
        $pending = $request->query('file') ? Cache::get('import_file_'.$request->query('file')) : null;
        $headers = $pending['rows'][0] ?? (json_decode((string) setting('import_headers', '[]'), true) ?: []);

        return view('admin.registrations.import', [
            'headers' => $headers,
            'map' => RegistrationImporter::map(),
            'pending' => $pending,
            'fileToken' => $pending ? $request->query('file') : null,
            'last' => json_decode((string) setting('import_last', '{}'), true) ?: [],
            'programs' => Program::orderByRaw('start_date is null')->orderByDesc('start_date')->get(['id', 'title', 'start_date']),
        ]);
    }

    /** حفظ رابط نموذج Google وقراءة عناوين الأعمدة */
    public function link(Request $request)
    {
        $data = $request->validate(['import_url' => 'nullable|url|max:500', 'import_auto' => 'nullable|boolean']);
        $url = $data['import_url'] ?? null;

        Setting::put(['import_url' => $url, 'import_auto' => $request->boolean('import_auto') ? '1' : '0']);
        if (! $url) {
            return back()->with('status', 'تم إلغاء ربط نموذج Google.');
        }

        try {
            $rows = SheetReader::fromGoogle($url);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_url' => $e->getMessage()]);
        }
        Setting::put(['import_headers' => json_encode($rows[0] ?? [], JSON_UNESCAPED_UNICODE)]);

        return back()->with('status', 'تم ربط النموذج: '.max(0, count($rows) - 1).' رد. اربط الأعمدة ثم اضغط «سحب الآن».');
    }

    /** ربط الأعمدة والقيم الافتراضية */
    public function map(Request $request)
    {
        $data = $request->validate([
            'map' => 'array',
            'map.*' => 'nullable|string|max:300',
            'import_program' => 'nullable|exists:programs,id',
            'import_status' => ['required', Rule::in(Registration::OPEN_STATUSES)],
        ]);
        Setting::put([
            'import_map' => json_encode(array_filter($data['map'] ?? []), JSON_UNESCAPED_UNICODE),
            'import_program' => $data['import_program'] ?? null,
            'import_status' => $data['import_status'],
        ]);

        if ($request->filled('file_token') && ($pending = Cache::pull('import_file_'.$request->input('file_token')))) {
            return $this->runImport($pending['rows'], 'ملف Excel', $pending['name']);
        }

        return back()->with('status', 'تم حفظ ربط الأعمدة.');
    }

    public function sync()
    {
        if (! RegistrationImporter::ready()) {
            return back()->withErrors(['map' => 'اربط عمود الاسم وعمود الجوال أولًا.']);
        }
        $r = RegistrationImporter::syncGoogle();
        if (isset($r['error'])) {
            return back()->withErrors(['import_url' => $r['error']]);
        }

        return redirect()->route('admin.registrations.import')->with('status', $this->summary($r));
    }

    /** رفع ملف Excel أو CSV */
    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file|max:10240']);
        try {
            $rows = SheetReader::fromUpload($request->file('file'));
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        if (count($rows) < 2) {
            return back()->withErrors(['file' => 'الملف فاضي أو فيه صف العناوين فقط.']);
        }

        $name = $request->file('file')->getClientOriginalName();
        $map = RegistrationImporter::map();
        $headers = array_map('trim', $rows[0]);
        $mapped = filled($map['name'] ?? null) && filled($map['phone'] ?? null)
            && in_array($map['name'], $headers, true) && in_array($map['phone'], $headers, true);

        if ($mapped && ! $request->boolean('review')) {
            return $this->runImport($rows, 'ملف Excel', $name);
        }

        // أعمدة جديدة: نحفظ الملف مؤقتًا ونطلب ربط الأعمدة
        $token = Str::random(24);
        Cache::put('import_file_'.$token, ['rows' => $rows, 'name' => $name], now()->addMinutes(30));

        return redirect()->route('admin.registrations.import', ['file' => $token])
            ->with('status', 'قرأنا '.(count($rows) - 1).' صف من «'.$name.'». اربط الأعمدة ثم اضغط «حفظ واستيراد الملف».');
    }

    protected function runImport(array $rows, string $source, string $name)
    {
        try {
            $r = RegistrationImporter::import($rows, $source);
        } catch (\Throwable $e) {
            return redirect()->route('admin.registrations.import')->withErrors(['file' => $e->getMessage()]);
        }
        Setting::put(['import_last' => json_encode($r + ['at' => now()->format('Y-m-d H:i'), 'from' => $name], JSON_UNESCAPED_UNICODE)]);

        return redirect()->route('admin.registrations.import')->with('status', $this->summary($r));
    }

    protected function summary(array $r): string
    {
        return 'تم: '.$r['added'].' متقدم جديد، '.$r['updated'].' تحديث، '.$r['skipped'].' صف بدون اسم أو جوال صحيح (من '.$r['total'].').';
    }
}
