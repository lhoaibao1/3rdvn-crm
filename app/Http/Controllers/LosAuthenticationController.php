<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StalwartMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LosAuthenticationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('los.index');
        }

        return view('los.login');
    }

    public function store(Request $request, StalwartMailService $mailService): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'identifier.required' => 'Vui lòng nhập User/UID.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $identifier = trim($data['identifier']);
        $normalizedPhone = preg_replace('/\D+/', '', $identifier) ?: $identifier;
        $normalizedIdentifier = mb_strtolower($identifier);

        $user = User::query()
            ->where(function ($query) use ($identifier, $normalizedIdentifier, $normalizedPhone): void {
                $query
                    ->whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(username) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(name) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(uid) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(employee_code) = ?', [$normalizedIdentifier])
                    ->orWhere('identity_number', $identifier)
                    ->orWhere('phone', $identifier)
                    ->orWhere('phone', $normalizedPhone);
            })
            ->first();

        $isInactive = $user && in_array($user->employment_status, [
            'inactive',
            User::STATUS_DEACTIVE,
            'resigned',
            User::STATUS_DELETED,
        ], true);

        if (! $user || $isInactive || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withInput($request->only('identifier'))
                ->withErrors(['identifier' => 'Thông tin đăng nhập không đúng hoặc tài khoản đã ngừng hoạt động.']);
        }

        Auth::login($user, (bool) ($data['remember'] ?? false));
        $request->session()->regenerate();
        $mailService->scheduleCredentialSync($user, $data['password']);

        return redirect()->intended(route('los.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('los.login');
    }
}
