<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$setup = require __DIR__.'/cards-fixture.php';
if ((getenv('CARD_FIXTURE_ACTION') ?: 'initial') !== 'initial') {
    return $setup;
}

return app(CurrentSchool::class)->run(1, function () use ($setup): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $card = app(Cards::class)->issue($admin, ['operation_id' => (string) Str::uuid(), 'person_id' => $setup['person'], 'type' => 'learner']);
    $reader = app(CardReaders::class)->register($admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic offline browser reader', 'kind' => 'gate', 'public_key' => (string) getenv('CARD_BROWSER_PUBLIC_KEY'), 'days' => 1]);

    return ['card' => $card['id'], 'credential' => $reader['credential']];
});
