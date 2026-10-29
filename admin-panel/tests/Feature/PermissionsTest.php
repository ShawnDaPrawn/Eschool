<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\RunsAppServer;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RunsAppServer;

    /** Admin actions a teacher has no permission for. */
    private const ADMIN_ONLY = [
        ['get', 'subject'],
        ['post', 'subject'],
        ['delete', 'subject/1'],
        ['get', 'sliders'],
        ['post', 'sliders'],
        ['delete', 'sliders/1'],
        ['get', 'email-tamplate'],
        ['post', 'email/store'],
        ['delete', 'permanent-delete/1'],
        ['post', 'update-active-status'],
        ['get', 'staff-attendance/list'],
        ['post', 'staff-attendance/store'],
        ['post', 'session-years'],
        ['post', 'exams/publish/1'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->freshInstall();
        $this->startAppServer();
    }

    protected function tearDown(): void
    {
        $this->stopAppServer();
        parent::tearDown();
    }

    public function test_teachers_cannot_use_admin_only_actions(): void
    {
        $teacher = User::create([
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'teacher.test@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);
        $teacher->assignRole('Teacher');
        Staff::forceCreate(['user_id' => $teacher->id]);
        Teacher::forceCreate(['user_id' => $teacher->id]);
        $client = $this->adminPanelClient('teacher.test@example.com', 'secret123');

        $allowed = [];
        foreach (self::ADMIN_ONLY as [$method, $uri]) {
            $response = $client->$method('/'.$uri);
            $redirectedHome = $response->status() === 302
                && str_ends_with((string) $response->header('Location'), '/home');
            $refused = $response->status() === 403 || ($response->json('error') === true
                && $response->json('message') === trans('no_permission_message'));
            if (! $redirectedHome && ! $refused) {
                $allowed[] = strtoupper($method)." $uri ({$response->status()})";
            }
            if ($redirectedHome) {
                // The redirect used to be followed by the page itself.
                $this->assertLessThan(2000, strlen($response->body()), "$uri sent its page after the redirect");
            }
        }

        $this->assertSame([], $allowed, 'A teacher was allowed to use admin-only actions');
    }

    public function test_settings_api_does_not_expose_the_gateway_secrets(): void
    {
        DB::table('settings')->insert([
            ['type' => 'currency_code', 'message' => 'ZAR'],
            ['type' => 'stripe_status', 'message' => '1'],
            ['type' => 'stripe_publishable_key', 'message' => 'pk_test_key'],
            ['type' => 'stripe_secret_key', 'message' => 'sk-secret-key'],
            ['type' => 'stripe_webhook_secret', 'message' => 'webhook-signing-secret'],
        ]);

        $response = $this->appClient()->get('/api/settings', ['type' => 'app_settings']);

        $this->assertNull($response->json('data.payment_options.stripe'));
        $this->assertSame('0', $response->json('data.online_payment'));
        $this->assertStringNotContainsString('sk-secret-key', $response->body());
        $this->assertStringNotContainsString('webhook-signing-secret', $response->body());
    }
}
