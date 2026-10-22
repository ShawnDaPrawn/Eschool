<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spatie permission's teams mode, the team being the school (Build Bible,
 * section 7, step 1; config/permission.php teams => true, team_foreign_key
 * school_id). Every existing role, and every role and permission given to
 * a user, is school 1's.
 *
 * roles: a nullable school_id (null would be a role every school shares;
 * none exists), unique per school by name and guard. model_has_roles and
 * model_has_permissions: school_id NOT NULL and part of the primary key,
 * the shape Spatie's own teams migration gives them, with an index kept on
 * role_id and permission_id for their foreign keys. One ALTER TABLE per
 * table; the tables are small (one row per grant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            DB::statement('ALTER TABLE `roles` ADD COLUMN `school_id` BIGINT UNSIGNED NULL AFTER `id`, '
                .'ADD INDEX `roles_team_foreign_key_index` (`school_id`), '
                .'ADD CONSTRAINT `roles_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)');
            DB::update('UPDATE `roles` SET `school_id` = 1 WHERE `school_id` IS NULL');
            DB::statement('ALTER TABLE `roles` DROP INDEX `roles_name_guard_name_unique`, '
                .'ADD UNIQUE INDEX `roles_school_id_name_guard_name_unique` (`school_id`, `name`, `guard_name`)');

            foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $table => $key) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `school_id` BIGINT UNSIGNED NOT NULL DEFAULT 1 FIRST");
                DB::statement("ALTER TABLE `{$table}` ALTER COLUMN `school_id` DROP DEFAULT, "
                    ."ADD INDEX `{$table}_team_foreign_key_index` (`school_id`), "
                    ."ADD INDEX `{$table}_{$key}_index` (`{$key}`), "
                    .'DROP PRIMARY KEY, '
                    ."ADD PRIMARY KEY (`school_id`, `{$key}`, `model_id`, `model_type`), "
                    ."ADD CONSTRAINT `{$table}_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)");
            }
        });
    }

    /** Back to one set of roles; works while school 1 is the only school with roles. */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $table => $key) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$table}_school_id_foreign`, "
                    .'DROP PRIMARY KEY, '
                    ."ADD PRIMARY KEY (`{$key}`, `model_id`, `model_type`), "
                    ."DROP INDEX `{$table}_{$key}_index`, "
                    ."DROP INDEX `{$table}_team_foreign_key_index`, "
                    .'DROP COLUMN `school_id`');
            }
            DB::statement('ALTER TABLE `roles` DROP FOREIGN KEY `roles_school_id_foreign`, '
                .'DROP INDEX `roles_school_id_name_guard_name_unique`, '
                .'ADD UNIQUE INDEX `roles_name_guard_name_unique` (`name`, `guard_name`), '
                .'DROP INDEX `roles_team_foreign_key_index`, '
                .'DROP COLUMN `school_id`');
        });
    }
};
