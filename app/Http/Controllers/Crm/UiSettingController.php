<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Events\SettingsUpdated;
use App\Models\UiSetting;
use Illuminate\Http\Request;

class UiSettingController extends Controller
{
    public function edit()
    {
        return view('settings.ui', ['settings' => UiSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'logo_text' => ['nullable', 'string', 'max:100'],
            'favicon_url' => ['nullable', 'string', 'max:255'],
            'login_title' => ['nullable', 'string', 'max:255'],
            'login_subtitle' => ['nullable', 'string'],
            'primary_color' => ['required', 'string', 'max:20'],
            'background_color' => ['required', 'string', 'max:20'],
            'surface_color' => ['required', 'string', 'max:20'],
            'border_color' => ['required', 'string', 'max:20'],
            'sidebar_width_expanded' => ['required', 'integer', 'min:200', 'max:280'],
            'sidebar_width_collapsed' => ['required', 'integer', 'min:56', 'max:90'],
            'show_search' => ['nullable', 'boolean'],
            'show_notifications' => ['nullable', 'boolean'],
            'show_user_role' => ['nullable', 'boolean'],
            'show_employee_code' => ['nullable', 'boolean'],
        ]);

        foreach (['show_search', 'show_notifications', 'show_user_role', 'show_employee_code'] as $key) {
            $data[$key] = $request->boolean($key);
        }

        UiSetting::query()->updateOrCreate(['id' => 1], $data);
        SettingsUpdated::dispatch();
        return back()->with('success', 'Đã lưu giao diện.');
    }
}
