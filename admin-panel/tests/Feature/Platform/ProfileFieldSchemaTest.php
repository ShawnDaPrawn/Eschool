<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Services\People\Profiles\ProfileDefaults;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ProfileFieldSchemaTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    public function test_platform_defaults_include_locked_identity_and_encrypted_medical_fields(): void
    {
        foreach (['first_name', 'last_name', 'dob', 'gender', 'identity_number', 'medical_alerts', 'allergies'] as $key) {
            $field = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', $key)->firstOrFail();
            $this->assertTrue($field->platform_locked, $key);
        }
        foreach (['identity_number', 'medical_alerts', 'allergies'] as $key) {
            $field = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', $key)->firstOrFail();
            $this->assertTrue($field->sensitive);
            $this->assertTrue($field->encrypted);
            $this->assertNotContains('teacher', $field->visible_to);
        }
        $religion = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', 'religion')->firstOrFail();
        $this->assertTrue($religion->hidden);
        $this->assertFalse($religion->required);
        $this->assertFalse(ProfileFieldDefinition::query()->where('key', 'caste')->exists());
    }

    public function test_new_second_school_gets_its_own_defaults_and_scopes_never_mix_them(): void
    {
        $a = ProfileFieldDefinition::query()->count();
        $this->assertGreaterThan(0, $a);
        app(CurrentSchool::class)->run(2, function () use ($a) {
            $this->assertSame($a, ProfileFieldDefinition::query()->count());
            $this->assertSame([2], DB::table('profile_field_definitions')->whereIn('id', ProfileFieldDefinition::query()->select('id'))->pluck('school_id')->unique()->all());
        });
    }

    public function test_reseeding_is_idempotent_and_does_not_erase_school_configuration(): void
    {
        $category = ProfileFieldCategory::query()->where('profile_type', 'learner')->where('key', 'identity')->firstOrFail();
        $category->update(['label' => 'Our identity category', 'sort_order' => 75]);
        $count = ProfileFieldDefinition::query()->count();
        ProfileDefaults::seed(1);
        ProfileDefaults::seed(1);
        $this->assertSame($count, ProfileFieldDefinition::query()->count());
        $this->assertSame('Our identity category', $category->fresh()->label);
        $this->assertSame(75, $category->fresh()->sort_order);
    }
}
