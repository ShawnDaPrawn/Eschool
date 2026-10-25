<?php

declare(strict_types=1);

namespace App\Http\Requests\Gallery;

use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Services\Gallery\GalleryAuthority;
use App\Services\Gallery\GalleryTags;
use App\Services\Platform\Files\SafeImages;
use Illuminate\Foundation\Http\FormRequest;

class GalleryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethodSafe()) {
            foreach (['pending', 'thumbnail'] as $key) {
                if (in_array($this->query($key), ['true', 'false'], true)) {
                    $this->merge([$key => $this->query($key) === 'true']);
                }
            }
        }
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Submit photo changes in the request body.');
    }

    public function authorize(): bool
    {
        $a = app(GalleryAuthority::class);
        $actor = $this->user();
        $a->actor($actor, ! $this->isMethodSafe());
        $method = $this->route()->getActionMethod();
        if ($method === 'settings') {
            $a->manage($actor, true);
        } elseif ($this->route('tag') !== null) {
            $tag = PhotoTag::select(['id', 'learner_id'])->findOrFail((int) $this->route('tag'));
            if ($method === 'decide') {
                app(GalleryTags::class)->decisionAuthority($actor, (int) $tag->learner_id);
            } else {
                $a->family($actor, (int) $tag->learner_id, true, removal: true);
            }
        } elseif ($this->route('learner') !== null) {
            $a->family($actor, (int) $this->route('learner'));
        } elseif ($this->route('group') !== null) {
            $a->group($actor, (int) $this->route('group'));
        } elseif ($method === 'withdraw') {
            $photo = GalleryPhoto::select(['id', 'scope', 'activity_group_id', 'owner_learner_id'])->findOrFail((int) $this->route('photo'));
            if ($photo->scope === 'group') {
                $a->group($actor, (int) $photo->activity_group_id, true);
            } else {
                $a->family($actor, (int) $photo->owner_learner_id, true, removal: true);
            }
        } elseif ($this->route('photo') !== null) {
            $a->photo($actor, (int) $this->route('photo'));
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['pending' => ['sometimes', 'boolean'], 'before' => ['sometimes', 'integer', 'min:1'], 'session_year_id' => ['sometimes', 'integer', 'min:1'], 'thumbnail' => ['sometimes', 'boolean']];
        }

        return self::writeRules($this->route()->getActionMethod());
    }

    public static function writeRules(string $operation): array
    {
        return ['operation_id' => ['required', 'uuid']] + match ($operation) {
            'upload' => ['scope' => ['required', 'in:family,group'], 'learner_id' => ['required_if:scope,family', 'nullable', 'integer', 'min:1'], 'activity_group_id' => ['required_if:scope,group', 'nullable', 'integer', 'min:1'],
                'session_year_id' => ['required', 'integer', 'min:1'], 'captured_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'caption' => ['nullable', 'string', 'max:2000'], 'photo' => SafeImages::rules()['photo']],
            'propose' => ['learner_id' => ['required_without:handle', 'nullable', 'integer', 'min:1'], 'handle' => ['required_without:learner_id', 'nullable', 'string', 'regex:/^@?[a-z][a-z0-9._]{1,18}[a-z0-9]$/D']],
            'decide' => ['revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:approve,reject']],
            'settings' => ['revision' => ['required', 'integer', 'min:0'], 'guardian_required' => ['required', 'boolean']],
            default => ['revision' => ['required', 'integer', 'min:1']],
        };
    }
}
