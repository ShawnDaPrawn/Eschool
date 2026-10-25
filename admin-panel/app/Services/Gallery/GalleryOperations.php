<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Gallery\GalleryOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GalleryOperations
{
    public function once(User $actor, array $input, callable $authority, callable $write): array
    {
        app(GalleryAuthority::class)->actor($actor, true);
        $authority();
        abort_unless(Str::isUuid($input['operation_id'] ?? null), 422);
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode($input, JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $input, $authority, $write, $school, $hash): array {
            School::whereKey($school)->lockForUpdate()->firstOrFail();
            DB::table('users')->where('id', $actor->id)->lockForUpdate()->first();
            DB::table('school_memberships')->where('school_id', $school)->where('user_id', $actor->id)->lockForUpdate()->get(['id']);
            DB::table('school_modules')->where('school_id', $school)->whereIn('module', ['gallery', 'groups'])->orderBy('id')->lockForUpdate()->get(['id']);
            app(GalleryAuthority::class)->actor($actor, true);
            $authority();
            $prior = GalleryOperation::where('actor_id', $actor->id)->where('operation_id', $input['operation_id'])->first();
            if ($prior !== null) {
                abort_unless(hash_equals($prior->request_hash, $hash), 409, 'This photo operation was already used with different details.');

                return $prior->receipt;
            }
            $result = $write();
            app(GalleryAuthority::class)->actor($actor, true);
            $authority();
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            GalleryOperation::create(['actor_id' => $actor->id, 'operation_id' => $input['operation_id'], 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }
}
