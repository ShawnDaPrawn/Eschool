<?php

namespace Tests\Support;

use App\Models\Settings;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A small but complete school, built on an installer-seeded database, so every
 * mobile-app endpoint has real data to return.
 *
 * Section A of Grade 5 is the main class: teacher Tara teaches Maths and
 * Science there and is its class teacher. Students Asha (father Farid and
 * mother Mona) and Ben (guardian Gina) are in it; Cara is in Grade 6 with
 * teacher Theo. Dates are relative to today, so derived states (upcoming,
 * ongoing, completed) are the same on every run.
 */
class DemoSchool
{
    /** @var array<string, int> */
    public array $ids = [];

    /** @var array<string, string> Sanctum tokens by person */
    public array $tokens = [];

    private Carbon $today;

    /** See build(). */
    private string $label = '';

    /** The school year the data is in. */
    private int $year = 1;

    /**
     * @param  string  $label  empty for the main school; for another school
     *                         (built inside CurrentSchool::run()) a word every name, title and
     *                         sign-in of that school carries, so a leak is easy to spot
     */
    public static function build(string $label = ''): self
    {
        $school = new self;
        $school->label = $label;
        // "Today" for the school, whose timezone the app server runs in.
        $school->today = now(schoolSetting('time_zone', 'UTC'))->startOfDay();
        $school->sessionYear();
        $school->classes();
        $school->people();
        $school->timetable();
        $school->lessons();
        $school->assignments();
        $school->announcements();
        $school->attendance();
        $school->exams();
        $school->onlineExams();
        $school->calendar();
        $school->leaves();
        $school->fees();
        $school->gallery();
        $school->notifications();

        foreach (['tara', 'theo', 'asha', 'ben', 'cara', 'farid', 'mona', 'gina'] as $person) {
            $school->tokens[$person] = User::findOrFail($school->ids[$person.'_user'])->createToken('test')->plainTextToken;
        }

        return $school;
    }

    private function sessionYear(): void
    {
        $values = [
            'name' => $this->t('Current year'),
            'start_date' => $this->day(-120),
            'end_date' => $this->day(240),
            'free_app_use_date' => $this->day(-30),
            'fee_due_date' => $this->day(30),
            'fee_due_charges' => 5,
        ];
        if ($this->label === '') {
            DB::table('session_years')->where('id', 1)->update($values);
        } else {
            // Another school: its own year, made current there.
            $this->year = $this->insert('session_years', $values + ['default' => 1]);
            Settings::query()->updateOrCreate(['type' => 'session_year'], ['message' => (string) $this->year]);
        }
        $this->ids['session_year'] = $this->year;
        foreach ([
            'currency_code' => 'ZAR', 'currency_symbol' => 'R', 'compulsory_fee_payment_mode' => '1',
            'is_student_can_pay_fees' => '1', 'online_payment' => '1', 'school_address' => '1 Main Road',
            'mail_send_from' => 'office@example.com', 'about_us' => '<p>A friendly neighbourhood school.</p>',
        ] as $type => $message) {
            Settings::query()->updateOrCreate(['type' => $type], ['message' => $message]);
        }
        // The classes don't use terms, but the school has one running.
        $this->ids['term'] = $this->insert('terms', ['name' => $this->t('Term 1'), 'start_date' => $this->day(-120), 'end_date' => $this->day(60), 'session_year_id' => $this->year]);
    }

    private function classes(): void
    {
        $medium = $this->insert('mediums', ['name' => $this->t('English')]);
        $this->ids['category'] = $this->insert('categories', ['name' => $this->t('General'), 'status' => 1]);

        foreach (['maths' => [$this->t('Maths'), 'MAT', 'Theory'], 'science' => [$this->t('Science'), 'SCI', 'Practical']] as $key => [$name, $code, $type]) {
            $this->ids[$key] = $this->insert('subjects', [
                'name' => $name, 'code' => $code, 'bg_color' => '#5c6bc0', 'image' => "subjects/$key.png", 'medium_id' => $medium, 'type' => $type,
            ]);
        }

        foreach (['a' => $this->t('Grade 5'), 'c' => $this->t('Grade 6')] as $key => $className) {
            $class = $this->ids["class_$key"] = $this->insert('classes', ['name' => $className, 'medium_id' => $medium]);
            $section = $this->insert('sections', ['name' => strtoupper($key)]);
            $this->ids["section_$key"] = $this->insert('class_sections', ['class_id' => $class, 'section_id' => $section]);
            $this->insert('class_session_configs', ['class_id' => $class, 'session_year_id' => $this->year, 'include_terms' => 0]);
            foreach (['maths', 'science'] as $subject) {
                $this->ids["class_subject_{$key}_$subject"] = $this->insert('class_subjects', [
                    'class_id' => $class, 'subject_id' => $this->ids[$subject], 'type' => 'Compulsory', 'session_year_id' => $this->year,
                ]);
            }
        }
    }

