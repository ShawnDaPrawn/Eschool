<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\People\Profiles\ProfileForms;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ProfileValidationTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function definition(array $extra = []): ProfileFieldDefinition
    {
        $category = ProfileFieldCategory::query()->where('profile_type', 'learner')->firstOrFail();

        return app(ProfileDefinitions::class)->field($this->superAdmin(), $extra + [
            'category_id' => $category->id, 'key' => 'custom_route', 'label' => 'Route', 'type' => 'text',
            'visible_to' => ['owner', 'school_admin'], 'editable_by' => ['school_admin'],
        ]);
    }

    private function save(array $values): void
    {
        app(ProfileForms::class)->save($this->superAdmin(), User::findOrFail($this->schoolA->ids['asha_user']), 'learner', $values);
    }

    public static function values(): array
    {
        return [
            'text' => ['text', [], 'Route 7', 17],
            'decimal remains text' => ['number', [], '00012.250', '1e5'],
            'date' => ['date', ['validation' => ['past' => true]], '2020-02-29', '2035-02-29'],
            'choice' => ['dropdown', ['options' => ['Bus', 'Walk']], 'Bus', 'Taxi'],
            'multiple choices' => ['checkbox', ['options' => ['Bus', 'Walk']], ['Bus', 'Walk'], ['Bus', 'Bus']],
            'false is a value' => ['boolean', [], false, 'perhaps'],
        ];
    }

    #[DataProvider('values')]
    public function test_types_validate_before_any_writes_and_store_only_ciphertext(string $type, array $extra, mixed $valid, mixed $invalid): void
    {
        $field = $this->definition(['type' => $type] + $extra);
        $this->save(['custom_route' => $valid]);
        $stored = ProfileFieldValue::query()->where('definition_id', $field->id)->firstOrFail();
        $this->assertSame($valid, json_decode(Crypt::decryptString($stored->value), true));
        try {
            $this->save(['first_name' => 'Do not persist', 'custom_route' => $invalid]);
            $this->fail('Invalid values must be refused.');
        } catch (ValidationException) {
            $this->assertSame($stored->value, $stored->fresh()->value);
            $this->assertNotSame('Do not persist', User::findOrFail($this->schoolA->ids['asha_user'])->first_name);
        }
        $this->save(['custom_route' => null]);
        $this->assertNull($stored->fresh()->value);
    }

    public function test_required_fields_do_not_block_unrelated_partial_changes_but_cannot_be_cleared(): void
    {
        $this->definition(['required' => true]);
        $this->save(['first_name' => 'Updated']);
        $this->assertSame('Updated', User::findOrFail($this->schoolA->ids['asha_user'])->first_name);
        $this->expectException(ValidationException::class);
        $this->save(['custom_route' => null]);
    }

    public function test_fixed_sources_cannot_weaken_canonical_column_validation(): void
    {
        $field = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', 'first_name')->firstOrFail();
        $field->update(['validation' => ['max' => 10000]]);
        $this->expectException(ValidationException::class);
        $this->save(['first_name' => str_repeat('A', 129)]);
    }

    public function test_sensitive_values_are_absent_during_support_impersonation(): void
    {
        $this->save(['medical_alerts' => 'Private health information']);
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $form = app(ProfileForms::class)->schema($this->superAdmin(), User::findOrFail($this->schoolA->ids['asha_user']), 'learner');
        $keys = collect($form['categories'])->pluck('fields')->flatten(1)->pluck('key')->all();
        $this->assertNotContains('medical_alerts', $keys);
        $this->assertStringNotContainsString('Private health information', json_encode($form));
        $this->expectException(ValidationException::class);
        $this->save(['medical_alerts' => 'Changed']);
    }

    public function test_definition_input_cannot_forge_a_canonical_source_or_platform_lock(): void
    {
        $field = $this->definition(['source' => 'user.dob', 'platform_locked' => true, 'school_id' => 2, 'editable_by' => []]);
        $this->assertSame('custom', $field->source);
        $this->assertFalse($field->refresh()->platform_locked);
        $this->assertSame(1, $field->school_id);
        $this->assertSame([], $field->editable_by);
        $this->assertTrue($field->encrypted);
    }

    public static function lockedEdits(): array
    {
        return [
            'retype' => [['type' => 'number']],
            'remove privacy' => [['sensitive' => false]],
            'remove encryption' => [['encrypted' => false]],
            'hide locked field' => [['hidden' => true]],
            'expand ID audience' => [['visible_to' => ['owner', 'school_admin', 'teacher']]],
        ];
    }

    #[DataProvider('lockedEdits')]
    public function test_locked_identity_fields_keep_type_and_privacy(array $changes): void
    {
        $field = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', 'identity_number')->firstOrFail();
        $this->expectException(ValidationException::class);
        app(ProfileDefinitions::class)->field($this->superAdmin(), $changes + $field->toArray(), $field->id);
    }

    public function test_a_field_in_another_school_cannot_be_changed_or_moved_into_this_school(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, fn () => ProfileFieldDefinition::query()->firstOrFail());
        $this->actingAs($this->superAdmin())->putJson('/api/v2/profile-fields/definitions/'.$foreign->id,
            ['category_id' => $foreign->category_id, 'key' => $foreign->key, 'label' => 'Changed', 'type' => $foreign->type,
                'visible_to' => ['owner'], 'editable_by' => []])->assertNotFound();
    }

    public function test_inconsistent_validation_bounds_are_refused_when_configuring_a_field(): void
    {
        $this->expectException(ValidationException::class);
        $this->definition(['validation' => ['min' => 10, 'max' => 2]]);
    }

    public function test_populated_fields_cannot_be_retyped_and_locked_fields_cannot_be_removed(): void
    {
        $field = $this->definition();
        $this->save(['custom_route' => 'Bus']);
        try {
            app(ProfileDefinitions::class)->field($this->superAdmin(), ['type' => 'number'] + $field->toArray(), $field->id);
            $this->fail('A populated field must retain its type.');
        } catch (ValidationException) {
            $this->assertSame('text', $field->fresh()->type);
        }
        $locked = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', 'first_name')->firstOrFail();
        $this->actingAs($this->superAdmin())->deleteJson('/api/v2/profile-fields/definitions/'.$locked->id)->assertUnprocessable();
        $this->assertNotNull($locked->fresh());
    }

    public static function choiceTypes(): array
    {
        return [['dropdown'], ['radio'], ['checkbox']];
    }

    #[DataProvider('choiceTypes')]
    public function test_populated_choices_cannot_be_removed_but_additions_and_reordering_remain_available(string $type): void
    {
        $field = $this->definition(['type' => $type, 'options' => ['Bus', 'Walk']]);
        $this->save(['custom_route' => $type === 'checkbox' ? ['Bus'] : 'Bus']);
        try {
            app(ProfileDefinitions::class)->field($this->superAdmin(), ['options' => ['Walk']] + $field->toArray(), $field->id);
            $this->fail('Removing choices must preserve existing encrypted selections.');
        } catch (ValidationException) {
            $this->assertSame(['Bus', 'Walk'], $field->fresh()->options);
        }
        $field = app(ProfileDefinitions::class)->field($this->superAdmin(), ['options' => ['Walk', 'Bus', 'Train']] + $field->toArray(), $field->id);
        $this->assertSame(['Walk', 'Bus', 'Train'], $field->options);
        $this->save(['custom_route' => null]);
        $field = app(ProfileDefinitions::class)->field($this->superAdmin(), ['options' => ['Walk', 'Train']] + $field->toArray(), $field->id);
        $this->assertSame(['Walk', 'Train'], $field->options);
    }

    public function test_canonical_gender_selections_are_also_protected_when_choices_change(): void
    {
        User::findOrFail($this->schoolA->ids['asha_user'])->forceFill(['gender' => 'Female'])->save();
        $field = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', 'gender')->firstOrFail();
        $this->expectException(ValidationException::class);
        app(ProfileDefinitions::class)->field($this->superAdmin(), ['options' => ['Male', 'Other', 'Not specified']] + $field->toArray(), $field->id);
    }
}
