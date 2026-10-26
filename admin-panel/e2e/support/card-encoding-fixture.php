<?php

declare(strict_types=1);

use App\Services\Cards\CardAccess;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Models\Platform\Role;
use App\Models\User;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Encoder browser fixtures require an isolated test database.');
}
if ((getenv('CARD_FIXTURE_ACTION') ?: 'initial') === 'withdraw_encoder') {
    return app(CurrentSchool::class)->run(1, function (): array {
        Role::where('name', 'Super Admin')->firstOrFail()->revokePermissionTo(CardAccess::ENCODE);

        return ['withdrawn' => true];
    });
}
$setup = require __DIR__.'/cards-fixture.php';

return app(CurrentSchool::class)->run(1, function () use ($setup): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    Role::where('name', 'Super Admin')->firstOrFail()->givePermissionTo(CardAccess::ENCODE);
    $card = app(Cards::class)->issue($admin, ['operation_id' => (string) Str::uuid(), 'person_id' => $setup['person'], 'type' => 'learner']);
    $reader = app(CardReaders::class)->register($admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic local encoder', 'kind' => 'encoder',
        'public_key' => (string) getenv('CARD_BROWSER_PUBLIC_KEY'), 'days' => 1]);

    return ['card' => $card['id'], 'device' => $reader['id'], 'credential' => $reader['credential'], 'uid' => '04'.strtoupper(bin2hex(random_bytes(6)))];
});
