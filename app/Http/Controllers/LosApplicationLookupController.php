<?php

namespace App\Http\Controllers;

use App\Support\LosApplicationLookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class LosApplicationLookupController extends Controller
{
    public function index(): Response
    {
        return $this->page();
    }

    public function search(Request $request, LosApplicationLookup $applications): Response
    {
        $data = $request->validate([
            'application_code' => ['nullable', 'string', 'required_without:identity_number', 'prohibits:identity_number', 'min:4', 'max:50'],
            'identity_number' => ['nullable', 'string', 'required_without:application_code', 'prohibits:application_code', 'max:20'],
        ], [
            'application_code.required_without' => 'Vui lòng nhập Mã hồ sơ hoặc CCCD/CMND.',
            'application_code.prohibits' => 'Chỉ nhập một trong hai ô Mã hồ sơ hoặc CCCD/CMND.',
            'application_code.min' => 'Mã hồ sơ phải có ít nhất 4 ký tự.',
            'identity_number.required_without' => 'Vui lòng nhập Mã hồ sơ hoặc CCCD/CMND.',
            'identity_number.prohibits' => 'Chỉ nhập một trong hai ô Mã hồ sơ hoặc CCCD/CMND.',
        ]);

        $applicationCode = trim((string) ($data['application_code'] ?? ''));
        $identityNumber = preg_replace('/\D+/', '', (string) ($data['identity_number'] ?? '')) ?: '';

        if ($identityNumber !== '' && strlen($identityNumber) < 6) {
            throw ValidationException::withMessages([
                'identity_number' => 'CCCD/CMND phải có ít nhất 6 chữ số.',
            ]);
        }

        $results = $applications->search($applicationCode, $identityNumber);

        return $this->page($applicationCode, $identityNumber, $results);
    }

    private function page(
        string $applicationCode = '',
        string $identityNumber = '',
        mixed $results = null,
    ): Response {
        return response()
            ->view('los.index', compact('applicationCode', 'identityNumber', 'results'))
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
