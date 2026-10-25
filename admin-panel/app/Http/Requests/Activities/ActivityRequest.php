<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Models\Activities\ActivitySession;
use App\Services\Activities\ActivityGroups;
use App\Services\Activities\CommunityAuthority;
use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    public function action(): string
    {
        $name = (string) $this->route()->getName();

        return substr($name, strrpos($name, '.') + 1);
    }

    public function authorize(): bool
    {
        $a = app(CommunityAuthority::class);
        $actor = $this->user();
        $action = $this->action();
        $id = $this->route('group');
        if (in_array($action, ['create', 'save', 'membership-save', 'staff-save'], true)) {
            $a->manage($actor);
        } elseif ($action === 'attendance-save') {
            $a->staff($actor, true);
            $session = ActivitySession::findOrFail((int) $this->route('session'));
            $a->groupStaff($actor, (int) $session->activity_group_id, true, $session->on_date->toDateString());
        } elseif (in_array($action, ['roster', 'session-save', 'material-save'], true)) {
            $a->groupStaff($actor, (int) $id, $action !== 'roster');
        } elseif ($id !== null) {
            $a->read($actor, (int) $id);
        } else {
            $a->actor($actor);
        }

        return true;
    }

    public function rules(): array
    {
        return ActivityGroups::rules($this->action());
    }
}
