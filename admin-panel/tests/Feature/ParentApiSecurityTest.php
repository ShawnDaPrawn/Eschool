<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ParentPolicyService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Concerns\RunsAppServer;
use Tests\TestCase;

/**
 * Two families: a mother (family A) and a father (family B), each with a child in
 * a different class section, each section with its own class teacher. The mother
 * must not reach family B's records through the parent API, and teacher payloads
 * must not carry the date of birth (it is the default teacher password).
 *
 * The API is served by a separate `php -S` process, as in AdminPagesTest: the API
 * controllers exit() after sending their response, which would end the test run.
 */
class ParentApiSecurityTest extends TestCase
{
    use RunsAppServer;

    /** @var array<string, int> */
    private array $ids = [];

    private string $motherToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freshInstall();
        $this->createFamilies();
        $this->startAppServer();
    }

    protected function tearDown(): void
    {
        $this->stopAppServer();

        parent::tearDown();
    }

    public function test_fee_receipt_and_payment_endpoints_refuse_another_familys_records(): void
    {
        $api = $this->motherApi();

        $receipt = $api->get('/api/parent/fees-paid-receipt-pdf', ['fees_paid_id' => $this->ids['fees_paid_b']]);
        $this->assertApiError($receipt);
        $this->assertArrayNotHasKey('pdf', $receipt->json() ?? []);

        $fail = $api->post('/api/parent/fail-payment-transaction', ['payment_transaction_id' => $this->ids['transaction_b']]);
        $this->assertApiError($fail);
        $this->assertSame(2, $this->paymentStatus('transaction_b'), "Another family's transaction was marked failed");

        $store = $api->post('/api/parent/store-fees', [
            'child_id' => $this->ids['child_a'],
            'transaction_id' => $this->ids['transaction_b'],
            'payment_id' => 'pay_forged',
        ]);
        $this->assertApiError($store);
        $this->assertNull(DB::table('payment_transactions')->where('id', $this->ids['transaction_b'])->value('payment_id'));

        $status = $api->post('/api/parent/get-payment-status', ['payment_intent_id' => 'pi_familyB']);
        $this->assertApiError($status);

        $malformed = $api->post('/api/parent/get-payment-status', ['payment_intent_id' => '../../v1/customers']);
        $this->assertApiError($malformed);

        // The same calls still work on the mother's own records.
        $ownFail = $api->post('/api/parent/fail-payment-transaction', ['payment_transaction_id' => $this->ids['transaction_a']]);
        $this->assertApiSuccess($ownFail);
        $this->assertSame(0, $this->paymentStatus('transaction_a'));

        // A transaction that is no longer pending can't be failed again (or overwritten).
        $settled = $api->post('/api/parent/fail-payment-transaction', ['payment_transaction_id' => $this->ids['transaction_a_paid']]);
        $this->assertApiError($settled);
        $this->assertSame(1, $this->paymentStatus('transaction_a_paid'));
    }

    public function test_teacher_payloads_have_no_date_of_birth(): void
    {
        $api = $this->motherApi();

        $teachers = $api->get('/api/parent/teachers', ['child_id' => $this->ids['child_a']]);
        $this->assertApiSuccess($teachers);
        $this->assertSame(['Alice'], array_column($teachers->json('data'), 'first_name'), $teachers->body());
        foreach ($teachers->json('data') as $teacher) {
            $this->assertArrayNotHasKey('dob', $teacher);
            $this->assertArrayHasKey('email', $teacher);
            $this->assertArrayHasKey('mobile_no', $teacher);
        }
        $this->assertStringNotContainsString('1985-04-12', $teachers->body());

        $chatUsers = $api->get('/api/parent/get-user-list', ['offset' => 0, 'limit' => 10]);
        $this->assertApiSuccess($chatUsers);
        $this->assertSame(['Alice'], array_column($chatUsers->json('data.items'), 'first_name'), $chatUsers->body());
        foreach ($chatUsers->json('data.items') as $teacher) {
            $this->assertArrayNotHasKey('dob', $teacher);
        }
        $this->assertStringNotContainsString('1985-04-12', $chatUsers->body());
    }

    public function test_child_endpoints_refuse_another_familys_child(): void
    {
        $api = $this->motherApi();

        // Outside the CheckChild route group.
        $this->assertApiError($api->get('/api/parent/announcements', ['type' => 'class', 'child_id' => $this->ids['child_b']]));
        $this->assertApiError($api->get('/api/parent/fees-transactions-list', ['child_id' => $this->ids['child_b']]));
        // Inside it.
        $this->assertApiError($api->get('/api/parent/teachers', ['child_id' => $this->ids['child_b']]));
        $this->assertApiError($api->get('/api/parent/fees-paid-list', ['child_id' => $this->ids['child_b']]));

        // Own child, but another class section's lesson: no topics.
        $own = $api->get('/api/parent/lesson-topics', ['child_id' => $this->ids['child_a'], 'lesson_id' => $this->ids['lesson_a']]);
        $this->assertApiSuccess($own);
        $this->assertSame(['Topic A'], array_column($own->json('data'), 'name'));

        $other = $api->get('/api/parent/lesson-topics', ['child_id' => $this->ids['child_a'], 'lesson_id' => $this->ids['lesson_b']]);
        $this->assertApiSuccess($other);
        $this->assertSame([], $other->json('data'));
    }

    public function test_chat_messages_only_go_to_the_childs_teachers(): void
    {
        $api = $this->motherApi();

        $this->assertApiError($api->post('/api/parent/send-message', ['receiver_id' => $this->ids['teacher_b_user'], 'message' => 'hello']));
        $this->assertApiError($api->post('/api/parent/send-message', ['receiver_id' => $this->ids['father_b_user'], 'message' => 'hello']));
        $this->assertSame(0, DB::table('chat_messages')->count());

        $sent = $api->post('/api/parent/send-message', ['receiver_id' => $this->ids['teacher_a_user'], 'message' => 'hello']);
        $this->assertApiSuccess($sent);
        $this->assertSame(1, DB::table('chat_messages')->where('modal_id', $this->ids['teacher_a_user'])->count());

        // An attachment is stored in its own generated folder under its (cleaned) name,
        // so it can't overwrite another file.
        $withFile = $this->motherApi()->attach('file[]', 'notes', 'report final.pdf')
            ->post('/api/parent/send-message', ['receiver_id' => $this->ids['teacher_a_user'], 'message' => '']);
        $this->assertApiSuccess($withFile);
        $stored = (string) DB::table('chat_files')->value('file_name');
        $this->assertMatchesRegularExpression('#^chatfile/[A-Za-z0-9]{32}/report_final\.pdf$#', $stored);
        $this->assertFileExists($this->appServerStoragePath('app/public/'.$stored));
        $this->assertStringEndsWith('/'.$stored, (string) $withFile->json('data.files.0'));
    }

    public function test_only_own_pending_leaves_can_be_deleted(): void
    {
        $api = $this->motherApi();
        $approved = $this->leave('child_a_user', 1);
        $otherFamily = $this->leave('child_b_user', 0);
        $pending = $this->leave('child_a_user', 0);

        foreach ([$approved, $otherFamily] as $leaveId) {
            $this->assertApiError($api->post('/api/parent/delete-leave', ['child_id' => $this->ids['child_a'], 'leave_id' => $leaveId]));
            $this->assertNull(DB::table('leaves')->where('id', $leaveId)->value('deleted_at'));
        }

        $this->assertApiSuccess($api->post('/api/parent/delete-leave', ['child_id' => $this->ids['child_a'], 'leave_id' => $pending]));
        $this->assertNotNull(DB::table('leaves')->where('id', $pending)->value('deleted_at'));
    }

    private function createFamilies(): void
    {
        $now = now();
        $stamp = ['created_at' => $now, 'updated_at' => $now];
        $session = (int) DB::table('settings')->where('type', 'session_year')->value('message');

        $medium = DB::table('mediums')->insertGetId(['name' => 'English'] + $stamp);
        $class = DB::table('classes')->insertGetId(['name' => 'Grade 1', 'medium_id' => $medium] + $stamp);
        $category = DB::table('categories')->insertGetId(['name' => 'General'] + $stamp);
        $sections = [];
        foreach (['a' => 'A', 'b' => 'B'] as $family => $name) {
            $section = DB::table('sections')->insertGetId(['name' => $name] + $stamp);
            $sections[$family] = DB::table('class_sections')->insertGetId(['class_id' => $class, 'section_id' => $section] + $stamp);
        }

        $parents = [
            'a' => ['email' => 'mother.a@example.com', 'column' => 'mother_id', 'gender' => 'Female', 'key' => 'mother_a'],
            'b' => ['email' => 'father.b@example.com', 'column' => 'father_id', 'gender' => 'Male', 'key' => 'father_b'],
        ];
        foreach ($parents as $family => $p) {
            $parentUser = $this->user($p['email'], 'Parent', ['gender' => $p['gender']]);
            ParentPolicyService::acceptCurrent($parentUser);
            $this->ids[$p['key'].'_user'] = $parentUser->id;
            $parentId = DB::table('parents')->insertGetId([
                'user_id' => $parentUser->id, 'first_name' => $parentUser->first_name, 'last_name' => 'Test',
                'gender' => $p['gender'], 'email' => $p['email'], 'mobile' => '0000000000', 'occupation' => 'Engineer',
            ] + $stamp);

            $childUser = $this->user("child.$family@example.com", 'Student');
            $this->ids["child_{$family}_user"] = $childUser->id;
            $childId = DB::table('students')->insertGetId([
                'user_id' => $childUser->id, 'class_id' => $class, 'class_section_id' => $sections[$family], 'category_id' => $category,
                'admission_no' => "ADM-$family", 'admission_date' => '2022-06-01', $p['column'] => $parentId,
            ] + $stamp);
            $this->ids["child_$family"] = $childId;
            DB::table('student_sessions')->insert([
                'student_id' => $childId, 'class_section_id' => $sections[$family], 'session_year_id' => $session, 'status' => 1,
            ] + $stamp);

            // Each section's class teacher; dob is the teacher's default password.
            $teacherUser = $this->user("teacher.$family@example.com", 'Teacher', [
                'first_name' => $family === 'a' ? 'Alice' : 'Bob', 'dob' => '1985-04-12',
            ]);
            $this->ids["teacher_{$family}_user"] = $teacherUser->id;
            $teacherId = DB::table('teachers')->insertGetId(['user_id' => $teacherUser->id, 'qualification' => 'B.Ed'] + $stamp);
            DB::table('class_teachers')->insert([
                'class_section_id' => $sections[$family], 'class_teacher_id' => $teacherId, 'session_year_id' => $session,
            ] + $stamp);

            $this->ids["fees_paid_$family"] = DB::table('fees_paids')->insertGetId([
                'parent_id' => $parentId, 'student_id' => $childId, 'class_id' => $class, 'mode' => 2,
                'total_amount' => 100, 'is_fully_paid' => 1, 'date' => '2022-07-01', 'session_year_id' => $session,
            ] + $stamp);
            $this->ids["transaction_$family"] = DB::table('payment_transactions')->insertGetId([
                'student_id' => $childId, 'class_id' => $class, 'parent_id' => $parentId, 'mode' => 2, 'payment_gateway' => 2,
                'order_id' => 'pi_family'.strtoupper($family), 'payment_status' => 2, 'total_amount' => 100,
                'date' => $now, 'session_year_id' => $session,
            ] + $stamp);

            $lesson = DB::table('lessons')->insertGetId(['name' => "Lesson $family", 'class_section_id' => $sections[$family], 'subject_id' => 1] + $stamp);
            $this->ids["lesson_$family"] = $lesson;
            DB::table('lesson_topics')->insert(['lesson_id' => $lesson, 'name' => 'Topic '.strtoupper($family)] + $stamp);
        }

        $this->ids['transaction_a_paid'] = DB::table('payment_transactions')->insertGetId([
            'student_id' => $this->ids['child_a'], 'class_id' => $class, 'parent_id' => 1, 'mode' => 2, 'payment_gateway' => 2,
            'order_id' => 'pi_familyApaid', 'payment_status' => 1, 'total_amount' => 100, 'date' => $now, 'session_year_id' => $session,
        ] + $stamp);

        $this->motherToken = User::findOrFail($this->ids['mother_a_user'])->createToken('test')->plainTextToken;
    }

    private function user(string $email, string $role, array $extra = []): User
    {
        $user = User::forceCreate(array_merge([
            'first_name' => Str::studly(Str::before($email, '.')),
            'last_name' => 'Test',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'gender' => 'Female',
            'mobile' => '0000000000',
        ], $extra));
        $user->assignRole($role);

        return $user;
    }

    private function leave(string $userKey, int $status): int
    {
        return DB::table('leaves')->insertGetId([
            'user_id' => $this->ids[$userKey], 'reason' => 'Unwell', 'from_date' => '2022-07-01', 'to_date' => '2022-07-01',
            'status' => (string) $status, 'session_year_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function paymentStatus(string $key): int
    {
        return (int) DB::table('payment_transactions')->where('id', $this->ids[$key])->value('payment_status');
    }

    private function motherApi(): PendingRequest
    {
        return $this->apiClient($this->motherToken);
    }

    private function assertApiError(Response $response): void
    {
        $this->assertSame(200, $response->status(), $response->body());
        $this->assertTrue($response->json('error'), 'Expected an error response: '.$response->body());
    }

    private function assertApiSuccess(Response $response): void
    {
        $this->assertSame(200, $response->status(), $response->body());
        $this->assertFalse($response->json('error'), 'Expected a success response: '.$response->body());
    }
}
