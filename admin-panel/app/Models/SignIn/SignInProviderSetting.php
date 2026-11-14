<?php

declare(strict_types=1);

namespace App\Models\SignIn;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One school's choice for one sign-in provider (Action Plan 5.9): off until
 * the school switches it on, with the email domains or Microsoft tenants it
 * allows and who may use it. See App\Services\Platform\SignIn\ProviderSettings.
 *
 * @property int $id
 * @property string $provider
 * @property bool $enabled
 * @property list<string>|null $allowed_domains
 * @property list<string>|null $allowed_tenants
 * @property bool $require_hosted_domain
 * @property list<string>|null $staff_roles
 * @property bool $learners_allowed
 * @property list<string>|null $learner_stages
 */
class SignInProviderSetting extends Model
{
    use BelongsToSchool;

    protected $table = 'sign_in_provider_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean', 'allowed_domains' => 'array', 'allowed_tenants' => 'array', 'require_hosted_domain' => 'boolean',
            'staff_roles' => 'array', 'learners_allowed' => 'boolean', 'learner_stages' => 'array',
        ];
    }
}
