<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The schools on the platform (Build Bible, section 7, step 1), and school 1:
 * the school this install has run so far, named, branded and timed from its
 * settings rows. Every school-owned row is back-filled to school 1 by the
 * migrations that follow. A fresh install gets school 1 too (named after the
 * app until the install wizard's settings rename it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->string('status', 16)->default('active')->comment('active, suspended or archived');
            $table->string('timezone', 64)->default('Africa/Johannesburg');
            $table->string('package_key', 64)->nullable()->comment('the package the school is on (platform admin sets it)');
            // Branding: shown beside the NavigateEd brand, never instead of it.
            $table->string('short_name', 64)->nullable();
            $table->string('tagline')->nullable();
            $table->string('crest_path')->nullable()->comment('path on the public disk');
            $table->string('theme_color', 16)->nullable();
            $table->string('secondary_color', 16)->nullable();
            $table->timestamps();
        });

        $settings = Schema::hasTable('settings')
            ? DB::table('settings')->whereIn('type', ['school_name', 'school_short_name', 'school_tagline', 'logo1', 'time_zone', 'theme_color', 'secondary_color'])
                ->orderBy('id')->pluck('message', 'type')
            : collect();
        $value = fn (string $type): ?string => ($v = trim((string) ($settings[$type] ?? ''))) === '' ? null : $v;

        $name = $value('school_name') ?? (string) config('app.name', 'School');
        $timezone = $value('time_zone');

        DB::table('schools')->insert([
            'id' => 1,
            'name' => mb_substr($name, 0, 255),
            'slug' => Str::limit(Str::slug($name), 60, '') ?: 'school-1',
            'status' => 'active',
            'timezone' => in_array($timezone, timezone_identifiers_list(), true) ? $timezone : (string) config('app.timezone', 'UTC'),
            'short_name' => $value('school_short_name') === null ? null : mb_substr($value('school_short_name'), 0, 64),
            'tagline' => $value('school_tagline') === null ? null : mb_substr($value('school_tagline'), 0, 255),
            'crest_path' => $value('logo1'),
            'theme_color' => $value('theme_color') === null ? null : mb_substr($value('theme_color'), 0, 16),
            'secondary_color' => $value('secondary_color') === null ? null : mb_substr($value('secondary_color'), 0, 16),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
