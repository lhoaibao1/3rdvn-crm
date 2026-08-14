<?php

use App\Http\Controllers\Auth\PasswordOtpResetController;
use App\Http\Controllers\CandidateApplicationController;
use App\Http\Controllers\CorporateWebsiteController;
use App\Http\Controllers\Crm\LatestNotificationController;
use App\Http\Controllers\Crm\TableColumnPreferenceController;
use App\Http\Controllers\FeolPublicLandingController;
use App\Http\Controllers\Integration\EssAuthenticationController;
use App\Http\Controllers\Integration\CompletedCustomerDirectoryController;
use App\Http\Controllers\Integration\FeolApplicationSyncController;
use App\Http\Controllers\Integration\FeolPendingApplicationsController;
use App\Http\Controllers\Integration\VpnUserDirectoryController;
use App\Http\Controllers\LosApplicationLookupController;
use App\Http\Controllers\LosAuthenticationController;
use App\Http\Controllers\MailSsoController;
use App\Models\User;
use App\Support\DataCenter\LeadReferralImportTemplate;
use App\Support\Permissions\DataCenterAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('fe-deeplink/b1/{token}/trang-thai', [FeolPublicLandingController::class, 'status'])
    ->middleware('throttle:120,1')
    ->name('feol.landing.status');

Route::prefix('fe-deeplink/b1')->middleware('throttle:30,1')->group(function (): void {
    Route::get('/{token}', [FeolPublicLandingController::class, 'show'])->name('feol.landing.show');
    Route::post('/{token}', [FeolPublicLandingController::class, 'store'])->name('feol.landing.store');
    Route::get('/{token}/hoan-tat', [FeolPublicLandingController::class, 'success'])->name('feol.landing.success');
});

Route::prefix('application/fe-deeplink/dang-ky')->middleware('throttle:10,1')->group(function (): void {
    Route::get('/{salesCode}', [FeolPublicLandingController::class, 'showForSalesCode'])
        ->where('salesCode', '[0-9]{5}')
        ->name('feol.registration.show');
    Route::post('/{salesCode}', [FeolPublicLandingController::class, 'storeForSalesCode'])
        ->where('salesCode', '[0-9]{5}')
        ->name('feol.registration.store');
});

Route::domain('los.3rdvn.io.vn')->group(function (): void {
    Route::get('/login', [LosAuthenticationController::class, 'create'])->name('los.login');
    Route::post('/login', [LosAuthenticationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('los.login.store');

    Route::middleware('auth')->group(function (): void {
        Route::get('/', [LosApplicationLookupController::class, 'index'])->name('los.index');
        Route::post('/tra-cuu', [LosApplicationLookupController::class, 'search'])
            ->middleware('throttle:20,1')
            ->name('los.search');
        Route::post('/logout', [LosAuthenticationController::class, 'destroy'])->name('los.logout');
    });
});

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

Route::get('/admin', function () {
    return redirect(app()->environment('uat') ? '/admin/workflow-configurations' : '/');
});
Route::redirect('/admin/login', '/authen/login');
Route::redirect('/workflow-configurations', '/admin/workflow-configurations');
Route::get('/workflow-configurations/{path}', function (string $path) {
    return redirect('/admin/workflow-configurations/'.$path);
})->where('path', '.*');
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

Route::get('/api/integration/v1/users', VpnUserDirectoryController::class)
    ->middleware('throttle:60,1')
    ->name('api.integration.vpn.users');

Route::post('/api/integration/v1/authenticate', EssAuthenticationController::class)
    ->middleware('throttle:10,1')
    ->name('api.integration.ess.authenticate');

Route::get('/api/integration/v1/completed-customers', CompletedCustomerDirectoryController::class)
    ->middleware('throttle:30,1')
    ->name('api.integration.completed-customers');

Route::get('/api/integration/v1/feol/pending', FeolPendingApplicationsController::class)
    ->middleware('throttle:120,1')
    ->name('api.integration.feol.pending');

Route::post('/api/integration/v1/feol/applications/{application}/sync', FeolApplicationSyncController::class)
    ->middleware('throttle:240,1')
    ->name('api.integration.feol.sync');
