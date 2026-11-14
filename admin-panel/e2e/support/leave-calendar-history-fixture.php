<?php

declare(strict_types=1);

use App\Models\Leave;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$database = (string) config('database.connections.'.config('database.default').'.database');
abort_unless(str_ends_with($database, '_test') || $database === 'eschool_e2e', 403);

return app(CurrentSchool::class)->run(1, function (): array {
    $year = SessionYear::findOrFail((int) schoolSetting('session_year'));
    $people = [];
    foreach (['history' => ['staff-leave-list'], 'owner' => ['leave-create']] as $name => $permissions) {
        $role = Role::firstOrCreate(['school_id' => 1, 'name' => 'Synthetic calendar '.$name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $person = User::firstOrCreate(['email' => 'calendar.'.$name.'@demo.navigateed.test'],
            ['first_name' => 'CalendarPrivacy'.ucfirst($name), 'last_name' => 'Staff', 'password' => Hash::make('NavigateDemo2026'), 'status' => 1]);
        $person->assignRole($role);
        SchoolMembership::updateOrCreate(['school_id' => 1, 'user_id' => $person->id, 'role' => $role->name],
            ['status' => 'active', 'starts_on' => $year->getRawOriginal('start_date'), 'ends_on' => null]);
        $people[$name] = $person;
    }
    $date = min(max(now()->toDateString(), $year->getRawOriginal('start_date')), $year->getRawOriginal('end_date'));
    $reason = 'Synthetic calendar private medical reason';
    $file = 'https://documents.example.invalid/calendar-private-evidence.pdf';
    $record = Leave::updateOrCreate(['user_id' => $people['owner']->id, 'session_year_id' => $year->id, 'reason' => $reason],
        ['from_date' => $date, 'to_date' => $date, 'status' => 1]);
    $record->leave_detail()->firstOrCreate(['date' => $date], ['type' => 'Full', 'session_year_id' => $year->id]);
    $record->file()->firstOrCreate(['file_name' => 'Synthetic private evidence'], ['file_url' => $file, 'type' => 4]);

    return ['email' => $people['history']->email, 'name' => $people['owner']->full_name,
        'record' => $record->id, 'date' => $date, 'reason' => $reason, 'file' => $file];
});
