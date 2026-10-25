<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Transfers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transfers\TransferRequest;
use App\Services\ResponseService;
use App\Services\Transfers\TransferAccept;
use App\Services\Transfers\TransferDownloads;
use App\Services\Transfers\TransferPackages;
use App\Services\Transfers\TransferQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TransferController extends Controller
{
    private function response(array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $data);
    }

    public function access(TransferRequest $r): JsonResponse
    {
        return $this->response(app(TransferQueries::class)->access($r->user()));
    }

    public function choices(TransferRequest $r, string $kind): JsonResponse
    {
        return $this->response(app(TransferQueries::class)->choices($r->user(), $kind, (string) $r->validated('search', ''), (int) $r->validated('page', 1), $r->has('learner_id') ? (int) $r->validated('learner_id') : null, $r->has('guardian_id') ? (int) $r->validated('guardian_id') : null, $r->validated('selected')));
    }

    public function index(TransferRequest $r, string $lane): JsonResponse
    {
        return $this->response(app(TransferQueries::class)->listing($r->user(), $lane, (int) $r->validated('page', 1)));
    }

    public function show(TransferRequest $r, string $lane, int $record): JsonResponse
    {
        return $this->response(app(TransferQueries::class)->show($r->user(), $lane, $record));
    }

    public function archive(TransferRequest $r, string $lane, int $record): Response
    {
        $bytes = app(TransferDownloads::class)->read($r->user(), $lane, $record);

        return response($bytes, 200, ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="school-transfer.zip"', 'Cache-Control' => 'private, no-store']);
    }

    public function prepare(TransferRequest $r): JsonResponse
    {
        $row = app(TransferPackages::class)->prepare($r->user(), $r->validated());

        return $this->response(['id' => (int) $row->id, 'revision' => (int) $row->revision, 'kind' => 'package']);
    }

    public function consent(TransferRequest $r, int $package): JsonResponse
    {
        $row = app(TransferPackages::class)->consent($r->user(), $package, $r->validated());

        return $this->response(['id' => (int) $row->id, 'revision' => (int) $row->revision, 'kind' => 'package']);
    }

    public function withdraw(TransferRequest $r, int $package): JsonResponse
    {
        $row = app(TransferPackages::class)->cancel($r->user(), $package, $r->validated(), true);

        return $this->response(['id' => (int) $row->id, 'revision' => (int) $row->revision, 'kind' => 'package']);
    }

    public function release(TransferRequest $r, int $package): JsonResponse
    {
        $row = app(TransferPackages::class)->release($r->user(), $package, $r->validated());

        return $this->response(['id' => (int) $row->id, 'revision' => (int) $row->revision, 'kind' => 'package']);
    }

    public function cancel(TransferRequest $r, int $package): JsonResponse
    {
        $row = app(TransferPackages::class)->cancel($r->user(), $package, $r->validated());

        return $this->response(['id' => (int) $row->id, 'revision' => (int) $row->revision, 'kind' => 'package']);
    }

    public function accept(TransferRequest $r, int $package): JsonResponse
    {
        $row = app(TransferAccept::class)->accept($r->user(), $package, $r->validated());

        return $this->response(['id' => (int) $row->id, 'revision' => 1, 'kind' => 'receipt']);
    }
}
