<?php

declare(strict_types=1);

namespace App\Services\People\Profiles;

use Illuminate\Support\Facades\DB;

/** Idempotent initial metadata, also usable while migrations disable scopes. */
class ProfileDefaults
{
    public static function seed(int $schoolId): void
    {
        foreach (config('profile_fields.defaults', []) as $profile => $categories) {
            $categoryOrder = 0;
            foreach ($categories as $key => $category) {
                $categoryOrder++;
                DB::table('profile_field_categories')->insertOrIgnore([
                    'school_id' => $schoolId, 'profile_type' => $profile, 'key' => $key,
                    'label' => $category['label'], 'sort_order' => $categoryOrder * 10,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $categoryId = DB::table('profile_field_categories')->where('school_id', $schoolId)->where('profile_type', $profile)->where('key', $key)->value('id');
                $fieldOrder = 0;
                foreach ($category['fields'] as $fieldKey => $definition) {
                    $fieldOrder++;
                    $visible = $profile === 'learner' ? ['owner', 'guardian', 'school_admin'] : ['owner', 'school_admin'];
                    $editable = $profile === 'learner' ? ['guardian', 'school_admin'] : $visible;
                    if ($profile === 'learner' && in_array($fieldKey, ['gender', 'religion'], true)) {
                        $editable[] = 'owner';
                    }
                    if ($profile === 'learner' && in_array($fieldKey, ['first_name', 'last_name'], true)) {
                        $visible = array_merge($visible, ['teacher', 'facilitator', 'counsellor']);
                    }
                    DB::table('profile_field_definitions')->insertOrIgnore([
                        'school_id' => $schoolId, 'category_id' => $categoryId, 'profile_type' => $profile,
                        'key' => $fieldKey, 'label' => $definition['label'], 'type' => $definition['type'],
                        'source' => $definition['source'] ?? 'custom',
                        'validation' => json_encode($definition['validation'] ?? (object) []),
                        'options' => json_encode($definition['options'] ?? []),
                        'visible_to' => json_encode($definition['visible_to'] ?? $visible),
                        'editable_by' => json_encode($definition['editable_by'] ?? $editable),
                        'required' => $definition['required'] ?? false, 'platform_locked' => $definition['locked'] ?? false,
                        'sensitive' => $definition['sensitive'] ?? false, 'encrypted' => ! isset($definition['source']),
                        'hidden' => $definition['hidden'] ?? false, 'sort_order' => $fieldOrder * 10,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
