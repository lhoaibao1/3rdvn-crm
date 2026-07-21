<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateApplicationRequest;
use App\Models\CandidateApplication;
use App\Models\JobVacancy;
use App\Models\UiSetting;
use App\Support\Candidates\CandidateWorkflow;
use App\Support\VietnamAddressCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CandidateApplicationController extends Controller
{
    public function index(): View
    {
        return view('recruitment.jobs', [
            'settings' => UiSetting::current(),
            'vacancies' => JobVacancy::query()->with('salesProject')
                ->publiclyAvailable()
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->get(),
        ]);
    }

    public function show(JobVacancy $jobVacancy): View
    {
        abort_unless($jobVacancy->isOpenForApplications(), 404);
        $jobVacancy->load('salesProject');

        return view('recruitment.apply', [
            'settings' => UiSetting::current(),
            'provinces' => VietnamAddressCatalog::provinceOptions(),
            'vacancy' => $jobVacancy,
        ]);
    }

    public function store(CandidateApplicationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $vacancy = JobVacancy::query()->with('autoAssignee.roles')->find($data['job_vacancy_id']);

        if (! $vacancy?->isOpenForApplications()) {
            throw ValidationException::withMessages([
                'job_vacancy_id' => 'Vị trí này đã ngưng nhận hồ sơ. Vui lòng chọn vị trí khác.',
            ]);
        }

        $location = VietnamAddressCatalog::wardInfo($data['ward_code']);

        if (! $location
            || (string) $location['province_code'] !== (string) $data['province_code']
            || (string) $location['district_code'] !== (string) $data['district_code']) {
            throw ValidationException::withMessages([
                'ward_code' => 'Địa chỉ hành chính không hợp lệ. Vui lòng chọn lại.',
            ]);
        }

        $file = $request->file('cv');
        $path = $file->storeAs(
            'recruitment/cv/'.now()->format('Y/m'),
            Str::uuid().'.'.strtolower($file->getClientOriginalExtension()),
            'local'
        );

        try {
            $candidate = DB::transaction(function () use ($data, $location, $path, $request, $vacancy): CandidateApplication {
                $candidate = CandidateApplication::create([
                    ...collect($data)->except([
                        'website', 'cv', 'consent', 'province_code', 'district_code',
                        'ward_code', 'job_vacancy_id',
                    ])->all(),
                    'job_vacancy_id' => $vacancy->id,
                    'applied_position' => $vacancy->title,
                    'province_code' => (string) $location['province_code'],
                    'province_name' => $location['province_name'],
                    'district_code' => (string) $location['district_code'],
                    'district_name' => $location['district_name'],
                    'ward_code' => (string) $location['ward_code'],
                    'ward_name' => $location['ward_name'],
                    'cv_path' => $path,
                    'source' => 'ungtuyen.3rdvn.io.vn',
                    'status' => CandidateApplication::STATUS_NEW,
                    'consented_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                ]);

                return CandidateWorkflow::autoAssign($candidate, $vacancy->autoAssignee);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('recruitment.success')->with('application_code', $candidate->application_code);
    }

    public function success(): View
    {
        return view('recruitment.success', [
            'settings' => UiSetting::current(),
            'applicationCode' => session('application_code'),
        ]);
    }

    public function districts(string $province): JsonResponse
    {
        return response()->json(VietnamAddressCatalog::districtOptions($province));
    }

    public function wards(string $district): JsonResponse
    {
        return response()->json(VietnamAddressCatalog::wardOptions($district));
    }

    public function download(Request $request, CandidateApplication $candidate): StreamedResponse
    {
        abort_unless(CandidateWorkflow::canView($candidate, $request->user()), 403);
        abort_unless(Storage::disk('local')->exists($candidate->cv_path), 404);

        $extension = pathinfo($candidate->cv_path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = Str::slug($candidate->full_name ?: 'ung-vien').'-'.$candidate->application_code.'.'.$extension;

        return Storage::disk('local')->download($candidate->cv_path, $filename, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
