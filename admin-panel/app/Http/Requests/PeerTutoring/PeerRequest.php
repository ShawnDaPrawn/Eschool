<?php

declare(strict_types=1);

namespace App\Http\Requests\PeerTutoring;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\PeerTutoring\PeerAccess;
use App\Services\PeerTutoring\PeerSessions;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PortalSession;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Sanctum\PersonalAccessToken;

class PeerRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(PeerAccess::class)->actor($this->user());
        $token = $this->user()->currentAccessToken();
        $profile = $token instanceof PersonalAccessToken ? $token->name
            : (PortalSession::isPortal($this) ? $this->session()->get('portal_app') : null);
        $expected = match ($profile) {
            AppTokens::STUDENT => 'student', AppTokens::PARENT => 'parent',
            AppTokens::TEACHER, AppTokens::FACILITATOR => 'staff', default => null,
        };
        abort_if($expected !== null && $expected !== $this->persona(), 403);
        if (! in_array($this->route()->getActionMethod(), ['configuration', 'index', 'show'], true)) {
            abort_unless($this->persona() === 'staff', 403);
        }

        return true;
    }

    public function persona(): string
    {
        $value = $this->validationData()['persona'] ?? null;

        return is_string($value) ? $value : '';
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        abort_unless(is_string($this->validationData()['persona'] ?? null), 422, 'Choose a valid session audience.');
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send private session fields in the request body.');
        abort_if($this->allFiles() !== [] || array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    public function rules(): array
    {
        $persona = ['persona' => ['required', 'in:staff,parent,student']];

        return $persona + match ($this->route()->getActionMethod()) {
            'configuration', 'show', 'learners', 'matches' => [],
            'index' => ['page' => ['sometimes', 'integer', 'between:1,100000']],
            'store' => PeerSessions::rules('create'),
            'complete' => PeerSessions::rules('complete'),
            'cancel' => PeerSessions::rules('cancel'),
            'reverse' => PeerSessions::rules('reverse'),
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the peer-session fields.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
