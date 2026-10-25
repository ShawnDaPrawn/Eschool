<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\User;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ExpectedAccountTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    public function test_cookie_account_replacement_is_refused_before_returning_private_context(): void
    {
        $old = User::findOrFail($this->schoolA->ids['tara_user']);
        $replacement = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->actingAs($replacement, 'web');
        $this->withHeader('X-Account-Id', (string) $old->id)->getJson('/api/v2/me/context')->assertForbidden()->assertJsonPath('data', null);
        $this->flushHeaders()->withHeader('X-Account-Id', (string) $replacement->id)->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.user.id', $replacement->id);
        $this->flushHeaders()->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.user.id', $replacement->id);
    }

    public function test_expected_header_never_grants_authentication_or_role_authority(): void
    {
        $user = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->withHeader('X-Account-Id', (string) $user->id)->getJson('/api/v2/me/context')->assertUnauthorized();
        $this->actingAs($user, 'web');
        foreach (['0', '01', '-1', '1.0', '1e3', str_repeat('9', 40), 'not-an-id'] as $value) {
            $this->withHeader('X-Account-Id', $value)->getJson('/api/v2/me/context')->assertForbidden();
        }
        $this->withHeader('X-Account-Id', (string) $user->id)->getJson('/api/v2/communication/rules')->assertForbidden();
    }

    public function test_stale_bearer_view_is_refused_on_writes_before_validation_or_operation_replay(): void
    {
        $user = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->withToken($user->createToken('expected-account-test')->plainTextToken)->withHeader('X-Account-Id', (string) $this->schoolA->ids['farid_user'])
            ->postJson('/api/v2/communication/drafts', [])->assertForbidden()->assertJsonPath('data', null);
    }
}
