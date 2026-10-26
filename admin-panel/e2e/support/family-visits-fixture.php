<?php

declare(strict_types=1);

use App\Models\FamilyVisits\GatePass;
use App\Models\FamilyVisits\MeetingSlot;
use App\Models\Students;
use App\Models\User;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\FamilyVisits\GatePasses;
use App\Services\FamilyVisits\Meetings;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Visit browser fixtures require an isolated test database.');
}
// Reuses only generated card portraits and synthetic consent/approval fixtures.
require base_path('e2e/support/cards-fixture.php');

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $parent = User::where('email', 'parent.a@demo.navigateed.test')->firstOrFail();
    $teacher = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
    $learner = Students::where('admission_no', 'DEMO-A-0001')->firstOrFail();
    app(ModuleGate::class)->change('family_visits', true, $admin);
    foreach (MeetingSlot::where('title', '!=', '')->where('created_by', $admin->id)->where('status', '!=', 'cancelled')->get() as $slot) {
        if (str_starts_with($slot->title, 'Synthetic browser visit ')) {
            app(Meetings::class)->cancel($admin, $slot->id, ['operation_id' => (string) Str::uuid(), 'revision' => $slot->revision]);
        }
    }
    foreach (GatePass::where('learner_id', $learner->id)->whereIn('status', ['pending', 'approved'])->get() as $pass) {
        if ($pass->reason === 'Synthetic browser visit collection') {
            app(GatePasses::class)->cancel($admin, $pass->id, ['operation_id' => (string) Str::uuid(), 'revision' => $pass->revision]);
        }
    }
    $card = app(Cards::class)->issue($admin, ['operation_id' => (string) Str::uuid(), 'person_id' => $learner->user_id, 'type' => 'learner']);
    $reader = app(CardReaders::class)->register($admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic browser visit gate', 'kind' => 'gate', 'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1]);
    $clock = \Carbon\CarbonImmutable::instance(now())->setTimezone(app(CurrentSchool::class)->school()->timezone);
    $pass = app(GatePasses::class)->request($parent, ['operation_id' => (string) Str::uuid(), 'learner_id' => (int) $learner->id,
        'collector' => 'Synthetic browser designated collector', 'reason' => 'Synthetic browser visit collection', 'starts_at' => $clock->format('Y-m-d\TH:i'), 'ends_at' => $clock->addHour()->format('Y-m-d\TH:i')]);
    $parent->tokens()->where('name', 'synthetic-visit-browser')->delete();

    return ['token' => $parent->createToken('synthetic-visit-browser', ['portal:parent'])->plainTextToken, 'guardian_id' => (int) $parent->id,
        'host' => (int) $teacher->id, 'learner' => (int) $learner->id, 'name' => $learner->user->full_name, 'card' => $card['id'], 'reader' => $reader['credential'], 'pass' => $pass['id'],
        'starts' => $clock->addDays(2)->setTime(17, 0)->format('Y-m-d\TH:i'), 'ends' => $clock->addDays(2)->setTime(17, 15)->format('Y-m-d\TH:i'), 'deadline' => $clock->addDay()->setTime(12, 0)->format('Y-m-d\TH:i')];
});
