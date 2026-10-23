<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('module', 64);
            $table->boolean('in_package');
            $table->boolean('enabled');
            $table->boolean('read_only')->default(false);
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'module']);
        });
        DB::table('schools')->orderBy('id')->each(function ($school) {
            $package = config('school_packages.'.($school->package_key ?? 'legacy'), []);
            foreach (config('modules') as $key => $definition) {
                DB::table('school_modules')->insert([
                    'school_id' => $school->id, 'module' => $key,
                    'in_package' => ($definition['always_on'] ?? false) || in_array($key, $package, true),
                    'enabled' => ($definition['always_on'] ?? false) || ($definition['legacy'] ?? $definition['default'] ?? false),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_modules');
    }
};
