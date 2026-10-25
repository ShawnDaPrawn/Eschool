<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Gallery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gallery\GalleryPanelRequest;
use App\Services\Gallery\GalleryPanelScope;
use App\Services\Gallery\GalleryPhotos;
use App\Services\Gallery\GalleryQueries;
use App\Services\Gallery\GallerySettings;
use App\Services\Gallery\GalleryTags;
use App\Services\Panel\SessionYearContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GalleryPanelController extends Controller
{
    public function index(GalleryPanelRequest $r): Response
    {
        return $this->workbench($r);
    }

    private function workbench(GalleryPanelRequest $r, array $messages = [], int $status = 200): Response
    {
        $configuration = app(GalleryQueries::class)->configuration($r->user());
        $groupId = (int) ($r->attributes->get('gallery_group_id') ?? ($configuration['groups'][0]['id'] ?? 0));
        $group = collect($configuration['groups'])->firstWhere('id', $groupId);
        abort_if($groupId > 0 && $group === null, 404);
        $listing = $group === null ? ['items' => [], 'next_before' => null] : app(GalleryQueries::class)->group($r->user(), $groupId, $r->filled('before') ? (int) $r->input('before') : null);
        $canWrite = $configuration['can_write'] && ! app(SessionYearContext::class)->isReadOnly();
        $draft = ['caption' => '', 'captured_on' => $configuration['today'], 'session_year_id' => $group['session_year_id'] ?? $configuration['session_year_id']];
        if (! $r->isMethodSafe() && $r->route('operation') === 'upload') {
            $draft['caption'] = is_string($r->input('caption')) ? mb_substr($r->input('caption'), 0, 2000) : '';
            if (is_string($r->input('captured_on'))) {
                $draft['captured_on'] = mb_substr($r->input('captured_on'), 0, 10);
            }
        }
        $operationId = Str::isUuid($r->input('operation_id', '')) ? $r->input('operation_id') : (string) Str::uuid();

        return response()->view('gallery.workbench', compact('configuration', 'groupId', 'group', 'listing', 'canWrite', 'draft', 'messages', 'operationId'), $status);
    }

    public function image(GalleryPanelRequest $r): Response
    {
        $bytes = app(GalleryPhotos::class)->bytes($r->user(), (int) $r->route('photo'), $r->boolean('thumbnail'));

        return response($bytes, 200, ['Content-Type' => 'image/jpeg', 'Content-Length' => (string) strlen($bytes), 'Content-Disposition' => 'inline; filename="photo.jpg"']);
    }

    public function write(GalleryPanelRequest $r): RedirectResponse|Response
    {
        try {
            $op = (string) $r->route('operation');
            $data = $r->safe()->except(['photo', 'expected_account_id', 'expected_school_id']);
            match ($op) {
                'upload' => app(GalleryPhotos::class)->upload($r->user(), $data, $r->file('photo')),
                'settings' => app(GallerySettings::class)->save($r->user(), $data),
                'propose' => app(GalleryTags::class)->propose($r->user(), (int) $r->route('photo'), $data),
                'withdraw' => app(GalleryPhotos::class)->withdraw($r->user(), (int) $r->route('photo'), $data),
                default => abort(404),
            };

            return redirect()->route('gallery-panel.index', array_filter(['group_id' => $r->attributes->get('gallery_group_id')]));
        } catch (ValidationException $error) {
            return $this->failure($r, $error->errors(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 423], true)) {
                throw $error;
            }

            return $this->failure($r, [$error->getMessage()], $error->getStatusCode());
        }
    }

    public function failure(GalleryPanelRequest $r, array $messages, int $status): Response
    {
        app(GalleryPanelScope::class)->authorize($r);

        return $this->workbench($r, collect($messages)->flatten()->all(), $status);
    }
}
