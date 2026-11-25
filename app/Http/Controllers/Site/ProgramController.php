<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->string('category')->toString();
        $category = array_key_exists($category, Program::CATEGORIES) ? $category : null;
        $q = trim($request->string('q')->toString());

        $programs = Program::active()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%$q%")->orWhere('summary', 'like', "%$q%")))
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        $counts = Program::active()->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category');
        $planCount = Program::active()->inPlan()->count();

        return view('site.programs.index', compact('programs', 'category', 'q', 'counts', 'planCount'));
    }

    /** الخطة التدريبية للأفراد: البرامج المعتمدة من الإدارة لثلاثة أشهر، مرتبة حسب الشهر */
    public function plan(Request $request)
    {
        $start = Carbon::now()->startOfMonth();
        if (preg_match('/^\d{4}-\d{2}$/', (string) $request->query('from'))) {
            $start = Carbon::createFromFormat('Y-m-d', $request->query('from').'-01')->startOfMonth();
        }
        $end = $start->copy()->addMonths(3);

        $notCancelled = fn ($q) => $q->whereNull('plan_exec')->orWhere('plan_exec', '!=', 'cancelled');
        $programs = Program::active()->inPlan()->where($notCancelled)->withCount('seatRegistrations')
            ->whereNotNull('start_date')
            ->where('start_date', '>=', $start->toDateString())
            ->where('start_date', '<', $end->toDateString())
            ->orderBy('start_date')->orderBy('sort')
            ->get();

        $months = [];
        for ($m = $start->copy(); $m->lt($end); $m->addMonth()) {
            $key = $m->format('Y-m');
            $months[$key] = [
                'label' => $m->translatedFormat('F Y'),
                'programs' => $programs->filter(fn ($p) => $p->start_date->format('Y-m') === $key)->values(),
            ];
        }

        $upcoming = Program::active()->inPlan()->where($notCancelled)->withCount('seatRegistrations')->whereNull('start_date')->ordered()->get();

        return view('site.programs.plan', [
            'months' => $months,
            'upcoming' => $upcoming,
            'start' => $start,
            'end' => $end->copy()->subDay(),
            'prev' => $start->copy()->subMonths(3)->format('Y-m'),
            'next' => $end->format('Y-m'),
            'total' => $programs->count(),
        ]);
    }

    /** رابط قصير للإعلانات ورموز QR: /go/{id} */
    public function short(int $id)
    {
        $program = Program::active()->findOrFail($id);

        return redirect()->route('programs.show', $program);
    }

    /** رابط واتساب قصير للإعلانات ورموز QR: /go/{id}/wa */
    public function whatsapp(int $id)
    {
        $program = Program::active()->findOrFail($id);
        abort_unless($program->whatsapp_url, 404);

        return redirect()->away($program->whatsapp_url);
    }

    /** ملف «أضف للتقويم» لأيام الدورة (للمتدرب والمدرب) */
    public function ics(Program $program)
    {
        abort_unless($program->is_active && ($program->session_dates || $program->start_date), 404);

        return \App\Support\Ics::response(\App\Support\Ics::build([$program], $program->title), 'course-'.$program->id.'.ics');
    }

    public function show(Program $program)
    {
        abort_unless($program->is_active, 404);

        $related = Program::active()->where('category', $program->category)->whereKeyNot($program->id)->ordered()->take(3)->get();

        return view('site.programs.show', compact('program', 'related'));
    }
}
