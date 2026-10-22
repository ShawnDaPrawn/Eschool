<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use App\Models\Settings;

/**
 * The query builder of App\Models\Settings: bulk inserts (insert,
 * updateOrInsert, upsert) write a platform key as a platform row and every
 * other key as the current school's, like Settings::create() does.
 *
 * @extends SchoolBuilder<Settings>
 */
class SettingsBuilder extends SchoolBuilder
{
    /**
     * The panel's settings pages update rows in place
     * (Settings::where('type', ...)->update()); a school's name, crest,
     * timezone or colours are then copied to its row in schools.
     */
    public function update(array $values)
    {
        $updated = parent::update($values);

        $currentSchool = app(CurrentSchool::class);
        if ($updated > 0 && $currentSchool->isSet() && ! $currentSchool->isMigrating() && $this->touchesBranding()) {
            SchoolBranding::sync($currentSchool->id());
        }

        return $updated;
    }

    private function touchesBranding(): bool
    {
        foreach ($this->getQuery()->wheres as $where) {
            $column = (string) ($where['column'] ?? '');
            if (($column === 'type' || $column === 'settings.type') && SchoolBranding::isBrandingKey($where['value'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    protected function withSchool(array $values): array
    {
        if ($values === []) {
            return $values;
        }
        $currentSchool = app(CurrentSchool::class);
        $fill = function (array $row) use ($currentSchool): array {
            if (PlatformSettings::isPlatformKey($row['type'] ?? null)) {
                $row['school_id'] = null;
            } elseif (! isset($row['school_id']) && ! $currentSchool->isMigrating()) {
                $row['school_id'] = $currentSchool->id();
            }

            return $row;
        };

        return array_is_list($values) && is_array(reset($values)) ? array_map($fill, $values) : $fill($values);
    }
}
