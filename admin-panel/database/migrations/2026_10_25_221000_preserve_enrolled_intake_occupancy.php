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
        Schema::table('intake_grades', fn (Blueprint $t) => $t->unsignedInteger('occupied_places')->default(0));
        DB::table('intake_grades')->orderBy('id')->chunkById(100, function ($grades): void {
            foreach ($grades as $grade) {
                $occupied = DB::table('applications')->where('school_id', $grade->school_id)
                    ->where('intake_grade_id', $grade->id)->where('state', 'enrolled')->count();
                DB::table('intake_grades')->where('school_id', $grade->school_id)->where('id', $grade->id)->update(['occupied_places' => $occupied]);
            }
        });
    }

    public function down(): void
    {
        if (DB::table('intake_grades')->where('occupied_places', '>', 0)->exists()) {
            throw new RuntimeException('Enrolled intake occupancy must be retained.');
        }
        Schema::table('intake_grades', fn (Blueprint $t) => $t->dropColumn('occupied_places'));
    }
};
