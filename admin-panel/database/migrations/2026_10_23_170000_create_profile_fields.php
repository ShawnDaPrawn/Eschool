<?php

declare(strict_types=1);

use App\Services\People\Profiles\ProfileDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_field_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('profile_type', 20);
            $table->string('key', 64);
            $table->string('label', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'profile_type', 'key'], 'profile_category_key');
        });
        Schema::create('profile_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('category_id')->constrained('profile_field_categories');
            $table->string('profile_type', 20);
            $table->string('key', 64);
            $table->string('label', 120);
            $table->string('type', 20);
            $table->string('source', 64)->default('custom');
            $table->json('validation');
            $table->json('options');
            $table->json('visible_to');
            $table->json('editable_by');
            $table->boolean('required')->default(false);
            $table->boolean('platform_locked')->default(false);
            $table->boolean('sensitive')->default(false);
            $table->boolean('encrypted')->default(true);
            $table->boolean('hidden')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('legacy_form_field_id')->nullable()->constrained('form_fields');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'profile_type', 'key'], 'profile_definition_key');
            $table->unique(['school_id', 'legacy_form_field_id'], 'profile_legacy_field');
        });
        Schema::create('profile_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('definition_id')->constrained('profile_field_definitions');
            $table->longText('value')->nullable();
            $table->boolean('encrypted')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'user_id', 'definition_id'], 'profile_person_value');
        });
        foreach (DB::table('schools')->orderBy('id')->pluck('id') as $schoolId) {
            ProfileDefaults::seed((int) $schoolId);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_field_values');
        Schema::dropIfExists('profile_field_definitions');
        Schema::dropIfExists('profile_field_categories');
    }
};
