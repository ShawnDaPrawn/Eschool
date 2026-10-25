<?php

declare(strict_types=1);

namespace App\Http\Requests\Live;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Live\LiveAuthority;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(LiveAuthority::class)->actor($this->user());

        return true;
    }

    public function validationData(): array
    {
        return $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        abort_if($this->query->count() > 0, 422, 'Send live session fields in the request body.');
        abort_if(array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    public function rules(): array
    {
        return match ($this->route()->getName()) {
            'api.v2.live.session' => [],
            'api.v2.live.authorize' => ['socket_id' => ['required', 'string', 'regex:/^[0-9]{1,20}[.][0-9]{1,20}$/D'], 'channel_name' => ['required', 'string', 'max:200']],
            default => ['lease_id' => ['required', 'uuid']],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the live session request.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
