<?php

use App\Http\Controllers\Auth\PasswordOtpResetController;
use App\Http\Controllers\CandidateApplicationController;
use App\Http\Controllers\CorporateWebsiteController;
use App\Http\Controllers\Crm\LatestNotificationController;
use App\Http\Controllers\Crm\TableColumnPreferenceController;
use App\Http\Controllers\MailSsoController;
use App\Models\User;
use App\Support\DataCenter\LeadReferralImportTemplate;
use App\Support\Permissions\DataCenterAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$publicWebsiteDomains = [
    '3rdvn.io.vn',
    'www.3rdvn.io.vn',
    '3rd-vn.io.vn',
    'www.3rd-vn.io.vn',
];

foreach ($publicWebsiteDomains as $publicWebsiteDomain) {
    $route = Route::domain($publicWebsiteDomain)
        ->get('/', [CorporateWebsiteController::class, 'index']);

    if ($publicWebsiteDomain === '3rdvn.io.vn') {
        $route->name('website.home');
    }
}
Route::domain('ungtuyen.3rdvn.io.vn')->group(function (): void {
    Route::get('/', [CandidateApplicationController::class, 'index'])->name('recruitment.apply');
    Route::get('/vi-tri/{jobVacancy:slug}', [CandidateApplicationController::class, 'show'])->name('recruitment.job');
    Route::post('/ung-tuyen', [CandidateApplicationController::class, 'store'])->middleware('throttle:5,1')->name('recruitment.store');
    Route::get('/hoan-tat', [CandidateApplicationController::class, 'success'])->name('recruitment.success');
    Route::get('/dia-chi/quan-huyen/{province}', [CandidateApplicationController::class, 'districts'])->middleware('throttle:120,1')->name('recruitment.districts');
    Route::get('/dia-chi/phuong-xa/{district}', [CandidateApplicationController::class, 'wards'])->middleware('throttle:120,1')->name('recruitment.wards');
});

Route::get('/tuyen-dung/cv/{candidate}', [CandidateApplicationController::class, 'download'])
    ->middleware('auth')
    ->name('recruitment.cv.download');

Route::get('/lead-referral/import-template', function (Request $request) {
    abort_unless(
        DataCenterAccess::canDistribute($request->user()),
        403,
    );

    $path = LeadReferralImportTemplate::create();

    return response()->download(
        $path,
        'Mau-import-Lead-Referral.xlsx',
        ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    )->deleteFileAfterSend(true);
})->middleware('auth')->name('lead-referral.import-template');

Route::redirect('/admin', '/');
Route::redirect('/admin/login', '/authen/login');
Route::redirect('/login', '/authen/login')->name('login');
Route::redirect('/dashboard', '/');
Route::redirect('/profiles', '/sale-profiles');
Route::redirect('/settings/ui', '/ui-settings');

Route::get('/mail/sso', MailSsoController::class)
    ->middleware('auth')
    ->name('mail.sso');

Route::get('/crm/notifications/latest', LatestNotificationController::class)
    ->middleware(['auth', 'throttle:60,1'])
    ->name('crm.notifications.latest');

Route::get('/authen/forgot-username', function () {
    return view('auth.forgot-username-crm');
})->name('crm.username.request');

Route::post('/authen/forgot-username', function (Request $request) {
    $data = $request->validate([
        'identifier' => ['required', 'string', 'max:255'],
    ], [
        'identifier.required' => 'Vui lòng nhập CCCD, SĐT hoặc email.',
    ]);

    $identifier = trim($data['identifier']);
    $normalizedPhone = preg_replace('/\D+/', '', $identifier) ?: $identifier;

    $user = User::query()
        ->where('email', $identifier)
        ->orWhere('identity_number', $identifier)
        ->orWhere('phone', $identifier)
        ->orWhere('phone', $normalizedPhone)
        ->first();

    return back()
        ->withInput()
        ->with('status', $user
            ? 'User/UID của bạn: '.($user->uid ?: $user->employee_code ?: $user->email)
            : 'Không tìm thấy tài khoản phù hợp.');
})->name('crm.username.lookup');

Route::get('/authen/forgot-password', [PasswordOtpResetController::class, 'create'])
    ->name('crm.password.request');

Route::post('/authen/forgot-password', [PasswordOtpResetController::class, 'sendOtp'])
    ->name('crm.password.lookup');

Route::post('/authen/reset-password', [PasswordOtpResetController::class, 'reset'])
    ->name('crm.password.otp.reset');

Route::post('/crm/table-column-preferences', [TableColumnPreferenceController::class, 'store'])
    ->middleware('auth')
    ->name('crm.table-columns.store');
