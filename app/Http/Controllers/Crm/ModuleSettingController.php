<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmModule;
use Illuminate\Http\Request;

class ModuleSettingController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        return view('settings.modules', [
            'modules' => CrmModule::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);

        $data = $request->validate([
            'modules' => ['array'],
            'modules.*.id' => ['required', 'integer', 'exists:crm_modules,id'],
            'modules.*.label' => ['required', 'string', 'max:80'],
            'modules.*.icon' => ['nullable', 'string', 'max:80'],
            'modules.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'modules.*.is_active' => ['nullable', 'boolean'],
            'modules.*.required_roles' => ['nullable', 'string', 'max:255'],
            'modules.*.required_permissions' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['modules'] ?? [] as $moduleData) {
            $module = CrmModule::query()->findOrFail($moduleData['id']);
            $module->update([
                'label' => $moduleData['label'],
                'icon' => $moduleData['icon'] ?? null,
                'sort_order' => $moduleData['sort_order'],
                'is_active' => (bool) ($moduleData['is_active'] ?? false),
                'required_roles' => $this->csv($moduleData['required_roles'] ?? ''),
                'required_permissions' => $this->csv($moduleData['required_permissions'] ?? ''),
            ]);
        }

        return back()->with('success', 'Đã lưu modules.');
    }

    private function csv(string $value): array
    {
        return collect(explode(',', $value))->map(fn ($item) => trim($item))->filter()->values()->all();
    }
}
