<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Cached context carries metadata only; never query or decrypt person values. */
class ProfileFieldsContributor implements ContextContributor
{
    public function __construct(private readonly MembershipService $memberships) {}

    public function contribute(ContextScope $scope): array
    {
        $types = $this->memberships->active($scope->user)->where('school_id', $scope->school->id)->pluck('role')
            ->map(fn ($role) => ProfilePolicy::typeForRole($role))->unique()->values()->all();
        $fields = DB::table('profile_field_definitions as fields')
            ->join('profile_field_categories as categories', 'categories.id', '=', 'fields.category_id')
            ->where('fields.school_id', $scope->school->id)->where('categories.school_id', $scope->school->id)
            ->whereIn('fields.profile_type', $types)->whereNull('fields.deleted_at')->whereNull('categories.deleted_at')
            ->where('fields.hidden', false)->whereJsonContains('fields.visible_to', 'owner')
            ->when(app(CurrentSchool::class)->isImpersonating(), fn ($query) => $query->where('fields.sensitive', false))
            ->orderBy('categories.sort_order')->orderBy('fields.sort_order')->orderBy('fields.id')
            ->get(['fields.profile_type', 'fields.key', 'fields.label', 'fields.type', 'fields.required', 'fields.platform_locked',
                'categories.key as category_key', 'categories.label as category_label']);

        return ['profile_fields' => $fields->map(fn ($field) => [
            'profile_type' => $field->profile_type, 'key' => $field->key, 'label' => __($field->label),
            'type' => $field->type, 'required' => (bool) $field->required, 'locked' => (bool) $field->platform_locked,
            'category_key' => $field->category_key, 'category_label' => __($field->category_label),
        ])->all()];
    }
}
