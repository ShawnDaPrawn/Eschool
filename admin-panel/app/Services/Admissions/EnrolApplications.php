<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class EnrolApplications
{
    /** Each placement sees the preceding new counts; any failure rolls back the batch. */
    public function enrol(User $actor, array $input): array
    {
        app(IntakeAuthority::class)->staff($actor, true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'category_id' => ['required', 'integer', 'min:1'],
            'applications' => ['required', 'array', 'min:1', 'max:100'], 'applications.*.id' => ['required', 'integer', 'distinct', 'min:1'], 'applications.*.revision' => ['required', 'integer', 'min:1']])->validate();

        return app(AdmissionOperations::class)->once($actor, 'applications.enrol', $d, function () use ($actor, $d): array {
            $ids = [];
            foreach ($d['applications'] as $item) {
                $row = app(EnrolApplication::class)->enrol($actor, (int) $item['id'], ['operation_id' => (string) Uuid::uuid5($d['operation_id'], (string) $item['id']), 'revision' => (int) $item['revision'], 'starts_on' => $d['starts_on'], 'category_id' => (int) $d['category_id']]);
                $ids[] = ['application_id' => (int) $row->id, 'learner_id' => (int) $row->learner_id];
            }

            return ['enrolled' => $ids];
        });
    }
}
