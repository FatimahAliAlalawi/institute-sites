<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;

/** عرض الشهادة (قابلة للطباعة PDF) والتحقق منها برمز */
class CertificateController extends Controller
{
    public function show(string $code)
    {
        $certificate = Certificate::with(['user', 'course.instructor'])->where('code', strtoupper($code))->firstOrFail();

        return view('site.certificate', compact('certificate'));
    }

    public function verify(Request $request)
    {
        $code = strtoupper(trim($request->string('code')->toString()));
        $certificate = $code !== '' ? Certificate::with(['user', 'course'])->where('code', $code)->first() : null;

        return view('site.verify', compact('code', 'certificate'));
    }
}
