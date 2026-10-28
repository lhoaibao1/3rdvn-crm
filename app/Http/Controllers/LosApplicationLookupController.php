<?php

namespace App\Http\Controllers;

use App\Support\LosApplicationLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LosApplicationLookupController extends Controller
{
    public function index(Request $request): Response
    {
        $applications = app(LosApplicationLookup::class);
        $projects = $applications->projects();

        return $this->page(
            keyword: '',
            project: 'all',
            system: 'all',
            status: 'all',
            dateRange: 'all',
            projects: $projects,
            results: null
        );
    }

    public function search(Request $request): Response|JsonResponse
    {
        $applications = app(LosApplicationLookup::class);
        $keyword = trim((string) ($request->input('keyword') ?? ($request->input('q') ?? ($request->input('application_code') ?? ''))));
        $project = trim((string) $request->input('project', 'all'));
        $system = trim((string) $request->input('system', 'all'));
        $status = trim((string) $request->input('status', 'all'));
        $dateRange = trim((string) $request->input('date_range', 'all'));
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));

        $projects = $applications->projects();

        if ($keyword === '' && $project === 'all' && $system === 'all' && $status === 'all' && $dateRange === 'all' && $dateFrom === '' && $dateTo === '') {
            $results = null;
        } else {
            $results = $applications->search([
                'keyword' => $keyword,
                'project' => $project,
                'system' => $system,
                'status' => $status,
                'date_range' => $dateRange,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'count' => $results ? $results->count() : 0,
                'results' => $results ? $results->values() : [],
                'projects' => $projects,
            ]);
        }

        return $this->page(
            keyword: $keyword,
            project: $project,
            system: $system,
            status: $status,
            dateRange: $dateRange,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            projects: $projects,
            results: $results
        );
    }

    public function detail(string $id): JsonResponse
    {
        $applications = app(LosApplicationLookup::class);
        $data = $applications->find($id);

        if (! $data) {
            return response()->json([
                'success' => false,
                'message' => 'Hồ sơ không tồn tại hoặc đã bị xóa.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function page(
        string $keyword = '',
        string $project = 'all',
        string $system = 'all',
        string $status = 'all',
        string $dateRange = 'all',
        string $dateFrom = '',
        string $dateTo = '',
        array $projects = [],
        mixed $results = null,
    ): Response {
        $applicationCode = $keyword;
        $identityNumber = '';

        return response()
            ->view('los.index', compact(
                'keyword',
                'applicationCode',
                'identityNumber',
                'project',
                'system',
                'status',
                'dateRange',
                'dateFrom',
                'dateTo',
                'projects',
                'results'
            ))
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
