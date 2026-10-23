<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\FormField;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\User;
use App\Services\People\Profiles\LegacyProfileBackfill;
use App\Services\People\Profiles\ProfileForms;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class LegacyProfileBackfillTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function legacy(array $extra = []): FormField
    {
        return FormField::query()->forceCreate($extra + ['name' => 'Transport_route', 'type' => 'text', 'for' => 1,
            'is_required' => 0, 'rank' => 1, 'default_values' => null]);
    }

    private function source(mixed $values, string $table = 'students', ?int $id = null): void
    {
        DB::table($table)->where('id', $id ?? $this->schoolA->ids['asha'])->update(['dynamic_fields' => json_encode($values, JSON_THROW_ON_ERROR)]);
    }

    private function runBatch(bool $apply = true, string $profile = 'learner', int $school = 1, int $after = 0, int $limit = 500): array
    {
        return app(LegacyProfileBackfill::class)->run($school, $profile, $after, $limit, $apply);
    }

    private function mapped(FormField $old): ProfileFieldDefinition
    {
        return ProfileFieldDefinition::query()->where('legacy_form_field_id', $old->id)->firstOrFail();
    }

    private function stored(ProfileFieldDefinition $field, ?int $user = null): mixed
    {
        $value = DB::table('profile_field_values')->where('definition_id', $field->id)->where('user_id', $user ?? $this->schoolA->ids['asha_user'])->value('value');

        return $value === null ? null : json_decode(Crypt::decryptString($value), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_dry_run_has_no_database_side_effects_and_reports_only_safe_metadata(): void
    {
        $this->legacy();
        $this->source([['Transport_route' => 'PRIVATE ROUTE']]);
        $tables = ['profile_field_categories', 'profile_field_definitions', 'profile_field_values', 'audit_log', 'domain_events', 'students'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        $report = $this->runBatch(false);
        $this->assertSame('dry_run', $report['mode']);
        $this->assertSame(1, $report['definitions_added']);
        $this->assertSame(1, $report['values_added']);
        $this->assertSame(0, $report['issue_count']);
        $this->assertStringNotContainsString('PRIVATE ROUTE', json_encode($report));
        foreach ($before as $table => $snapshot) {
            $this->assertSame($snapshot, DB::table($table)->orderBy('id')->get()->toJson(), $table);
        }
    }

    public function test_apply_encrypts_values_preserves_sources_and_starts_hidden_for_review(): void
    {
        $old = $this->legacy(['other' => '<script>unsafe()</script>']);
        $this->source([['Transport_route' => 'PRIVATE ROUTE']]);
        DB::table('students')->where('id', $this->schoolA->ids['asha'])->update(['religion' => 'PRIVATE RELIGION', 'caste' => 'DO NOT IMPORT']);
        $report = $this->runBatch();
        $this->assertSame(2, $report['values_added']);
        $field = $this->mapped($old);
        $this->assertTrue($field->hidden);
        $this->assertTrue($field->sensitive);
        $this->assertTrue($field->encrypted);
        $this->assertSame(['guardian', 'school_admin'], $field->editable_by);
        $this->assertSame('PRIVATE ROUTE', $this->stored($field));
        $religion = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', 'religion')->firstOrFail();
        $this->assertTrue($religion->hidden);
        $this->assertFalse($religion->required);
        $this->assertSame('PRIVATE RELIGION', $this->stored($religion));
        $raw = DB::table('profile_field_values')->get()->toJson();
        $this->assertStringNotContainsString('PRIVATE', $raw);
        $this->assertStringNotContainsString('DO NOT IMPORT', $raw);
        $this->assertStringNotContainsString('PRIVATE', DB::table('audit_log')->get()->toJson());
        $this->assertStringNotContainsString('unsafe', $field->toJson());
        $this->assertStringContainsString('PRIVATE ROUTE', DB::table('students')->where('id', $this->schoolA->ids['asha'])->value('dynamic_fields'));
        $this->assertSame(1, DB::table('audit_log')->where('action', 'profile.legacy_backfilled')->count());
    }

    public function test_retries_keep_newer_v2_edits_explicit_clears_and_schema_changes(): void
    {
        $old = $this->legacy();
        $this->source([['Transport_route' => 'Old']]);
        $this->runBatch();
        $field = $this->mapped($old);
        $field->update(['hidden' => false, 'label' => 'Reviewed transport']);
        $person = User::findOrFail($this->schoolA->ids['asha_user']);
        app(ProfileForms::class)->save($this->superAdmin(), $person, 'learner', [$field->key => 'New']);
        $this->assertSame(0, $this->runBatch()['values_added']);
        $this->assertSame('New', $this->stored($field));
        app(ProfileForms::class)->save($this->superAdmin(), $person, 'learner', [$field->key => null]);
        $this->assertSame(1, $this->runBatch()['preserved']);
        $this->assertNull($this->stored($field));
        $this->assertSame('Reviewed transport', $field->fresh()->label);
        $this->assertFalse($field->fresh()->hidden);
        $this->assertSame(1, ProfileFieldDefinition::query()->where('legacy_form_field_id', $old->id)->count());
    }

    public function test_parent_and_teacher_values_use_their_own_profile_and_person(): void
    {
        foreach (['parent' => ['parents', 2, 'farid'], 'teacher' => ['teachers', 3, 'tara']] as $profile => [$table, $for, $who]) {
            $old = $this->legacy(['for' => $for, 'name' => 'Office_code']);
            $this->source(['Office_code' => 'PRIVATE '.$profile], $table, $this->schoolA->ids[$profile === 'teacher' ? $who.'_teacher' : $who]);
            $report = $this->runBatch(profile: $profile);
            $this->assertSame(0, $report['issue_count']);
            $field = $this->mapped($old);
            $this->assertSame($profile, $field->profile_type);
            $this->assertSame('PRIVATE '.$profile, $this->stored($field, $this->schoolA->ids[$who.'_user']));
        }
    }

    public function test_school_batches_never_copy_another_schools_values_or_a_mismatched_person(): void
    {
        $oldA = $this->legacy();
        $oldB = app(CurrentSchool::class)->run(2, fn () => $this->legacy());
        $this->source(['Transport_route' => 'School A']);
        $this->source(['Transport_route' => 'Baobab PRIVATE'], id: $this->schoolB->ids['asha']);
        $this->runBatch();
        $this->assertSame(0, DB::table('profile_field_values')->where('school_id', 2)->count());
        $this->assertSame('School A', $this->stored($this->mapped($oldA)));
        $this->runBatch(school: 2);
        app(CurrentSchool::class)->run(2, function () use ($oldB) {
            $this->assertSame('Baobab PRIVATE', $this->stored($this->mapped($oldB), $this->schoolB->ids['asha_user']));
        });
        DB::table('profile_field_values')->delete();
        DB::table('students')->where('id', $this->schoolA->ids['asha'])->update(['user_id' => $this->schoolB->ids['asha_user']]);
        $report = $this->runBatch();
        $this->assertContains('person_school_mismatch', array_column($report['issues'], 'code'));
        $this->assertSame(0, DB::table('profile_field_values')->count());
        $this->assertSame(1, app(CurrentSchool::class)->id());
    }

    public function test_cursor_resumes_and_repeating_a_batch_does_not_duplicate_data(): void
    {
        $this->legacy();
        foreach (['asha', 'ben', 'cara'] as $who) {
            $this->source(['Transport_route' => $who], id: $this->schoolA->ids[$who]);
        }
        $report = $this->runBatch(limit: 1);
        $this->assertSame(1, $report['scanned']);
        $this->assertTrue($report['has_more']);
        $this->assertSame(0, $this->runBatch(limit: 1)['values_added']);
        $next = $this->runBatch(after: $report['next_after'], limit: 1000);
        $this->assertFalse($next['has_more']);
        $this->assertSame(2, $next['values_added']);
        $this->assertSame(3, DB::table('profile_field_values')->count());
    }

    public function test_malformed_or_conflicting_maps_are_reported_without_blocking_other_people(): void
    {
        $this->legacy();
        $this->source([['Transport_route' => 'PRIVATE A'], ['Transport_route' => 'PRIVATE B']]);
        DB::table('students')->where('id', $this->schoolA->ids['ben'])->update(['dynamic_fields' => '{PRIVATE INVALID']);
        $this->source(['Transport_route' => 'Valid'], id: $this->schoolA->ids['cara']);
        $report = $this->runBatch();
        $this->assertSame(2, $report['issue_count']);
        $this->assertSame(1, $report['values_added']);
        $this->assertStringNotContainsString('PRIVATE', json_encode($report));
    }

    public function test_values_follow_shared_choice_and_numeric_validation(): void
    {
        $choice = $this->legacy(['name' => 'Options', 'type' => 'checkbox', 'default_values' => '["Red","Blue"]']);
        $number = $this->legacy(['name' => 'Distance', 'type' => 'number']);
        $this->source([['Options' => ['Red', 'Blue']], ['Distance' => 12]]);
        $this->assertSame(0, $this->runBatch()['issue_count']);
        $this->assertSame(['Red', 'Blue'], $this->stored($this->mapped($choice)));
        $this->assertSame('12', $this->stored($this->mapped($number)));
        $this->source(['Options' => ['Outside'], 'Distance' => 'abc'], id: $this->schoolA->ids['ben']);
        $report = $this->runBatch();
        $this->assertSame(2, $report['issue_count']);
        $this->assertSame(2, DB::table('profile_field_values')->count());
    }

    public function test_actual_legacy_checkbox_maps_are_normalised_to_lists_for_app_contracts(): void
    {
        $old = $this->legacy(['name' => 'Options', 'type' => 'checkbox', 'for' => 2, 'default_values' => '["Red","Blue"]']);
        $this->source([['Options' => ['Red' => 'Red', 'Blue' => 'Blue']]], 'parents', $this->schoolA->ids['farid']);
        $this->assertSame(0, $this->runBatch(profile: 'parent')['issue_count']);
        $field = $this->mapped($old);
        $this->assertSame(['Red', 'Blue'], $this->stored($field, $this->schoolA->ids['farid_user']));
        $field->update(['hidden' => false]);
        $this->app['auth']->forgetGuards();
        $answer = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/v2/me/profile?profile_type=parent')->assertOk();
        $value = collect($answer->json('data.categories'))->pluck('fields')->flatten(1)->firstWhere('key', $field->key)['value'];
        $this->assertSame(['choices' => ['Red', 'Blue']], $value);
    }

    public function test_a_value_committed_after_the_transaction_snapshot_is_preserved(): void
    {
        $old = $this->legacy();
        $this->runBatch(); // Create mapping while the legacy value is empty.
        $field = $this->mapped($old);
        $this->source(['Transport_route' => 'STALE PRIVATE VALUE']);
        $primary = DB::connection();
        $isolation = collect($primary->select("SHOW VARIABLES LIKE '%isolation'"))->pluck('Value')->all();
        $this->assertContains('REPEATABLE-READ', $isolation);
        config(['database.connections.profile_race' => config('database.connections.mysql')]);
        $other = DB::connection('profile_race');
        // Fixtures are inside the test's outer transaction; the second writer
        // skips FK checks solely to insert a committed competing value against
        // those uncommitted synthetic IDs. Production FK checks stay enabled.
        $other->statement('SET FOREIGN_KEY_CHECKS=0');
        $armed = true;
        $insertedId = null;
        DB::listen(function (QueryExecuted $query) use (&$armed, &$insertedId, $other, $field) {
            if ($armed && $query->connectionName === 'mysql' && str_starts_with($query->sql, 'select * from `students`')) {
                $armed = false;
                $insertedId = $other->table('profile_field_values')->insertGetId(['school_id' => 1,
                    'user_id' => $this->schoolA->ids['asha_user'], 'definition_id' => $field->id, 'value' => null,
                    'encrypted' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        // UsesTwoSchools registers its rollback first; remove the independent
        // committed insert only after that releases the current-read row lock.
        $this->beforeApplicationDestroyed(function () use ($other, &$insertedId) {
            if ($insertedId !== null) {
                $other->table('profile_field_values')->where('id', $insertedId)->delete();
            }
            $other->statement('SET FOREIGN_KEY_CHECKS=1');
            DB::purge('profile_race');
        });
        try {
            $report = $this->runBatch();
        } finally {
            $armed = false;
        }
        $this->assertNotNull($insertedId);
        $this->assertSame(0, $report['values_added']);
        $this->assertSame(1, $report['preserved']);
        $this->assertSame(0, $report['issue_count']);
    }

    public function test_deleted_mappings_are_never_resurrected_and_key_collisions_are_reported(): void
    {
        $old = $this->legacy();
        $this->source(['Transport_route' => 'Old']);
        $this->runBatch();
        $field = $this->mapped($old);
        $field->delete();
        $report = $this->runBatch();
        $this->assertContains('mapping_unavailable', array_column($report['issues'], 'code'));
        $this->assertTrue($field->fresh()->trashed());
        $other = $this->legacy(['name' => 'Other']);
        $field->forceFill(['key' => 'legacy_'.$other->id])->save();
        $report = $this->runBatch();
        $this->assertContains('mapping_conflict', array_column($report['issues'], 'code'));
    }

    public function test_ambiguous_names_invalid_options_and_unknown_populated_keys_are_not_guessed(): void
    {
        $this->legacy();
        $this->legacy(['name' => 'Transport route']);
        $this->legacy(['name' => 'Broken', 'type' => 'dropdown', 'default_values' => '{"a":"PRIVATE"}']);
        $this->source(['Unknown_private_field' => 'PRIVATE']);
        $report = $this->runBatch();
        $this->assertSame(0, $report['definitions_added']);
        $this->assertSame(4, $report['issue_count']);
        $this->assertStringNotContainsString('PRIVATE', json_encode($report));
    }

    public function test_file_paths_are_reported_without_copying_or_publishing_them(): void
    {
        $old = $this->legacy(['name' => 'Document', 'type' => 'file']);
        $this->source(['Document' => '../schools/2/PRIVATE.pdf']);
        $report = $this->runBatch();
        $this->assertSame('private_file_migration_pending', $report['issues'][0]['code']);
        $this->assertSame(0, DB::table('profile_field_values')->count());
        $this->assertTrue($this->mapped($old)->hidden);
        $this->assertSame(0, DB::table('profile_files')->count());
        $this->assertStringNotContainsString('PRIVATE', json_encode($report));
    }

    public function test_ambiguous_people_are_reported_and_float_values_are_not_rounded_into_strings(): void
    {
        $this->legacy(['type' => 'number']);
        $this->source(['Transport_route' => 1.23456789012345]);
        $this->assertSame('invalid_value', $this->runBatch()['issues'][0]['code']);
        $this->source(['Transport_route' => 'PRIVATE'], id: $this->schoolA->ids['ben']);
        DB::table('students')->where('id', $this->schoolA->ids['ben'])->update(['user_id' => $this->schoolA->ids['asha_user']]);
        $report = $this->runBatch();
        $this->assertSame(['ambiguous_person_records', 'ambiguous_person_records'], array_column($report['issues'], 'code'));
        $this->assertSame(0, DB::table('profile_field_values')->count());
    }

    public function test_existing_bad_storage_is_reported_without_overwriting_it(): void
    {
        $old = $this->legacy();
        $this->source(['Transport_route' => 'Old']);
        $this->runBatch();
        $field = $this->mapped($old);
        DB::table('profile_field_values')->where('definition_id', $field->id)->update(['value' => 'PRIVATE BAD STORAGE', 'encrypted' => false]);
        $report = $this->runBatch();
        $this->assertSame('existing_storage_invalid', $report['issues'][0]['code']);
        $this->assertSame(0, $report['values_added']);
        $this->assertSame('PRIVATE BAD STORAGE', DB::table('profile_field_values')->where('definition_id', $field->id)->value('value'));
        $this->assertStringNotContainsString('PRIVATE', json_encode($report));
    }

    public function test_migrated_sensitive_fields_remain_absent_from_owner_guardian_and_teacher_forms_until_review(): void
    {
        $old = $this->legacy();
        $this->source(['Transport_route' => 'PRIVATE']);
        $this->runBatch();
        foreach (['asha', 'farid', 'tara'] as $who) {
            $this->app['auth']->forgetGuards();
            $response = $this->withToken($this->schoolA->tokens[$who])->getJson('/api/v2/people/'.$this->schoolA->ids['asha_user'].'/profile?profile_type=learner')->assertOk();
            $this->assertStringNotContainsString('PRIVATE', $response->getContent());
        }
        $field = $this->mapped($old);
        $field->update(['hidden' => false]);
        $this->app['auth']->forgetGuards();
        $response = $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/v2/me/profile')->assertOk();
        $this->assertStringContainsString('PRIVATE', $response->getContent());
        $this->app['auth']->forgetGuards();
        $response = $this->withToken($this->schoolA->tokens['tara'])->getJson('/api/v2/people/'.$this->schoolA->ids['asha_user'].'/profile?profile_type=learner')->assertOk();
        $this->assertStringNotContainsString('PRIVATE', $response->getContent());
    }

    public function test_command_requires_an_explicit_bounded_school_profile_and_defaults_to_dry_run(): void
    {
        $this->assertSame(2, Artisan::call('profiles:backfill-legacy'));
        $this->assertSame(2, Artisan::call('profiles:backfill-legacy', ['--school' => '1junk', '--profile' => 'learner']));
        $this->assertSame(2, Artisan::call('profiles:backfill-legacy', ['--school' => 1, '--profile' => 'learner', '--limit' => 1001]));
        $this->legacy();
        $this->source(['Transport_route' => 'PRIVATE']);
        $this->assertSame(0, Artisan::call('profiles:backfill-legacy', ['--school' => 1, '--profile' => 'learner']));
        $this->assertSame('dry_run', json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)['mode']);
        $this->assertSame(0, DB::table('profile_field_values')->count());
        $this->source(['Unknown' => 'PRIVATE']);
        $this->assertSame(1, Artisan::call('profiles:backfill-legacy', ['--school' => 1, '--profile' => 'learner', '--apply' => true]));
        $this->assertStringNotContainsString('PRIVATE', Artisan::output());
    }
}