    private function people(): void
    {
        foreach (['tara' => 'a', 'theo' => 'c'] as $name => $section) {
            $user = $this->user($name, 'Teacher', ['gender' => 'Female', 'dob' => '1990-04-12', 'current_address' => '2 School Lane']);
            $teacher = $this->ids["{$name}_teacher"] = $this->insert('teachers', ['user_id' => $user, 'qualification' => 'B.Ed']);
            $this->insert('staffs', ['user_id' => $user]);
            $this->insert('class_teachers', ['class_section_id' => $this->ids["section_$section"], 'class_teacher_id' => $teacher, 'session_year_id' => $this->year]);
            foreach (['maths', 'science'] as $subject) {
                $this->ids["subject_teacher_{$section}_$subject"] = $this->insert('subject_teachers', [
                    'class_section_id' => $this->ids["section_$section"], 'subject_id' => $this->ids[$subject], 'teacher_id' => $teacher, 'session_year_id' => $this->year,
                ]);
            }
        }

        foreach (['farid' => 'Male', 'mona' => 'Female', 'gina' => 'Female'] as $name => $gender) {
            $user = $this->user($name, 'Parent', ['gender' => $gender]);
            $this->ids[$name] = $this->insert('parents', [
                'user_id' => $user, 'first_name' => ucfirst($name), 'last_name' => $this->t('Parent'), 'gender' => $gender,
                'email' => $this->email($name), 'mobile' => '0123456789', 'occupation' => 'Engineer',
            ]);
        }

        foreach ([
            'asha' => ['a', 1, ['father_id' => $this->ids['farid'], 'mother_id' => $this->ids['mona']]],
            'ben' => ['a', 2, ['guardian_id' => $this->ids['gina']]],
            'cara' => ['c', 1, ['father_id' => $this->ids['farid']]],
        ] as $name => [$section, $roll, $parents]) {
            // Students sign in with their GR number, which the admin panel stores as their email.
            $user = $this->user($name, 'Student', ['dob' => '2015-06-01', 'email' => $this->grNumber($name)]);
            $student = $this->ids[$name] = $this->insert('students', $parents + [
                'user_id' => $user, 'class_id' => $this->ids["class_$section"], 'class_section_id' => $this->ids["section_$section"],
                'category_id' => $this->ids['category'], 'admission_no' => $this->grNumber($name), 'roll_number' => $roll,
                'admission_date' => $this->day(-200), 'blood_group' => 'O+',
            ]);
            $this->insert('student_sessions', ['student_id' => $student, 'class_section_id' => $this->ids["section_$section"], 'session_year_id' => $this->year, 'status' => 1]);
        }
    }

