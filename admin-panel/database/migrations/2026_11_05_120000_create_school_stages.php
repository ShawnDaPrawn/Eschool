<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The age stage of each grade, per school (Action Plan 4.1): early years,
 * junior or high school. Every existing school gets the owner's default
 * mapping (Grade R to 2 early years, 3 to 7 junior, 8 to 12 high school);
 * a school then moves boundaries itself under Settings, School stages.
 * engineering/specs/platform/school-stages.md.
 */
return new class extends Migration
{
    /** Frozen copy of the defaults: later changes to the service never change this migration. */
    private const DEFAULTS = [
        'R' => 'early_years', '1' => 'early_years', '2' => 'early_years',
        '3' => 'junior', '4' => 'junior', '5' => 'junior', '6' => 'junior', '7' => 'junior',
        '8' => 'high_school', '9' => 'high_school', '10' => 'high_school', '11' => 'high_school', '12' => 'high_school',
    ];

    public function up(): void
    {
        Schema::create('school_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $table->string('stage', 16);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('The school leader who last chose this stage; null while it is the default');
            $table->timestamps();

            $table->unique(['school_id', 'grade_level_id']);
        });

        $now = now();
        $grades = DB::table('grade_levels')->pluck('id', 'code');
        foreach (DB::table('schools')->pluck('id') as $school) {
            $rows = [];
            foreach (self::DEFAULTS as $code => $stage) {
                if (isset($grades[$code])) {
                    $rows[] = ['school_id' => $school, 'grade_level_id' => $grades[$code], 'stage' => $stage, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            DB::table('school_stages')->insert($rows);
        }
    }

    public function down(): void
    {
        // Default rows can be rebuilt; a school's own choices cannot.
        if (Schema::hasTable('school_stages') && DB::table('school_stages')->whereNotNull('changed_by')->exists()) {
            throw new RuntimeException('Schools have chosen their own stages; rollback refused.');
        }
        Schema::dropIfExists('school_stages');
    }
};
