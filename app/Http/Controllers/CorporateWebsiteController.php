<?php

namespace App\Http\Controllers;

use App\Models\JobVacancy;
use App\Models\UiSetting;
use Illuminate\View\View;

class CorporateWebsiteController extends Controller
{
    public function index(): View
    {
        return view('site.home', [
            'settings' => UiSetting::current(),
            'vacancies' => JobVacancy::query()
                ->with('salesProject')
                ->publiclyAvailable()
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->limit(4)
                ->get(),
        ]);
    }
}
