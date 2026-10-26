<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\ActivityFilePanelRequest;
use App\Services\Activities\ActivityFiles;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ActivityFilePanelController extends Controller
{
    public function index(ActivityFilePanelRequest $request, int $group, int $material): Response
    {
        return $this->page($request);
    }

    public function page(ActivityFilePanelRequest $request, array $fileErrors = [], int $status = 200): Response
    {
        $group = (int) $request->route('group');
        $material = (int) $request->route('material');
        $files = app(ActivityFiles::class)->listing($request->user(), $group, $material);

        return response()->view('activities.files', compact('group', 'material', 'files', 'fileErrors'), $status);
    }

    public function write(ActivityFilePanelRequest $request, int $group, int $material, ?string $file = null): BaseResponse
    {
        $service = app(ActivityFiles::class);
        try {
            if ($request->action() === 'upload') {
                $service->upload($request->user(), $group, $material, $request->safe()->except('document'), $request->file('document'));
            } else {
                $service->remove($request->user(), $group, $material, (string) $file, $request->validated());
            }
        } catch (ValidationException $error) {
            return $this->page($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 503], true)) {
                throw $error;
            }

            return $this->page($request, [$error->getMessage()], $error->getStatusCode());
        }

        return redirect()->route('activity-files.listing', compact('group', 'material'));
    }

    public function read(ActivityFilePanelRequest $request, int $group, int $material, string $file): Response
    {
        $result = app(ActivityFiles::class)->read($request->user(), $group, $material, $file);
        $extension = $result['mime'] === 'application/pdf' ? 'pdf' : 'jpg';

        return response($result['bytes'], 200, ['Content-Type' => $result['mime'],
            'Content-Disposition' => 'attachment; filename="'.$file.'.'.$extension.'"',
            'Content-Security-Policy' => "sandbox; default-src 'none'"]);
    }
}
