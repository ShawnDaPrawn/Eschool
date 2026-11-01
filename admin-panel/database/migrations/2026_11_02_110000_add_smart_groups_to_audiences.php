<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved audiences become fixed lists or smart groups, private or shared
 * (Action Plan 2.5). A fixed list keeps its explicit people; a smart group
 * keeps rules (grades, homerooms, teaching and activity groups) resolved again
 * whenever a message is previewed or sent. Existing lists stay fixed and
 * shared, as they behaved before. engineering/specs/communication/custom-groups.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audiences', function (Blueprint $t) {
            $t->string('kind', 10)->default('fixed')->after('created_by');
            $t->boolean('shared')->default(true)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('audiences', function (Blueprint $t) {
            $t->dropColumn(['kind', 'shared']);
        });
    }
};
