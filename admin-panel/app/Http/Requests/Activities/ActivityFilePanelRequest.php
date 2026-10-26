<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Http\Controllers\Panel\Activities\ActivityFilePanelController;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ActivityFilePanelRequest extends ActivityFileRequest
{
    public function validationData(): array
    {
        $data = parent::validationData();
        unset($data['_token']);

        return $data;
    }

    protected function failedValidation(Validator $validator): void
    {
        // Files and private names are never flashed into a shared session.
        throw new HttpResponseException(app(ActivityFilePanelController::class)->page($this, $validator->errors()->all(), 422));
    }
}
