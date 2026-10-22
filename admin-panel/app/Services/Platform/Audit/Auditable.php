<?php

declare(strict_types=1);

namespace App\Services\Platform\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Records every create, update, delete, restore and force delete of the
 * model in audit_log, with the changed attributes before and after.
 *
 * Never stored: the model's $hidden attributes (passwords, remember tokens,
 * timestamps), the names in config('platform.audit.never_store') (API and
 * device tokens) and anything a model removes in auditRedact(). An update
 * that changes only such attributes writes no row.
 *
 * Writes that bypass Eloquent (query builder updates, inserts) are not
 * seen; use Audit::record() where those matter.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            Audit::record('created', $model, [], $model->auditValues($model->getAttributes()));
        });

        static::updated(function (Model $model) {
            $after = $model->auditValues($model->getChanges());
            if ($after === []) {
                return;
            }
            $before = array_intersect_key($model->auditValues($model->getRawOriginal()), $after);
            Audit::record('updated', $model, $before, $after);
        });

        static::deleted(function (Model $model) {
            $softDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);
            $action = $softDeletes && $model->isForceDeleting() ? 'force_deleted' : 'deleted';
            Audit::record($action, $model, $model->auditValues($model->getRawOriginal()), []);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            // registerModelEvent rather than restored(), which only models
            // with SoftDeletes have.
            static::registerModelEvent('restored', function (Model $model) {
                Audit::record('restored', $model);
            });
        }
    }

    /**
     * The attributes that may be written to the audit log.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditValues(array $attributes): array
    {
        $never = array_merge(
            (array) config('platform.audit.never_store', []),
            $this->getHidden(),
            [$this->getCreatedAtColumn(), $this->getUpdatedAtColumn()],
        );

        return $this->auditRedact(array_diff_key($attributes, array_flip(array_filter($never))));
    }

    /**
     * Override to blank out values that must not be kept even though the
     * attribute is visible (for example a secret held in a generic column).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function auditRedact(array $values): array
    {
        return $values;
    }
}
