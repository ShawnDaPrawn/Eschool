<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Gallery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gallery\GalleryRequest;
use App\Services\Gallery\GalleryPhotos;
use App\Services\Gallery\GalleryQueries;
use App\Services\Gallery\GallerySettings;
use App\Services\Gallery\GalleryTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GalleryController extends Controller
{
    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Photo request completed.', 'data' => $data, 'code' => 200]);
    }

    private function reference(Model $row): JsonResponse
    {
        return $this->result(['id' => (int) $row->id, 'revision' => (int) $row->revision]);
    }

    public function configuration(GalleryRequest $r): JsonResponse
    {
        return $this->result(app(GalleryQueries::class)->configuration($r->user()));
    }

    public function family(GalleryRequest $r): JsonResponse
    {
        return $this->result(app(GalleryQueries::class)->family($r->user(), (int) $r->route('learner'), $r->boolean('pending'),
            $r->filled('before') ? (int) $r->validated('before') : null, $r->filled('session_year_id') ? (int) $r->validated('session_year_id') : null));
    }

    public function group(GalleryRequest $r): JsonResponse
    {
        return $this->result(app(GalleryQueries::class)->group($r->user(), (int) $r->route('group'), $r->filled('before') ? (int) $r->validated('before') : null));
    }

    public function content(GalleryRequest $r): Response
    {
        $bytes = app(GalleryPhotos::class)->bytes($r->user(), (int) $r->route('photo'), $r->boolean('thumbnail'));

        return response($bytes, 200, ['Content-Type' => 'image/jpeg', 'Content-Length' => (string) strlen($bytes), 'Content-Disposition' => 'inline; filename="photo.jpg"']);
    }

    public function upload(GalleryRequest $r): JsonResponse
    {
        return $this->reference(app(GalleryPhotos::class)->upload($r->user(), $r->safe()->except(['photo']), $r->file('photo')));
    }

    public function withdraw(GalleryRequest $r): JsonResponse
    {
        return $this->reference(app(GalleryPhotos::class)->withdraw($r->user(), (int) $r->route('photo'), $r->validated()));
    }

    public function propose(GalleryRequest $r): JsonResponse
    {
        return $this->reference(app(GalleryTags::class)->propose($r->user(), (int) $r->route('photo'), $r->validated()));
    }

    public function decide(GalleryRequest $r): JsonResponse
    {
        return $this->reference(app(GalleryTags::class)->decide($r->user(), (int) $r->route('tag'), $r->validated()));
    }

    public function remove(GalleryRequest $r): JsonResponse
    {
        return $this->reference(app(GalleryTags::class)->remove($r->user(), (int) $r->route('tag'), $r->validated()));
    }

    public function settings(GalleryRequest $r): JsonResponse
    {
        $settings = app(GallerySettings::class)->save($r->user(), $r->validated());

        return $this->result(['guardian_required' => $settings->guardian_required, 'revision' => (int) $settings->revision]);
    }
}
