<?php

use App\Models\Commerce\TicketEvent;
use App\Models\Event;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

// Explicitly synthetic local browser records; no admissions are sold or issued.
$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Event readiness browser fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    foreach (['vendors', 'ticketing'] as $module) {
        SchoolModule::updateOrCreate(['module' => $module], ['enabled' => true, 'in_package' => true, 'read_only' => false]);
    }
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $marker = (string) Str::uuid();
    $event = new Event;
    $event->forceFill(['title' => 'Synthetic admission link '.$marker, 'type' => 'single', 'session_year_id' => schoolSetting('session_year'),
        'start_date' => now()->addDays(3)->toDateString(), 'description' => 'Synthetic office readiness verification'])->save();
    $ticket = TicketEvent::create(['name' => 'Synthetic admissions '.$marker, 'status' => 'draft', 'revision' => 1, 'changed_by' => $admin->id,
        'sales_from' => now()->utc(), 'sales_until' => now()->addDays(3)->utc(), 'starts_at' => now()->addDays(3)->utc(), 'ends_at' => now()->addDays(3)->addHours(2)->utc()]);

    return ['event' => $event->id, 'event_title' => $event->title, 'ticket' => $ticket->id, 'ticket_name' => $ticket->name];
});
