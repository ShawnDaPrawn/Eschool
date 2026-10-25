<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Pastoral\SupportAssignment;
use App\Models\Pastoral\SupportDisclosure;
use App\Models\Pastoral\SupportOperation as Receipt;
use App\Models\Pastoral\SupportPlan;
use App\Models\Pastoral\SupportReview;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportOperation
{
    public function run(User $actor, string $uuid, string $action, array $input, callable $authorize, callable $effect): Model
    {
        abort_unless(Str::isUuid($uuid) && (string) config('app.key') !== '', 422, 'Choose a fresh request identifier.');
        $context = app(CurrentSchool::class);
        $snapshot = $context->snapshot();
        $school = $context->id();
        try {
            return DB::transaction(function () use ($actor, $uuid, $action, $input, $authorize, $effect, $context, $school) {
                $this->context($context, $school);
                School::whereKey($school)->lockForUpdate()->firstOrFail();
                $authorize();
                $hash = hash_hmac('sha256', json_encode([$actor->id, $action, $input], JSON_THROW_ON_ERROR), (string) config('app.key'));
                $prior = Receipt::where('operation_id', $uuid)->first();
                if ($prior !== null) {
                    abort_unless((int) $prior->user_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This identifier belongs to different support changes.');
                    $class = match ($prior->result_kind) {
                        'assignment' => SupportAssignment::class,'plan' => SupportPlan::class,'review' => SupportReview::class,'disclosure' => SupportDisclosure::class
                    };
                    $result = $class::findOrFail($prior->result_id);
                    $this->context($context, $school);
                    $authorize();

                    return $result;
                }
                $result = $effect();
                $this->context($context, $school);
                $authorize();
                $kind = match (true) {
                    $result instanceof SupportAssignment => 'assignment',$result instanceof SupportPlan => 'plan',$result instanceof SupportReview => 'review',$result instanceof SupportDisclosure => 'disclosure'
                };
                Receipt::create(['user_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash, 'result_kind' => $kind, 'result_id' => $result->id]);
                $this->context($context, $school);

                return $result;
            }, 3);
        } finally {
            $context->restore($snapshot);
        }
    }

    private function context(CurrentSchool $context, int $school): void
    {
        abort_unless($context->id() === $school && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
    }
}
