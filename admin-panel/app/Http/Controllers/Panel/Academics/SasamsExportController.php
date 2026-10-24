<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\SasamsExportRequest;
use App\Services\Academics\SasamsExport;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;
use ZipArchive;

class SasamsExportController extends Controller
{
    public function index(SasamsExportRequest $request): View
    {
        return view('academics.sasams-export');
    }

    public function download(SasamsExportRequest $request): Response
    {
        try {
            $profile = json_decode($request->file('layout')->get(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['layout' => __('Choose a valid JSON export layout.')]);
        }
        if (! is_array($profile)) {
            throw ValidationException::withMessages(['layout' => __('Choose a JSON object describing the target layout.')]);
        }
        $result = app(SasamsExport::class)->generate($request->user(), $profile, $request->file('template')?->getRealPath());
        $path = tempnam(sys_get_temp_dir(), 'sasams-package-');
        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                abort(503, __('The export could not be packaged. Try again.'));
            }
            try {
                $zip->addFromString($result['filename'], $result['content']);
                $zip->addFromString('manifest.json', json_encode($result['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            } finally {
                $zip->close();
            }

            return response(file_get_contents($path), 200, ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="sasams-export.zip"',
                'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        } finally {
            if ($path !== false && is_file($path)) {
                unlink($path);
            }
        }
    }
}
