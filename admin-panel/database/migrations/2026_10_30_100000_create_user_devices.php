<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One push registration per device instead of one per account (users.fcm_id).
 * A device belongs to one human at a time; it is bound to the app sign-in token
 * that registered it, so ending that sign-in ends the device's notifications.
 * users.fcm_id / device_type stay as a compatibility projection of the most
 * recent registration for installed clients and older code paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->char('token_hash', 64)->unique();
            $t->text('token');
            $t->string('platform', 16);
            $t->string('app', 32)->nullable();
            $t->unsignedBigInteger('personal_access_token_id')->nullable()->index();
            $t->timestamp('last_seen_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'revoked_at']);
        });

        // Existing single registrations become the account's first device, so
        // nobody stops receiving notifications at deploy. They are not bound to a
        // sign-in token; the next sign-in on that device binds it.
        DB::table('users')->whereNotNull('fcm_id')->where('fcm_id', '!=', '')->whereIn('device_type', ['android', 'ios'])
            ->orderBy('id')->select(['id', 'fcm_id', 'device_type'])
            ->chunkById(500, function ($users) {
                $now = now();
                $rows = [];
                foreach ($users as $user) {
                    $rows[] = [
                        'user_id' => $user->id, 'token_hash' => hash('sha256', (string) $user->fcm_id),
                        'token' => Crypt::encryptString((string) $user->fcm_id), 'platform' => $user->device_type,
                        'app' => null, 'personal_access_token_id' => null, 'last_seen_at' => $now, 'revoked_at' => null,
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                DB::table('user_devices')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        // Derived data only: users.fcm_id still holds each account's latest token.
        Schema::dropIfExists('user_devices');
    }
};
