<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Activities\ActivityFiles;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class ActivityFileRequest extends FormRequest
{
    public function action(): string
    {
        return Str::afterLast((string) $this->route()->getName(), '.');
    }

    public function authorize(): bool
    {
        app(ActivityFiles::class)->authorize($this->user(), (int) $this->route('group'), (int) $this->route('material'),
            in_array($this->action(), ['upload', 'remove'], true), $this->action() === 'upload');

        return true;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : array_replace($this->getInputSource()->all(), $this->allFiles());
    }

    protected function prepareForValidation(): void
    {
        $this->authorize();
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send private activity fields in the request body.');
        abort_if(array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    public function rules(): array
    {
        return match ($this->action()) {
            'listing', 'read' => [],
            'upload' => ['operation_id' => ['required', 'uuid'], 'material_revision' => ['required', 'integer', 'min:1'],
                'document' => ['required', 'file', 'mimes:pdf,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120']],
            'remove' => ['operation_id' => ['required', 'uuid'], 'material_revision' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:1']],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the activity attachment.', ['errors' => $validator->errors()->toArray()]));
    }
}
