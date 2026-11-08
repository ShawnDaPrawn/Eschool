<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\Pools\PoolRequest;
use App\Services\People\Pools\PoolBookings;
use App\Services\People\Pools\PoolMembers;
use Illuminate\Http\JsonResponse;

/** The school's side of the alumni and volunteer pool (Action Plan 5.6). */
class PoolController extends Controller
{
    private function ok(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => trans('data_fetch_successfully'), 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function members(PoolRequest $r): JsonResponse
    {
        return $this->ok(app(PoolMembers::class)->listing($r->user(), (int) $r->validated('page', 1)));
    }

    public function invite(PoolRequest $r): JsonResponse
    {
        return $this->ok(app(PoolMembers::class)->invite($r->user(), $r->validated()));
    }

    public function member(PoolRequest $r, int $member): JsonResponse
    {
        return $this->ok(app(PoolMembers::class)->detail($r->user(), $member));
    }

    public function vet(PoolRequest $r, int $member): JsonResponse
    {
        return $this->ok(app(PoolMembers::class)->vet($r->user(), $member, $r->validated()));
    }

    public function bookings(PoolRequest $r): JsonResponse
    {
        return $this->ok(app(PoolBookings::class)->listing($r->user(), $r->has('activity_session_id') ? (int) $r->validated('activity_session_id') : null));
    }

    public function book(PoolRequest $r): JsonResponse
    {
        return $this->ok(app(PoolBookings::class)->book($r->user(), $r->validated()));
    }

    public function cancel(PoolRequest $r, int $booking): JsonResponse
    {
        return $this->ok(app(PoolBookings::class)->cancel($r->user(), $booking, $r->validated()));
    }
}
