<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->text('type');
            $table->text('message');
        });

        DB::table('settings')->insert([
            [
                'type' => 'school_name',
                'message' => 'e-School',
            ],
            [
                'type' => 'school_email',
                'message' => 'info@tokensoft.tech',
            ],
            [
                'type' => 'school_phone',
                'message' => '+27 12 345 6789',
            ],
            [
                'type' => 'school_address',
                'message' => 'Pretoria, South Africa',
            ],
            [
                'type' => 'time_zone',
                'message' => 'Africa/Johannesburg',
            ],
            [
                'type' => 'currency_code',
                'message' => 'ZAR',
            ],
            [
                'type' => 'currency_symbol',
                'message' => 'R',
            ],
            [
                'type' => 'date_formate',
                'message' => 'd-m-Y',
            ],
            [
                'type' => 'time_formate',
                'message' => 'h:i A',
            ],
            [
                'type' => 'theme_color',
                'message' => '#4C5EA6',
            ],
            [
                'type' => 'update_warning_modal',
                'message' => 1
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
