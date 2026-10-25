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
        Schema::table('incident_reports', fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'incident_report_school_identity'));
        Schema::create('incident_photos', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('incident_report_id');
            $t->uuid('operation_id');
            $t->string('source_digest', 64);
            $t->string('sha256', 64);
            $t->string('path');
            $t->unsignedInteger('bytes');
            $t->unsignedInteger('width');
            $t->unsignedInteger('height');
            $t->unsignedInteger('report_revision');
            $t->longText('thumbnail');
            $t->longText('scan_receipt');
            $t->timestamps();
            $t->unique(['school_id', 'incident_report_id', 'operation_id'], 'incident_photo_operation');
            $t->foreign(['school_id', 'incident_report_id'], 'incident_photo_report_scope')->references(['school_id', 'id'])->on('incident_reports');
        });
    }

    public function down(): void
    {
        if (DB::table('incident_photos')->exists()) {
            throw new RuntimeException('Retained private incident photos prevent destructive rollback.');
        }
        Schema::drop('incident_photos');
        Schema::table('incident_reports', fn (Blueprint $t) => $t->dropUnique('incident_report_school_identity'));
    }
};
