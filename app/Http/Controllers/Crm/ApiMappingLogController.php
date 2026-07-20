<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\ApiMappingLog;

class ApiMappingLogController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasAnyRole(['Admin', 'API Manager']), 403);

        return view('modules.api-mapping-logs.index', [
            'logs' => ApiMappingLog::query()->latest()->paginate(30),
        ]);
    }
}
