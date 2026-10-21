<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureStudentSessionIsActive;
use App\Models\ChatFile;
use App\Models\OnlineExamStudentAnswer;
use App\Models\PaymentTransaction;
use App\Models\Settings;
use App\Models\StudentSubject;
use App\Models\User;
use App\Services\OnlineExamScoringService;
use App\Services\ParentPolicyService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\RunsAppServer;
use Tests\TestCase;

/**
 * Student API checks. The API controllers exit() after responding, so the
 * endpoints are called on a separate `php -S` server (see RunsAppServer), with
 * the fixture committed to the database for it to see.
 */
class StudentApiSecurityTest extends TestCase
{
    use RunsAppServer;

    private const SECRETS = ['rzp_secret_value', 'rzp_webhook_secret_value', 'sk_stripe_secret_value', 'whsec_stripe_value', 'sk_paystack_secret_value', 'flw_secret_value', 'flw_hash_value'];

    /** @var array<string, int> */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->freshInstall();
        $this->seedSchool();
    }

    protected function tearDown(): void
    {
        $this->stopAppServer();

        parent::tearDown();
    }

    public function test_public_settings_do_not_include_payment_secrets(): void
    {
        $response = $this->api()->get('/api/settings', ['type' => 'app_settings']);

        $this->assertSame(200, $response->status());
        $this->assertSame('pk_public', $response->json('data.payment_options.stripe.stripe_publishable_key'));
        $this->assertArrayNotHasKey('stripe_secret_key', $response->json('data.payment_options.stripe'));
        $this->assertArrayNotHasKey('stripe_webhook_secret', $response->json('data.payment_options.stripe'));
        foreach (self::SECRETS as $secret) {
            $this->assertStringNotContainsString($secret, $response->body());
        }
    }

    /**
     * Razorpay, Paystack and Flutterwave were removed. A school that had them
     * switched on still has their settings rows: the apps must stop offering
     * them, and a payment through one must be refused like a disabled gateway.
     */
    public function test_removed_gateways_are_neither_offered_nor_accepted(): void
    {
        $options = $this->api()->get('/api/settings', ['type' => 'app_settings'])->json('data.payment_options');
        $this->assertArrayHasKey('stripe', $options);
        foreach (['razorpay', 'paystack', 'flutterwave'] as $gateway) {
            $this->assertArrayNotHasKey($gateway, $options);
        }

        $payment = ['type_of_fee' => 2, 'is_fully_paid' => 0, 'amount' => '200.00', 'optional_fees_data' => [['id' => $this->ids['fee_sports'], 'amount' => '200.00']]];
        foreach ([1, 3, 4] as $method) {
            foreach ([
                [$this->api($this->token('student')), '/api/student/add-fees-transaction', []],
                [$this->api($this->token('parent')), '/api/parent/add-fees-transaction', ['child_id' => $this->ids['student']]],
            ] as [$api, $url, $extra]) {
                $response = $api->asForm()->post($url, ['payment_method' => $method] + $extra + $payment);
                $this->assertTrue($response->json('error'), "$url accepted payment method $method");
                $this->assertSame('The selected payment method is not available.', $response->json('message'));
            }
        }
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_exam_answers_are_not_sent_and_picking_every_option_scores_nothing(): void
    {
        $api = $this->api($this->token('student'));

        $list = $api->get('/api/student/get-online-exam-list')->json('data.data');
        $this->assertSame([$this->ids['exam']], array_column($list, 'exam_id'));
        $this->assertArrayNotHasKey('exam_key', $list[0]);

        $this->assertTrue($api->get('/api/student/get-online-exam-questions', ['exam_id' => $this->ids['exam'], 'exam_key' => '9999'])->json('error'));

        $questions = $api->get('/api/student/get-online-exam-questions', ['exam_id' => $this->ids['exam'], 'exam_key' => '4321']);
        $this->assertFalse($questions->json('error'), $questions->body());
        $multi = collect($questions->json('data'))->firstWhere('id', $this->ids['choice_multi']);
        $this->assertSame(2, $multi['answers_count']);
        $this->assertSame([['id' => 0, 'option_id' => 0, 'answer' => ''], ['id' => 0, 'option_id' => 0, 'answer' => '']], $multi['answers']);
        $this->assertCount(3, $multi['options']);

        // An option of another question is refused, and nothing is stored.
        $wrong = $api->asForm()->post('/api/student/submit-online-exam-answers', [
            'online_exam_id' => $this->ids['exam'],
            'answers_data' => [['question_id' => $this->ids['choice_multi'], 'option_id' => [$this->ids['single_right']]]],
        ]);
        $this->assertTrue($wrong->json('error'));
        $this->assertSame(0, OnlineExamStudentAnswer::count());

        // Every option of the two-answer question, the right one of the other; 0 = unanswered.
        $submitted = $api->asForm()->post('/api/student/submit-online-exam-answers', [
            'online_exam_id' => $this->ids['exam'],
            'answers_data' => [
                ['question_id' => $this->ids['choice_multi'], 'option_id' => [$this->ids['multi_a'], $this->ids['multi_b'], $this->ids['multi_wrong']]],
                ['question_id' => $this->ids['choice_single'], 'option_id' => [$this->ids['single_right'], 0]],
            ],
        ]);
        $this->assertFalse($submitted->json('error'), $submitted->body());

        $result = $api->get('/api/student/get-online-exam-result', ['online_exam_id' => $this->ids['exam']])->json('data');
        $this->assertEquals(3, $result['total_obtained_marks']);
        $this->assertEquals(5, $result['total_marks']);
        $this->assertSame(1, $result['correct_answers']['total_questions']);
        $this->assertSame([['question_id' => $this->ids['choice_single'], 'marks' => 3]], $result['correct_answers']['question_data']);
        $this->assertSame(1, $result['in_correct_answers']['total_questions']);

        // The other places that show the score agree.
        $listed = $api->get('/api/student/get-online-exam-result-list')->json('data.data');
        $this->assertEquals(3, $listed[0]['obtained_marks']);
        $report = $api->get('/api/student/online-exam-report', ['session_year_id' => $this->ids['session_year']])->json('data');
        $this->assertEquals(60, $report['average_percentage']);

        $parent = $this->api($this->token('parent'));
        $query = ['child_id' => $this->ids['student'], 'online_exam_id' => $this->ids['exam']];
        $this->assertEquals(3, $parent->get('/api/parent/get-online-exam-result', $query)->json('data.total_obtained_marks'));
        $this->assertSame(1, $parent->get('/api/parent/get-online-exam-result', $query)->json('data.in_correct_answers.total_questions'));
        $this->assertEquals(3, $parent->get('/api/parent/get-online-exam-result-list', ['child_id' => $this->ids['student']])->json('data.data.0.obtained_marks'));

        $again = $api->asForm()->post('/api/student/submit-online-exam-answers', [
            'online_exam_id' => $this->ids['exam'],
            'answers_data' => [['question_id' => $this->ids['choice_single'], 'option_id' => [$this->ids['single_right']]]],
        ]);
        $this->assertTrue($again->json('error'));
        $this->assertSame(4, OnlineExamStudentAnswer::count());
    }

    public function test_another_class_exam_cannot_be_taken(): void
    {
        $api = $this->api($this->token('student'));

        $this->assertTrue($api->get('/api/student/get-online-exam-questions', ['exam_id' => $this->ids['other_exam'], 'exam_key' => '1111'])->json('error'));
        $this->assertTrue($api->asForm()->post('/api/student/submit-online-exam-answers', [
            'online_exam_id' => $this->ids['other_exam'],
            'answers_data' => [['question_id' => $this->ids['other_choice'], 'option_id' => [$this->ids['other_option']]]],
        ])->json('error'));
        $this->assertSame(0, OnlineExamStudentAnswer::count());
    }

    public function test_scoring_needs_exactly_the_correct_options(): void
    {
        $this->assertTrue(OnlineExamScoringService::isCorrect([2, 1], [1, 2]));
        $this->assertFalse(OnlineExamScoringService::isCorrect([1, 2, 3], [1, 2]));
        $this->assertFalse(OnlineExamScoringService::isCorrect([1], [1, 2]));
        $this->assertFalse(OnlineExamScoringService::isCorrect([], []));

        $this->expectException(ValidationException::class);
        app(OnlineExamScoringService::class)->validateSubmission($this->ids['exam'], [
            ['question_id' => $this->ids['choice_single'], 'option_id' => [$this->ids['single_right']]],
            ['question_id' => $this->ids['choice_single'], 'option_id' => [$this->ids['single_right']]],
        ]);
    }

    public function test_student_api_is_only_for_active_student_accounts(): void
    {
        $this->assertSame(403, $this->api($this->token('teacher'))->get('/api/student/subjects')->status());

        $student = User::findOrFail($this->ids['student_user']);
        $token = $student->createToken('test')->plainTextToken;
        $student->forceFill(['status' => 0])->save();
        $this->assertSame(403, $this->api($token)->get('/api/student/subjects')->status());

        // Logging in doesn't hand out a token to a deactivated account either.
        PersonalAccessToken::query()->delete();
        $login = $this->api()->asForm()->post('/api/student/login', ['gr_number' => 'GR1', 'password' => 'secret123']);
        $this->assertTrue($login->json('error'));
        $this->assertNull($login->json('token'));
        $this->assertSame(0, PersonalAccessToken::count());

        // The middleware on its own, for an active student.
        $student->forceFill(['status' => 1])->save();
        $request = Request::create('/api/student/subjects');
        $request->setUserResolver(fn () => $student->fresh());
        $this->assertSame('ok', (new EnsureStudentSessionIsActive)->handle($request, fn () => response('ok'))->getContent());
    }

    public function test_forgot_password_answers_the_same_whether_or_not_the_account_exists(): void
    {
        $api = $this->api();

        $known = $api->asForm()->post('/api/student/forgot-password', ['gr_no' => 'GR1', 'dob' => '2012-05-01'])->json();
        $unknown = $api->asForm()->post('/api/student/forgot-password', ['gr_no' => 'NOPE', 'dob' => '2012-05-01'])->json();
        $this->assertFalse($known['error']);
        $this->assertSame($known, $unknown);
        $this->assertSame(1, (int) User::findOrFail($this->ids['student_user'])->reset_request);

        // (Mail isn't set up on the test server, so sending fails for the real account: still the same answer.)
        $known = $api->asForm()->post('/api/forgot-password', ['email' => 'teacher@example.com'])->json();
        $unknown = $api->asForm()->post('/api/forgot-password', ['email' => 'nobody@example.com'])->json();
        $this->assertFalse($known['error'], json_encode($known));
        $this->assertSame($known, $unknown);
    }

    public function test_changing_password_signs_out_other_devices(): void
    {
        $student = User::findOrFail($this->ids['student_user']);
        $current = $student->createToken('this device')->plainTextToken;
        $other = $student->createToken('other device')->plainTextToken;

        $response = $this->api($current)->asForm()->post('/api/change-password', [
            'current_password' => 'secret123', 'new_password' => 'new-secret-123', 'new_confirm_password' => 'new-secret-123',
        ]);
        $this->assertFalse($response->json('error'), $response->body());

        $this->assertNotNull(PersonalAccessToken::findToken($current));
        $this->assertNull(PersonalAccessToken::findToken($other));
    }

    public function test_chat_messages_only_go_to_the_students_teachers_and_files_get_safe_names(): void
    {
        $api = $this->api($this->token('student'));

        $listed = array_column($api->get('/api/student/get-user-list')->json('data.items'), 'user_id');
        $this->assertSame([$this->ids['teacher_user']], $listed);

        foreach ([$this->ids['other_teacher_user'], $this->ids['admin_user']] as $receiver) {
            $this->assertTrue($api->asForm()->post('/api/student/send-message', ['receiver_id' => $receiver, 'message' => 'hi'])->json('error'));
        }
        $this->assertSame(0, DB::table('chat_messages')->count());

        $sent = $this->api($this->token('student'))->attach('file[]', 'my notes', '../notes v1.txt')
            ->post('/api/student/send-message', ['receiver_id' => $this->ids['teacher_user'], 'message' => 'hello']);
        $this->assertFalse($sent->json('error'), $sent->body());

        $stored = ChatFile::sole()->file_name;
        $this->assertMatchesRegularExpression('#^chatfile/[A-Za-z0-9]{32}/notes_v1\.txt$#', $stored);
        $this->assertFileExists($this->appServerStoragePath('app/public/'.$stored));
    }

    public function test_fee_payments_use_server_amounts_and_own_transactions(): void
    {
        $api = $this->api($this->token('student'));

        $response = $api->asForm()->post('/api/student/add-fees-transaction', [
            'payment_method' => 2, 'type_of_fee' => 2, 'is_fully_paid' => 1, 'amount' => '1.00',
            'optional_fees_data' => [['id' => $this->ids['fee_sports'], 'amount' => '1.00']],
        ]);
        $this->assertTrue($response->json('error'));
        $this->assertStringContainsString('amount', $response->json('message'));

        // The parent app's endpoint uses the same server-side pricing.
        $response = $this->api($this->token('parent'))->asForm()->post('/api/parent/add-fees-transaction', [
            'child_id' => $this->ids['student'], 'payment_method' => 2, 'type_of_fee' => 2, 'is_fully_paid' => 1, 'amount' => '1.00',
            'optional_fees_data' => [['id' => $this->ids['fee_sports'], 'amount' => '1.00']],
        ]);
        $this->assertTrue($response->json('error'));
        $this->assertStringContainsString('amount', $response->json('message'));
        $this->assertSame(0, PaymentTransaction::count());

        // Another student's pending transaction can't be touched.
        $theirs = PaymentTransaction::forceCreate([
            'student_id' => $this->ids['other_student'], 'class_id' => $this->ids['class'], 'parent_id' => $this->ids['parent'],
            'mode' => 2, 'type_of_fee' => 2, 'payment_gateway' => 1, 'order_id' => 'order_x', 'payment_status' => 2,
            'total_amount' => 200, 'date' => now(), 'session_year_id' => $this->ids['session_year'],
        ]);
        $this->assertTrue($api->asForm()->post('/api/student/store-fees', ['transaction_id' => $theirs->id, 'payment_id' => 'pay_fake'])->json('error'));
        $this->assertTrue($api->asForm()->post('/api/student/fail-payment-transaction', ['payment_transaction_id' => $theirs->id])->json('error'));
        $theirs->refresh();
        $this->assertNull($theirs->payment_id);
        $this->assertSame(2, (int) $theirs->payment_status);
    }

    public function test_elective_subjects_must_come_from_the_class_groups(): void
    {
        $api = $this->api($this->token('student'));
        $select = fn (array $subjectIds) => $api->asForm()->post('/api/student/select-subjects', [
            'subject_group' => [['id' => $this->ids['elective_group'], 'subject_id' => $subjectIds]],
        ]);

        $this->assertTrue($select([$this->ids['subject_other_class']])->json('error'));
        $this->assertTrue($select([$this->ids['subject_art'], $this->ids['subject_music']])->json('error'));
        $this->assertFalse($select([$this->ids['subject_art']])->json('error'));
        $this->assertTrue($select([$this->ids['subject_music']])->json('error'));

        $this->assertSame([$this->ids['subject_art']], StudentSubject::pluck('subject_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_other_class_sections_content_is_not_served(): void
    {
        $api = $this->api($this->token('student'));

        $this->assertSame([], $api->get('/api/student/lesson-topics', ['lesson_id' => $this->ids['other_lesson']])->json('data'));
        $this->assertCount(1, $api->get('/api/student/lesson-topics', ['lesson_id' => $this->ids['lesson']])->json('data'));

        $this->assertTrue($api->asForm()->post('/api/student/submit-assignment', ['assignment_id' => $this->ids['other_assignment'], 'text_submission' => 'x'])->json('error'));
        $this->assertSame(0, DB::table('assignment_submissions')->count());

        $titles = array_column($api->get('/api/student/announcements')->json('data.data'), 'title');
        sort($titles);
        $this->assertSame(['For my class', 'Notice'], $titles);
    }

    // ---- Helpers ----------------------------------------------------------------------------

    private function api(?string $token = null): PendingRequest
    {
        if (! self::appServerRunning()) {
            $this->startAppServer(['SESSION_DRIVER' => 'array']);
        }

        return $this->apiClient($token ?: null);
    }

    private function token(string $who): string
    {
        return User::findOrFail($this->ids[$who.'_user'])->createToken('test')->plainTextToken;
    }

    private function seedSchool(): void
    {
        $now = now();
        $sessionYear = $this->ids['session_year'] = DB::table('session_years')->insertGetId([
            'name' => 'Current', 'start_date' => $now->copy()->subMonths(2)->toDateString(), 'end_date' => $now->copy()->addMonths(8)->toDateString(),
            'fee_due_date' => $now->copy()->addDays(30)->toDateString(), 'fee_due_charges' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        // Razorpay, Paystack and Flutterwave rows as an install that used them still has.
        $settings = [
            'session_year' => $sessionYear, 'currency_code' => 'ZAR', 'currency_symbol' => 'R',
            'razorpay_status' => 1, 'razorpay_api_key' => 'rzp_public_key', 'razorpay_secret_key' => self::SECRETS[0], 'razorpay_webhook_secret' => self::SECRETS[1],
            'stripe_status' => 1, 'stripe_publishable_key' => 'pk_public', 'stripe_secret_key' => self::SECRETS[2], 'stripe_webhook_secret' => self::SECRETS[3],
            'paystack_status' => 1, 'paystack_public_key' => 'pk_paystack', 'paystack_secret_key' => self::SECRETS[4],
            'flutterwave_status' => 1, 'flutterwave_public_key' => 'flw_public', 'flutterwave_secret_key' => self::SECRETS[5], 'flutterwave_hash_key' => self::SECRETS[6],
        ];
        foreach ($settings as $type => $message) {
            Settings::updateOrCreate(['type' => $type], ['message' => $message]);
        }

        $medium = DB::table('mediums')->insertGetId(['name' => 'English']);
        $class = $this->ids['class'] = DB::table('classes')->insertGetId(['name' => 'Grade 5', 'medium_id' => $medium]);
        $otherClass = DB::table('classes')->insertGetId(['name' => 'Grade 6', 'medium_id' => $medium]);
        $sectionA = DB::table('sections')->insertGetId(['name' => 'A']);
        $sectionB = DB::table('sections')->insertGetId(['name' => 'B']);
        $mySection = DB::table('class_sections')->insertGetId(['class_id' => $class, 'section_id' => $sectionA]);
        $otherSection = DB::table('class_sections')->insertGetId(['class_id' => $class, 'section_id' => $sectionB]);
        $category = DB::table('categories')->insertGetId(['name' => 'General']);

        $parentUser = $this->user('parent@example.com', 'Parent');
        ParentPolicyService::acceptCurrent($parentUser);
        $this->ids['parent_user'] = $parentUser->id;
        $this->ids['parent'] = DB::table('parents')->insertGetId([
            'user_id' => $parentUser->id, 'first_name' => 'Pat', 'last_name' => 'Parent', 'gender' => 'Male',
            'email' => 'parent@example.com', 'mobile' => '0000000000', 'occupation' => 'None',
        ]);
        [$this->ids['student_user'], $this->ids['student']] = $this->student('GR1', $mySection, $category);
        [, $this->ids['other_student']] = $this->student('GR2', $otherSection, $category);
        $this->ids['admin_user'] = $this->superAdmin()->id;

        // Subjects: Maths (core), Art/Music (pick one), Science (another class's core).
        foreach (['maths' => 'Theory', 'art' => 'Practical', 'music' => 'Practical', 'other_class' => 'Theory'] as $name => $type) {
            $this->ids['subject_'.$name] = DB::table('subjects')->insertGetId(['name' => ucfirst($name), 'bg_color' => '#fff', 'image' => 'x.png', 'medium_id' => $medium, 'type' => $type]);
        }
        DB::table('class_subjects')->insert(['session_year_id' => $sessionYear, 'class_id' => $class, 'type' => 'Compulsory', 'subject_id' => $this->ids['subject_maths']]);
        DB::table('class_subjects')->insert(['session_year_id' => $sessionYear, 'class_id' => $otherClass, 'type' => 'Compulsory', 'subject_id' => $this->ids['subject_other_class']]);
        $group = $this->ids['elective_group'] = DB::table('elective_subject_groups')->insertGetId(['session_year_id' => $sessionYear, 'total_subjects' => 2, 'total_selectable_subjects' => 1, 'class_id' => $class]);
        foreach (['art', 'music'] as $name) {
            DB::table('class_subjects')->insert(['session_year_id' => $sessionYear, 'class_id' => $class, 'type' => 'Elective', 'subject_id' => $this->ids['subject_'.$name], 'elective_subject_group_id' => $group]);
        }

        // Teachers: one teaches the student Maths, one only the other section.
        foreach (['teacher' => $mySection, 'other_teacher' => $otherSection] as $who => $section) {
            $user = $this->user($who.'@example.com', 'Teacher');
            $this->ids[$who.'_user'] = $user->id;
            $teacher = DB::table('teachers')->insertGetId(['user_id' => $user->id, 'qualification' => 'BEd']);
            DB::table('subject_teachers')->insert(['session_year_id' => $sessionYear, 'class_section_id' => $section, 'subject_id' => $this->ids['subject_maths'], 'teacher_id' => $teacher]);
        }

        // Online exams: one for the student's section, one for the other section.
        $this->ids['exam'] = $this->exam($mySection, '4321', $sessionYear);
        $question = $this->question($class, 'Pick the even numbers', ['multi_a' => true, 'multi_b' => true, 'multi_wrong' => false]);
        $this->ids['choice_multi'] = DB::table('online_exam_question_choices')->insertGetId(['online_exam_id' => $this->ids['exam'], 'question_id' => $question, 'marks' => 2]);
        $question = $this->question($class, 'Pick the prime', ['single_right' => true, 'single_wrong' => false]);
        $this->ids['choice_single'] = DB::table('online_exam_question_choices')->insertGetId(['online_exam_id' => $this->ids['exam'], 'question_id' => $question, 'marks' => 3]);

        $this->ids['other_exam'] = $this->exam($otherSection, '1111', $sessionYear);
        $question = $this->question($class, 'Other exam', ['other_option' => true]);
        $this->ids['other_choice'] = DB::table('online_exam_question_choices')->insertGetId(['online_exam_id' => $this->ids['other_exam'], 'question_id' => $question, 'marks' => 1]);

        // Fees: an optional fee for the class.
        $this->ids['fee_sports'] = DB::table('fees_types')->insertGetId(['name' => 'Sports']);
        DB::table('fees_classes')->insert(['session_year_id' => $sessionYear, 'class_id' => $class, 'fees_type_id' => $this->ids['fee_sports'], 'choiceable' => 1, 'amount' => 200]);

        // Lessons, assignments and announcements of each section.
        foreach (['lesson' => $mySection, 'other_lesson' => $otherSection] as $key => $section) {
            $this->ids[$key] = DB::table('lessons')->insertGetId(['name' => $key, 'class_section_id' => $section, 'subject_id' => $this->ids['subject_maths']]);
            DB::table('lesson_topics')->insert(['lesson_id' => $this->ids[$key], 'name' => $key.' topic']);
        }
        $this->ids['other_assignment'] = DB::table('assignments')->insertGetId([
            'class_section_id' => $otherSection, 'subject_id' => $this->ids['subject_maths'], 'name' => 'Homework',
            'due_date' => $now->copy()->addDays(3), 'session_year_id' => $sessionYear,
        ]);
        foreach ([['Notice', '', null], ['For my class', 'App\Models\ClassSchool', $class], ['For another class', 'App\Models\ClassSchool', $otherClass], ['Other section', 'App\Models\ClassSection', $otherSection]] as [$title, $type, $id]) {
            DB::table('announcements')->insert(['title' => $title, 'description' => $title, 'table_type' => $type, 'table_id' => $id, 'session_year_id' => $sessionYear, 'created_at' => $now]);
        }
    }

    /** @return array{int, int} user id, student id */
    private function student(string $grNumber, int $classSectionId, int $categoryId): array
    {
        $user = $this->user($grNumber, 'Student');
        $user->forceFill(['dob' => '2012-05-01'])->save();
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $user->id, 'class_section_id' => $classSectionId, 'category_id' => $categoryId, 'admission_no' => $grNumber,
            'admission_date' => now()->toDateString(), 'father_id' => $this->ids['parent'],
        ]);
        DB::table('student_sessions')->insert(['student_id' => $studentId, 'class_section_id' => $classSectionId, 'session_year_id' => $this->ids['session_year'], 'status' => 1]);

        return [$user->id, $studentId];
    }

    private function user(string $email, string $role): User
    {
        $user = User::forceCreate([
            'first_name' => 'Test', 'last_name' => $role, 'email' => $email, 'password' => Hash::make('secret123'),
            'gender' => 'Male', 'mobile' => '0000000000', 'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function exam(int $classSectionId, string $key, int $sessionYear): int
    {
        return DB::table('online_exams')->insertGetId([
            'model_type' => 'App\Models\ClassSection', 'model_id' => $classSectionId, 'subject_id' => $this->ids['subject_maths'],
            'title' => 'Quiz', 'exam_key' => $key, 'duration' => 30, 'session_year_id' => $sessionYear,
            'start_date' => now()->subHour(), 'end_date' => now()->addHour(),
        ]);
    }

    /** @param array<string, bool> $options name => is correct */
    private function question(int $classId, string $text, array $options): int
    {
        $classSubject = DB::table('class_subjects')->where('class_id', $classId)->value('id');
        $question = DB::table('online_exam_questions')->insertGetId(['class_subject_id' => $classSubject, 'question_type' => 0, 'question' => $text]);
        foreach ($options as $name => $correct) {
            $this->ids[$name] = DB::table('online_exam_question_options')->insertGetId(['question_id' => $question, 'option' => $name]);
            if ($correct) {
                DB::table('online_exam_question_answers')->insert(['question_id' => $question, 'answer' => $this->ids[$name]]);
            }
        }

        return $question;
    }
}
