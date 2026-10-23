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
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name', 24);
            $table->string('phase', 16);
            $table->unsignedTinyInteger('sort_order')->unique();
            $table->timestamps();
        });
        Schema::create('academic_rating_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();
            $table->string('name', 40);
            $table->unsignedSmallInteger('min_basis_points');
            $table->unsignedSmallInteger('max_basis_points');
            $table->timestamps();
        });

        // Frozen reference data: future changes require their own migration.
        $now = now();
        foreach (range(0, 12) as $grade) {
            $code = $grade === 0 ? 'R' : (string) $grade;
            $phase = match (true) {
                $grade <= 3 => 'Foundation', $grade <= 6 => 'Intermediate',
                $grade <= 9 => 'Senior', default => 'FET',
            };
            DB::table('grade_levels')->insert(['code' => $code, 'name' => 'Grade '.$code,
                'phase' => $phase, 'sort_order' => $grade, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ([
            [1, 'Not achieved', 0, 2999], [2, 'Elementary achievement', 3000, 3999],
            [3, 'Moderate achievement', 4000, 4999], [4, 'Adequate achievement', 5000, 5999],
            [5, 'Substantial achievement', 6000, 6999], [6, 'Meritorious achievement', 7000, 7999],
            [7, 'Outstanding achievement', 8000, 10000],
        ] as [$level, $name, $min, $max]) {
            DB::table('academic_rating_levels')->insert(['level' => $level, 'name' => $name,
                'min_basis_points' => $min, 'max_basis_points' => $max, 'created_at' => $now, 'updated_at' => $now]);
        }
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_level_id');
        });
        Schema::dropIfExists('academic_rating_levels');
        Schema::dropIfExists('grade_levels');
    }
};
