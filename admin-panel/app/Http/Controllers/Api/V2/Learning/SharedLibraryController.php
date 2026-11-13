<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\SharedLibraryRequest;
use App\Models\Library\LibraryContribution;
use App\Services\Learning\Library\SharedLibrary;
use App\Services\Learning\Library\SharedLibraryBrowse;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/**
 * API v2 for teachers' apps: the shared library, teacher to teacher (Action
 * Plan 5.4). Browse, read, rate and copy shared questions; share your own and
 * withdraw them. Leaders' approvals and the school's sharing switch are in
 * the panel. The services check the person, the school and the item again.
 */
class SharedLibraryController extends Controller
{
    public function __construct(private readonly SharedLibrary $shared, private readonly SharedLibraryBrowse $browse) {}

    public function items(SharedLibraryRequest $r): JsonResponse
    {
        return $this->ok($this->browse->catalogue($r->user(), $r->validated()));
    }

    public function item(SharedLibraryRequest $r, int $item): JsonResponse
    {
        return $this->ok($this->browse->item($r->user(), $item));
    }

    public function rate(SharedLibraryRequest $r, int $item): JsonResponse
    {
        return $this->ok($this->browse->rate($r->user(), $item, $r->validated()));
    }

    public function copy(SharedLibraryRequest $r, int $item): JsonResponse
    {
        return $this->ok($this->browse->copy($r->user(), $item, $r->validated()));
    }

    public function contributions(SharedLibraryRequest $r): JsonResponse
    {
        return $this->ok(['contributions' => $this->shared->mine($r->user()), 'sharing' => $this->shared->settings()]);
    }

    public function options(SharedLibraryRequest $r, int $item): JsonResponse
    {
        return $this->ok($this->shared->options($r->user(), $item));
    }

    public function contribute(SharedLibraryRequest $r): JsonResponse
    {
        return $this->ok($this->contribution($this->shared->contribute($r->user(), $r->validated())));
    }

    public function withdraw(SharedLibraryRequest $r, int $contribution): JsonResponse
    {
        return $this->ok($this->contribution($this->shared->withdraw($r->user(), $contribution)));
    }

    /** @return array{id: int, state: string, practice_item_id: int} */
    private function contribution(LibraryContribution $contribution): array
    {
        return ['id' => (int) $contribution->id, 'state' => $contribution->state, 'practice_item_id' => (int) $contribution->practice_item_id];
    }

    private function ok(array $data): JsonResponse
    {
        return ResponseService::successResponse(data: $data)->header('Cache-Control', 'private, no-store');
    }
}
