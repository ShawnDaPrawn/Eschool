This page covers the admin panel's Artisan commands and scheduled tasks: where they are defined, their signatures and options, exactly what they delete, when they run, and how to run them by hand. Laravel's own commands (`migrate`, `db:seed`, `storage:link` and so on) are covered where they matter in [Installation-Wizard](Installation-Wizard) and [Deployment](Deployment).

## Where commands and schedules are defined

| File | Role |
|---|---|
| [routes/console.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/routes/console.php) | Closure commands and the whole schedule (`Schedule::` facade). Registered by `withRouting(commands: ...)` in `bootstrap/app.php`. |
| [app/Console/Commands/](https://github.com/ShawnDaPrawn/Eschool/tree/main/admin-panel/app/Console/Commands) | Class-based commands. Discovered automatically: `Application::configure()` calls `withCommands()`, which loads `app/Console/Commands`. |
| `app/Console/Kernel.php` | Left over from older Laravel. Laravel 12 binds the framework's own console kernel (`withKernels()`), so this class, its empty `schedule()` and its `commands()` method are never used. |
| `bootstrap/app.php` | Has no `withSchedule()` call; nothing is scheduled there. |

`routes/console.php` in full:

```php
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Runs daily to purge old chat messages and keep the table size manageable.
Schedule::command('app:cleanup-old-chat-messages')->daily()->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
```

## Schedule

`php artisan schedule:list` shows:

```text
0 0 * * *  php artisan app:cleanup-old-chat-messages
0 0 * * *  php artisan sanctum:prune-expired --hours=24
```

| Task | Frequency | Overlap protection | Purpose |
|---|---|---|---|
| `app:cleanup-old-chat-messages` | `daily()`: 00:00 | `withoutOverlapping()`: a cache mutex, expiring after 1440 minutes, skips a run while the previous one is still going | Chat retention |
| `sanctum:prune-expired --hours=24` | `daily()`: 00:00 | none | Remove expired app tokens |

"Midnight" is in the scheduler timezone: `config('app.schedule_timezone')` is not set, so Laravel uses `config('app.timezone')`. `SettingsConfigService` sets that from the `time_zone` setting (General Settings) on every boot, falling back to `TIMEZONE` in `.env` and then `UTC`. Because the settings migration seeds `time_zone = Asia/Kolkata`, a fresh install runs these at midnight India time until the timezone is changed.

Nothing runs unless cron calls the scheduler every minute:

```cron
* * * * * cd /path/to/admin-panel && php artisan schedule:run >> /dev/null 2>&1
```

For local development, `php artisan schedule:work` runs the scheduler in the foreground. `php artisan schedule:test` lets you pick one task and run it now.

## app:cleanup-old-chat-messages

[CleanupOldChatMessages.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Console/Commands/CleanupOldChatMessages.php)

| | |
|---|---|
| Signature | `app:cleanup-old-chat-messages` (no arguments or options) |
| Description | Delete chat messages (and their files) older than the configured retention period |
| Scheduled | Daily, without overlapping |
| Output | `Deleted N old chat message(s).`; also `Log::info('Cleaned up old chat messages', ['deleted' => N])`, which is not written with the default `LOG_LEVEL=error` |
| Exit code | Always `0` (`self::SUCCESS`) unless an exception is thrown |

It calls `ChatMessageCleanupService::deleteOlderThanConfiguredDays()` in [app/Services/ChatMessageCleanupService.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/ChatMessageCleanupService.php):

1. `$days = (int) (getSettings()['automatically_messages_removed_days'] ?? 30)`. The setting is edited on the Chat Settings page (`chat_setting_update`, validated only as `required`).
2. `$cutoff = now()->subDays($days)`.
3. Loads every `ChatMessage` with its `file` relation where `whereDate('date', '<', $cutoff)`, so messages dated before the cutoff's calendar day. The `date` column is set to `Carbon::now()` when an app sends a message.
4. `deleteMessagesWithFiles()`: for each message, for each `ChatFile`, deletes `file_name` from the `public` disk if it exists and deletes the `chat_files` row; then deletes the `chat_messages` row.

What else goes with them:

- `read_messages.last_read_message_id` references `chat_messages` with `ON DELETE CASCADE`, so read markers pointing at a deleted message are deleted by the database.
- `chat_rooms` and `chat_members` are not touched.
- All matching messages are loaded into memory at once, and each row is deleted with its own query.

A retention value of `0`, or anything that casts to `0`, deletes every message dated before today.

The same service backs the manual delete on the Chat Settings page: `POST /delete-chat-messages` (`SettingController@delete_chat_messages`, permission `chat-settings`) validates `from_date` and `to_date` (`to_date` must be after `from_date`) and calls `deleteBetweenDates()`, which deletes messages whose date is in that inclusive range, with their files. The old public URL `delete-chat-message/cron-job` has been removed; `SettingController::cron_job()` still exists but has no route. See [Chat](Chat).

Run by hand:

```bash
php artisan app:cleanup-old-chat-messages
```

## sanctum:prune-expired

Laravel Sanctum's command, scheduled with `--hours=24`.

| | |
|---|---|
| Signature | `sanctum:prune-expired {--hours=24 : The number of hours to retain expired Sanctum tokens}` |
| Deletes | `personal_access_tokens` rows whose `expires_at` is more than `--hours` in the past, and, because `config('sanctum.expiration')` is set (`SANCTUM_EXPIRATION`, default 43200 minutes), rows whose `created_at` is older than `expiration + hours * 60` minutes |

With the defaults that is any app token created more than 30 days and 24 hours ago. Expired tokens are already rejected whenever they are used (`SecurityTest::test_expired_app_tokens_are_rejected`); the prune only keeps the table small. The apps log the user out when they get a 401. See [Security-Model](Security-Model).

```bash
php artisan sanctum:prune-expired --hours=24
```

## app:delete-fees

[DeleteFeesPaid.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Console/Commands/DeleteFeesPaid.php)

| | |
|---|---|
| Signature | `app:delete-fees` (no arguments or options) |
| Description | Delete Fees Data |
| Scheduled | No |
| Confirmation | None, even with `APP_ENV=production` |
| Output | Nothing on the console; `Log::info('data-delete')` |

It runs, in this order:

```php
DB::table('fees_choiceables')->delete();
DB::table('payment_transactions')->delete();
DB::table('fees_paids')->delete();
```

These are query-builder deletes, so every row is removed permanently even though the three models use soft deletes. `paid_installment_fees.payment_transaction_id` references `payment_transactions` with `ON DELETE CASCADE`, so all paid installment records go too. Fee definitions (`fees_types`, `fees_classes`, `installment_fees`) and session years stay.

The effect is to wipe every fee payment and online transaction in every session year, for every student. Nothing in the code calls it. Treat it as a reset tool for test or demo data; take a database backup before running it anywhere else:

```bash
php artisan app:delete-fees
```

See [Payments](Payments) for what these tables hold.

## inspire

The closure command left from the Laravel skeleton: `php artisan inspire` prints a random quote.

## Other commands you will use

Commands from Laravel and packages that the app's own code runs, or that operators need:

| Command | Used by / when |
|---|---|
| `migrate --force` | Installer, System Update, `/migrate` URL, manual updates |
| `db:seed --class=InstallationSeeder --force` | Installer, System Update, `/seeder_install` URL |
| `db:seed --class=AddSuperAdminSeeder --force` | Installer (once only) |
| `db:seed --class=EmailSeeder --force` | Installer, System Update |
| `key:generate` | Before the first web request; the installer runs it again with `--force` |
| `storage:link` | Installer, `/storage-link` URL |
| `view:clear`, `route:clear`, `config:clear`, `cache:clear` | `InstallationSeeder`, `/clear`, `/migrate`, `/seeder_install`, installer finish step (which also runs `optimize:clear`) |
| `optimize`, `optimize:clear` | Production caching; see [Deployment](Deployment#caching) |
| `permission:cache-reset` | Spatie permission cache, after editing roles or permissions directly in the database |
| `schedule:run`, `schedule:list`, `schedule:work`, `schedule:test` | The scheduler |

The wizard package's default command `install:users` does not exist in this app; `config/installer.php` replaces it with the seeders above.

## See also

- [Chat](Chat)
- [Payments](Payments)
- [Deployment](Deployment)
- [Configuration](Configuration)
- [Security-Model](Security-Model)
