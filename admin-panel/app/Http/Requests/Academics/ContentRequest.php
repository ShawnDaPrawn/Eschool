<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Content\ContentFlow;
use App\Services\Academics\Content\ContentSelection;
use App\Services\Academics\Content\ContentSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ContentRequest extends FormRequest
{
    private function action(): string
    {
        return substr((string) $this->route()->getName(), strrpos((string) $this->route()->getName(), '.') + 1);
    }

    public function authorize(): bool
    {
        $action = $this->action();
        $policy = app(ContentPolicy::class);
        $actor = $this->user();
        if (in_array($action, ['library', 'reader', 'read', 'saved-access'], true)) {
            $policy->learnerGroups($actor, (int) $this->route('learner'));
            if ($this->route('version') !== null) {
                $policy->reader($actor, (int) $this->route('learner'), (int) $this->route('version'));
            }
        } else {
            app(AcademicAuthority::class)->staff($actor);
            if ($action === 'settings') {
                app(AcademicAuthority::class)->manage($actor);
            }
            if ($this->route('item') !== null) {
                $policy->staff($actor, (int) $this->route('item'), in_array($action, ['upload', 'archive'], true));
            }
            if ($this->route('version') !== null) {
                $policy->version($actor, (int) $this->route('version'), $action !== 'version');
            }
        }

        return true;
    }

    public function rules(): array
    {
        $revision = ContentFlow::revisionRules();
        $selection = ['selection' => ['required', 'array']];
        foreach (ContentSelection::rules() as $key => $rules) {
            $selection['selection.'.$key] = $rules;
        }

        return match ($this->action()) {
            'create','upload' => ['selection' => ['required', 'string', 'json', 'max:32768'], 'source' => ['required', 'file', 'max:25600'], 'operation_id' => ['required', 'uuid']] + ($this->action() === 'upload' ? $revision : []),
            'save' => $selection + $revision + ['markdown' => ['required', 'string', 'max:2097152']],
            'saved-access' => ['version_ids' => ['required', 'array', 'min:1', 'max:20'], 'version_ids.*' => ['required', 'integer', 'min:1', 'distinct']],
            'retry' => $revision,
            'publish' => $revision + ['fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'], 'reviewed' => ['required', 'accepted'], 'reason' => ['sometimes', 'nullable', 'string', 'min:3', 'max:2000']],
            'archive' => ['item_revision' => ['required', 'integer', 'min:1'], 'operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'min:3', 'max:2000']],
            'settings' => ContentSettings::rules(),
            default => ['session_year_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,10000']],
        };
    }

    public function selectionInput(): array
    {
        $data = $this->validated();
        $selection = $data['selection'];
        if (is_string($selection)) {
            try {
                $selection = json_decode($selection, true, 32, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw ValidationException::withMessages(['selection' => __('Choose the content subject, groups and topics.')]);
            }
        }
        abort_unless(is_array($selection) && ! array_is_list($selection), 422, __('Choose the content subject, groups and topics.'));

        return array_diff_key($data, ['selection' => true, 'source' => true]) + $selection;
    }
}
