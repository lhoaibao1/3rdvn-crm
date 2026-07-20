@extends('layouts.app', ['title' => 'UI Settings'])

@section('content')
<form class="form-card" method="POST" action="{{ route('settings.ui.update') }}">
    @csrf @method('PUT')
    <div class="form-grid">
        <label class="field"><span>App name</span><input name="app_name" value="{{ old('app_name', $settings->app_name) }}" required></label>
        <label class="field"><span>Logo text</span><input name="logo_text" value="{{ old('logo_text', $settings->logo_text) }}"></label>
        <label class="field full"><span>Favicon URL</span><input name="favicon_url" value="{{ old('favicon_url', $settings->favicon_url) }}"></label>
        <label class="field"><span>Login title</span><input name="login_title" value="{{ old('login_title', $settings->login_title) }}"></label>
        <label class="field"><span>Login subtitle</span><input name="login_subtitle" value="{{ old('login_subtitle', $settings->login_subtitle) }}"></label>
        <label class="field"><span>Primary color</span><input name="primary_color" value="{{ old('primary_color', $settings->primary_color) }}" required></label>
        <label class="field"><span>Background</span><input name="background_color" value="{{ old('background_color', $settings->background_color) }}" required></label>
        <label class="field"><span>Surface</span><input name="surface_color" value="{{ old('surface_color', $settings->surface_color) }}" required></label>
        <label class="field"><span>Border</span><input name="border_color" value="{{ old('border_color', $settings->border_color) }}" required></label>
        <label class="field"><span>Sidebar width</span><input type="number" name="sidebar_width_expanded" value="{{ old('sidebar_width_expanded', $settings->sidebar_width_expanded) }}" required></label>
        <label class="field"><span>Sidebar collapsed</span><input type="number" name="sidebar_width_collapsed" value="{{ old('sidebar_width_collapsed', $settings->sidebar_width_collapsed) }}" required></label>
        <label class="checkbox"><input type="checkbox" name="show_search" value="1" @checked($settings->show_search)><span>Show search</span></label>
        <label class="checkbox"><input type="checkbox" name="show_user_role" value="1" @checked($settings->show_user_role)><span>Show role</span></label>
        <label class="checkbox"><input type="checkbox" name="show_employee_code" value="1" @checked($settings->show_employee_code)><span>Show employee code</span></label>
        <label class="checkbox"><input type="checkbox" name="show_notifications" value="1" @checked($settings->show_notifications)><span>Show notifications</span></label>
    </div>
    <div class="actions"><a class="secondary-btn" href="{{ route('settings.modules.edit') }}">Modules</a><button class="primary-btn">Lưu settings</button></div>
</form>
@endsection
