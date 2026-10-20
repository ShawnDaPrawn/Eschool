<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
        // Like a real install, the Flutterwave secret comes from an env file,
        // which Laravel also copies into $_SERVER. With APP_ENV set, Laravel
        // loads .env.{APP_ENV} instead of .env.
        File::put(base_path('.env.webhook-test'), "FLW_SECRET_HASH=flutterwave-secret\n");
        $this->freshInstall();
        $this->startAppServer(['APP_ENV' => 'webhook-test']);
    }

    protected function tearDown(): void
    {
        $this->stopAppServer();
        File::delete(base_path('.env.webhook-test'));
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

    public function test_settings_api_does_not_expose_the_razorpay_webhook_secret(): void
    {
        DB::table('settings')->insert([
            ['type' => 'currency_code', 'message' => 'ZAR'],
            ['type' => 'razorpay_status', 'message' => '1'],
            ['type' => 'razorpay_api_key', 'message' => 'rzp_test_key'],
            ['type' => 'razorpay_webhook_secret', 'message' => 'webhook-signing-secret'],
        ]);

        $response = $this->appClient()->get('/api/settings', ['type' => 'app_settings']);

        $this->assertSame('rzp_test_key', $response->json('data.payment_options.razorpay.razorpay_api_key'));
        $this->assertStringNotContainsString('webhook-signing-secret', $response->body());
    }

    public function test_flutterwave_webhook_needs_the_secret_hash(): void
    {
        $transactionId = DB::table('payment_transactions')->insertGetId([
            'student_id' => 1, 'class_id' => 1, 'parent_id' => 1, 'session_year_id' => 1,
            'payment_status' => 2, 'total_amount' => 100,
        ]);
        $payload = [
            'data' => ['id' => 123, 'status' => 'successful'],
            'meta_data' => [
                'payment_transaction_id' => $transactionId, 'student_id' => 1, 'class_id' => 1,
                'parent_id' => 1, 'session_year_id' => 1, 'is_fully_paid' => 1, 'type_of_fee' => 0,
                'optional_fees_paid' => '[]', 'installment_fees_paid' => '[]',
            ],
        ];

        $this->appClient()->post('/webhook/flutterwave', $payload);
        $this->appClient()->withHeaders(['verif-hash' => 'wrong'])->post('/webhook/flutterwave', $payload);

        $this->assertSame(2, (int) DB::table('payment_transactions')->where('id', $transactionId)->value('payment_status'));
    }
}