    private function timetable(): void
    {
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $index => $day) {
            foreach (['maths' => ['09:00:00', '09:45:00'], 'science' => ['10:00:00', '10:45:00']] as $subject => [$start, $end]) {
                $this->ids["timetable_$subject"] = $this->insert('timetables', [
                    'session_year_id' => $this->year, 'subject_teacher_id' => $this->ids["subject_teacher_a_$subject"], 'class_section_id' => $this->ids['section_a'],
                    'start_time' => $start, 'end_time' => $end, 'day' => $index + 1, 'day_name' => $day, 'note' => $this->t('Bring notebook'),
                ]);
            }
        }
    }

    private function lessons(): void
    {
        $lesson = $this->ids['lesson'] = $this->insert('lessons', [
            'name' => $this->t('Fractions'), 'description' => $this->t('Halves and quarters'), 'class_section_id' => $this->ids['section_a'], 'subject_id' => $this->ids['maths'],
        ]);
        $topic = $this->ids['topic'] = $this->insert('lesson_topics', ['lesson_id' => $lesson, 'name' => $this->t('Adding fractions'), 'description' => $this->t('Same denominators')]);

        $this->ids['lesson_file'] = $this->file('App\Models\Lesson', $lesson, 'Worksheet', '1', 'lessons/worksheet.pdf');
        $this->file('App\Models\Lesson', $lesson, 'Intro video', '2', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'lessons/thumb.png');
        $this->file('App\Models\LessonTopic', $topic, 'Practice', '4', 'https://example.com/practice');
    }

    private function assignments(): void
    {
        $this->ids['assignment'] = $this->insert('assignments', [
            'class_section_id' => $this->ids['section_a'], 'subject_id' => $this->ids['maths'], 'name' => $this->t('Fraction worksheet'),
            'instructions' => $this->t('Do questions 1-10'), 'due_date' => $this->day(7).' 17:00:00', 'points' => 10,
            'resubmission' => 1, 'extra_days_for_resubmission' => 2, 'session_year_id' => $this->year,
        ]);
        $this->file('App\Models\Assignment', $this->ids['assignment'], 'Questions', '1', 'assignment/questions.pdf');
        $this->ids['submission'] = $this->insert('assignment_submissions', [
            'assignment_id' => $this->ids['assignment'], 'student_id' => $this->ids['asha'], 'text_submission' => $this->t('My answers'),
            'session_year_id' => $this->year, 'status' => 0,
        ]);
        $this->file('App\Models\AssignmentSubmission', $this->ids['submission'], 'answers.pdf', '1', 'assignment/answers.pdf');
    }

    private function announcements(): void
    {
        foreach ([
            'notice' => [$this->t('School closes early on Friday'), '', null],
            'class_announcement' => [$this->t('Grade 5 trip'), 'App\Models\ClassSchool', $this->ids['class_a']],
            'subject_announcement' => [$this->t('Maths test next week'), 'App\Models\SubjectTeacher', $this->ids['subject_teacher_a_maths']],
        ] as $key => [$title, $type, $id]) {
            $this->ids[$key] = $this->insert('announcements', [
                'title' => $title, 'description' => "$title.", 'table_type' => $type, 'table_id' => $id, 'session_year_id' => $this->year,
            ]);
        }
        $this->file('App\Models\Announcement', $this->ids['subject_announcement'], 'syllabus.pdf', '1', 'announcement/syllabus.pdf');
    }

    private function attendance(): void
    {
        // Today included, so "this month" always has attendance.
        foreach ([-3 => 1, -2 => 0, -1 => 1, 0 => 1] as $offset => $type) {
            foreach (['asha', 'ben'] as $student) {
                $this->insert('attendances', [
                    'class_section_id' => $this->ids['section_a'], 'student_id' => $this->ids[$student], 'session_year_id' => $this->year,
                    'type' => $student === 'ben' ? 1 : $type, 'date' => $this->day($offset), 'remark' => '',
                ]);
            }
        }
    }

    private function exams(): void
    {
        foreach ([[0, 49, 'C'], [50, 79, 'B'], [80, 100, 'A']] as [$from, $to, $grade]) {
            $this->insert('grades', ['session_year_id' => $this->year, 'starting_range' => $from, 'ending_range' => $to, 'grade' => $grade]);
        }

        // Science was last week (marked), Maths is next week: the exam is ongoing.
        $exam = $this->ids['exam'] = $this->insert('exams', ['name' => $this->t('Mid term'), 'description' => $this->t('Term 1'), 'session_year_id' => $this->year, 'publish' => 1]);
        $this->insert('exam_classes', ['exam_id' => $exam, 'class_id' => $this->ids['class_a'], 'session_year_id' => $this->year]);
        foreach (['science' => -7, 'maths' => 7] as $subject => $offset) {
            $this->ids["exam_timetable_$subject"] = $this->insert('exam_timetables', [
                'exam_id' => $exam, 'class_id' => $this->ids['class_a'], 'subject_id' => $this->ids[$subject], 'total_marks' => 100,
                'passing_marks' => 35, 'date' => $this->day($offset), 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'session_year_id' => $this->year,
            ]);
        }
        $this->insert('exam_marks', [
            'exam_timetable_id' => $this->ids['exam_timetable_science'], 'student_id' => $this->ids['asha'], 'subject_id' => $this->ids['science'],
            'obtained_marks' => 78, 'teacher_review' => $this->t('Good work'), 'passing_status' => 1, 'session_year_id' => $this->year, 'grade' => 'B',
        ]);
        // Marks can only be entered for an exam that isn't published yet.
        $unitTest = $this->ids['unit_test'] = $this->insert('exams', ['name' => $this->t('Unit test'), 'session_year_id' => $this->year, 'publish' => 0]);
        $this->insert('exam_classes', ['exam_id' => $unitTest, 'class_id' => $this->ids['class_a'], 'session_year_id' => $this->year]);
        $unitTestScience = $this->insert('exam_timetables', [
            'exam_id' => $unitTest, 'class_id' => $this->ids['class_a'], 'subject_id' => $this->ids['science'], 'total_marks' => 50,
            'passing_marks' => 20, 'date' => $this->day(-3), 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'session_year_id' => $this->year,
        ]);
        $this->insert('exam_marks', [
            'exam_timetable_id' => $unitTestScience, 'student_id' => $this->ids['asha'], 'subject_id' => $this->ids['science'],
            'obtained_marks' => 42, 'passing_status' => 1, 'session_year_id' => $this->year, 'grade' => 'A',
        ]);
        $this->insert('exam_results', [
            'exam_id' => $exam, 'class_section_id' => $this->ids['section_a'], 'student_id' => $this->ids['asha'],
            'total_marks' => 100, 'obtained_marks' => 78, 'percentage' => 78, 'grade' => 'B', 'session_year_id' => $this->year,
        ]);
    }

    private function onlineExams(): void
    {
        $question = $this->ids['question'] = $this->insert('online_exam_questions', [
            'session_year_id' => $this->year, 'class_subject_id' => $this->ids['class_subject_a_maths'], 'question_type' => 0, 'question' => $this->t('What is 1/2 + 1/2?'),
        ]);
        foreach (['1', '2', '1/4'] as $option) {
            $this->ids['option_'.str_replace('/', '_', $option)] = $this->insert('online_exam_question_options', ['question_id' => $question, 'option' => $option]);
        }
        $this->insert('online_exam_question_answers', ['question_id' => $question, 'answer' => $this->ids['option_1']]);

        foreach (['online_exam' => [-1, 1, 'Fractions quiz'], 'past_online_exam' => [-10, -9, 'Numbers quiz']] as $key => [$start, $end, $title]) {
            $exam = $this->ids[$key] = $this->insert('online_exams', [
                'model_type' => 'App\Models\ClassSection', 'model_id' => $this->ids['section_a'], 'subject_id' => $this->ids['maths'],
                'title' => $title, 'exam_key' => 1234, 'duration' => 30, 'start_date' => $this->day($start).' 08:00:00',
                'end_date' => $this->day($end).' 20:00:00', 'session_year_id' => $this->year,
            ]);
            $this->insert('online_exam_question_choices', ['online_exam_id' => $exam, 'question_id' => $question, 'marks' => 5]);
        }
        $this->insert('online_exam_student_answers', [
            'student_id' => $this->ids['asha'], 'online_exam_id' => $this->ids['past_online_exam'], 'question_id' => $question,
            'option_id' => $this->ids['option_1'], 'submitted_date' => $this->day(-10),
        ]);
        $this->insert('student_online_exam_statuses', ['student_id' => $this->ids['asha'], 'online_exam_id' => $this->ids['past_online_exam'], 'status' => 2]);
    }

    private function calendar(): void
    {
        $this->ids['holiday'] = $this->insert('holidays', ['session_year_id' => $this->year, 'date' => $this->day(10), 'title' => $this->t('Founders day'), 'description' => $this->t('School closed')]);
        $this->ids['event'] = $this->insert('events', [
            'session_year_id' => $this->year, 'title' => $this->t('Sports day'), 'type' => 'single', 'start_date' => $this->day(14), 'end_date' => $this->day(14),
            'start_time' => '09:00:00', 'end_time' => '15:00:00', 'description' => $this->t('Races and games'),
        ]);
        $this->insert('academic_calendars', ['date' => $this->day(20), 'title' => $this->t('Parents meeting'), 'description' => $this->t('Grade 5'), 'session_year_id' => $this->year]);
    }

    private function leaves(): void
    {
        $this->insert('leave_masters', ['total_leave' => '12', 'holiday_days' => '0', 'session_year_id' => $this->year]);
        foreach ([
            'student_leave' => ['asha', -5, '1'], 'pending_student_leave' => ['ben', 3, '0'], 'leave_to_decide' => ['ben', 4, '0'],
            'teacher_leave' => ['tara', 5, '0'],
        ] as $key => [$person, $offset, $status]) {
            $leave = $this->ids[$key] = $this->insert('leaves', [
                'user_id' => $this->ids[$person.'_user'], 'reason' => $this->t('Family visit'), 'from_date' => $this->day($offset),
                'to_date' => $this->day($offset), 'status' => $status, 'session_year_id' => $this->year,
            ]);
            $this->insert('leave_details', ['session_year_id' => $this->year, 'leave_id' => $leave, 'date' => $this->day($offset), 'type' => 'Full']);
        }
    }

    private function fees(): void
    {
        foreach (['tuition' => ['Tuition', 0, 1000], 'bus' => ['Bus', 1, 200]] as $key => [$name, $optional, $amount]) {
            $this->ids["fees_$key"] = $this->insert('fees_types', ['name' => $this->t($name), 'description' => $this->t("$name fee")]);
            $this->insert('fees_classes', [
                'session_year_id' => $this->year, 'class_id' => $this->ids['class_a'], 'fees_type_id' => $this->ids["fees_$key"], 'choiceable' => $optional, 'amount' => $amount,
            ]);
        }

        $transaction = $this->ids['payment'] = $this->insert('payment_transactions', [
            'student_id' => $this->ids['asha'], 'class_id' => $this->ids['class_a'], 'parent_id' => $this->ids['farid'], 'mode' => 2,
            'type_of_fee' => 0, 'payment_gateway' => 1, 'order_id' => 'order_demo'.$this->suffix(), 'payment_id' => 'pay_demo'.$this->suffix(), 'payment_status' => 1,
            'total_amount' => 1000, 'date' => $this->day(-20).' 10:00:00', 'session_year_id' => $this->year,
        ]);
        $this->ids['fees_paid'] = $this->insert('fees_paids', [
            'parent_id' => $this->ids['farid'], 'student_id' => $this->ids['asha'], 'class_id' => $this->ids['class_a'], 'mode' => 2,
            'payment_transaction_id' => (string) $transaction, 'total_amount' => 1000, 'is_fully_paid' => 1, 'date' => $this->day(-20), 'session_year_id' => $this->year,
        ]);
    }

    private function gallery(): void
    {
        $album = $this->ids['album'] = $this->insert('medias', ['name' => $this->t('Annual day'), 'type' => 1, 'thumbnail' => 'gallery/thumb.png', 'session_year_id' => $this->year]);
        $this->insert('media_files', ['media_id' => $album, 'file_url' => 'gallery/photo1.png']);
        $this->insert('medias', ['name' => $this->t('Science fair'), 'type' => 2, 'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'session_year_id' => $this->year]);
        $this->insert('sliders', ['image' => 'sliders/banner.png', 'type' => '1']);
    }

    private function notifications(): void
    {
        $notification = $this->insert('notifications', [
            'session_year_id' => $this->year, 'title' => $this->t('Welcome back'), 'type' => 'custom', 'message' => $this->t('Term starts Monday'),
            'send_to' => 1, 'date' => $this->day(-1).' 08:00:00', 'is_custom' => 1,
        ]);
        foreach (['asha', 'farid', 'tara'] as $person) {
            $this->insert('user_notifications', ['notification_id' => $notification, 'user_id' => $this->ids[$person.'_user']]);
        }
    }

    private function user(string $name, string $role, array $extra = []): int
    {
        $user = User::forceCreate($extra + [
            'first_name' => ucfirst($name), 'last_name' => $this->t('Demo'), 'email' => $this->email($name),
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        $user->assignRole($role);
        if ($role === 'Parent' || $role === 'Teacher') {
            ParentPolicyService::acceptCurrent($user);
        }

        return $this->ids[$name.'_user'] = $user->id;
    }

    private function file(string $model, int $id, string $name, string $type, string $url, ?string $thumbnail = null): int
    {
        return $this->insert('files', [
            'modal_type' => $model, 'modal_id' => $id, 'file_name' => $name, 'type' => $type, 'file_url' => $url, 'file_thumbnail' => $thumbnail,
        ]);
    }

    /** A name or title of this school: the label first, for another school. */
    private function t(string $text): string
    {
        return $this->label === '' ? $text : "{$this->label} {$text}";
    }

    private function suffix(): string
    {
        return $this->label === '' ? '' : '-'.strtolower($this->label);
    }

    private function email(string $name): string
    {
        return $this->label === '' ? "$name@example.com" : "$name.".strtolower($this->label).'@example.com';
    }

    /** Students sign in with their GR number, which the panel keeps as their email. */
    private function grNumber(string $name): string
    {
        return 'GR-'.($this->label === '' ? '' : strtoupper($this->label).'-').strtoupper($name);
    }

    private function insert(string $table, array $values): int
    {
        $query = SchoolOwnedTables::isSchoolOwned($table) ? SchoolTable::query($table) : DB::table($table);

        return $query->insertGetId($values + ['created_at' => now(), 'updated_at' => now()]);
    }

    /** The school's date, `$offsetDays` from today. */
    public function today(int $offsetDays = 0): Carbon
    {
        return $this->today->copy()->addDays($offsetDays);
    }

    private function day(int $offset): string
    {
        return $this->today->copy()->addDays($offset)->toDateString();
    }
}
