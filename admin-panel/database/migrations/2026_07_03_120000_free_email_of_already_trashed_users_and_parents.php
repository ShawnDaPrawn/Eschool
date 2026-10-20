<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Rows soft-deleted before this migration still hold their original email,
     * which blocks reuse of that email (both at DB unique-index level for `users`,
     * and at the app-validation level for `parents`). Mutate them the same way
     * User::booted()/Parents::booted() now do on every future soft-delete.
     *
     * @return void
     */
    public function up() {
        DB::table('users')
            ->whereNotNull('deleted_at')
            ->whereNotNull('email')
            ->where('email', 'NOT LIKE', '%::deleted-%')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'email' => User::buildDeletedEmailPlaceholder($user->email, $user->id),
                    ]);
                }
            });

        DB::table('parents')
            ->whereNotNull('deleted_at')
            ->whereNotNull('email')
            ->where('email', 'NOT LIKE', '%::deleted-%')
            ->orderBy('id')
            ->chunkById(200, function ($parents) {
                foreach ($parents as $parent) {
                    DB::table('parents')->where('id', $parent->id)->update([
                        'email' => User::buildDeletedEmailPlaceholder($parent->email, $parent->id),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * Strips the `::deleted-{id}-{timestamp}` suffix appended in up(), restoring
     * the original email. Only fails to fully restore if the original email was
     * longer than 190 chars (truncated in up()) — not possible with the standard
     * 255-char `email` column.
     *
     * @return void
     */
    public function down() {
        DB::table('users')
            ->whereNotNull('deleted_at')
            ->where('email', 'LIKE', '%::deleted-%')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $original = preg_replace('/::deleted-' . $user->id . '-\d+$/', '', $user->email);
                    if ($original !== $user->email) {
                        DB::table('users')->where('id', $user->id)->update(['email' => $original]);
                    }
                }
            });

        DB::table('parents')
            ->whereNotNull('deleted_at')
            ->where('email', 'LIKE', '%::deleted-%')
            ->orderBy('id')
            ->chunkById(200, function ($parents) {
                foreach ($parents as $parent) {
                    $original = preg_replace('/::deleted-' . $parent->id . '-\d+$/', '', $parent->email);
                    if ($original !== $parent->email) {
                        DB::table('parents')->where('id', $parent->id)->update(['email' => $original]);
                    }
                }
            });
    }
};
