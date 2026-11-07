<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\OneRoster;

use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * The rows of a OneRoster 1.2 CSV bulk export (Action Plan 5.9) for the
 * current school's school year, as it stands on the school's today: the
 * school, the year and its terms, homerooms and teaching groups with their
 * courses, the learners and teachers in them, their roles and enrolments, and
 * (only when the school asks for them) learners' birth dates and sex.
 *
 * Learners are identified LURITS-first: the LURITS number when recorded, else
 * the NavigateEd number. sourcedIds are stable, opaque UUIDs, so a receiving
 * system can update its records from a later export. Every query runs inside
 * the current school (BelongsToSchool), and nothing about guardians, contact
 * details of learners, marks or other schools is read.
 */
class OneRosterTables
{
    /** The OneRoster 1.2 CSV binding's column headers, in order. */
    public const HEADERS = [
        'orgs.csv' => ['sourcedId', 'status', 'dateLastModified', 'name', 'type', 'identifier', 'parentSourcedId'],
        'academicSessions.csv' => ['sourcedId', 'status', 'dateLastModified', 'title', 'type', 'startDate', 'endDate', 'parentSourcedId', 'schoolYear'],
        'courses.csv' => ['sourcedId', 'status', 'dateLastModified', 'schoolYearSourcedId', 'title', 'courseCode', 'grades', 'orgSourcedId', 'subjects', 'subjectCodes'],
        'classes.csv' => ['sourcedId', 'status', 'dateLastModified', 'title', 'grades', 'courseSourcedId', 'classCode', 'classType', 'location', 'schoolSourcedId', 'termSourcedIds', 'subjects', 'subjectCodes', 'periods'],
        'users.csv' => ['sourcedId', 'status', 'dateLastModified', 'enabledUser', 'username', 'userIds', 'givenName', 'familyName', 'middleName', 'identifier', 'email', 'sms', 'phone', 'agentSourcedIds', 'grades', 'password', 'userMasterIdentifier', 'preferredGivenName', 'preferredMiddleName', 'preferredFamilyName', 'primaryOrgSourcedId', 'pronouns'],
        'roles.csv' => ['sourcedId', 'status', 'dateLastModified', 'userSourcedId', 'roleType', 'role', 'beginDate', 'endDate', 'orgSourcedId', 'userProfileSourcedId'],
        'enrollments.csv' => ['sourcedId', 'status', 'dateLastModified', 'classSourcedId', 'schoolSourcedId', 'userSourcedId', 'role', 'primary', 'beginDate', 'endDate'],
        'demographics.csv' => ['sourcedId', 'status', 'dateLastModified', 'birthDate', 'sex', 'americanIndianOrAlaskaNative', 'asian', 'blackOrAfricanAmerican', 'nativeHawaiianOrOtherPacificIslander', 'white', 'demographicRaceTwoOrMoreRaces', 'hispanicOrLatinoEthnicity', 'countryOfBirthCode', 'stateOfBirthAbbreviation', 'cityOfBirth', 'publicSchoolResidenceStatus'],
    ];

    /** Every file manifest.csv lists (OneRoster 1.2), in its order. */
    public const MANIFEST_FILES = ['academicSessions', 'categories', 'classes', 'classResources', 'courses', 'courseResources', 'demographics', 'enrollments',
        'lineItemLearningObjectiveIds', 'lineItems', 'lineItemScoreScales', 'orgs', 'resources', 'resultLearningObjectiveIds', 'results', 'resultScoreScales',
        'roles', 'scoreLearningObjectiveIds', 'scoreScales', 'userProfiles', 'userResources', 'users'];

    /** @var array<string, list<array<string, string>>> */
    private array $rows = [];

    private string $org = '';

    /** @var array<string, true> */
    private array $classes = [];

    /** @var array<string, true> */
    private array $enrolled = [];

    /**
     * @return array<string, list<array<string, string>>> file name => rows keyed by header
     */
    public function build(int $sessionYearId, bool $demographics, string $today): array
    {
        $this->rows = array_fill_keys(array_keys(self::HEADERS), []);
        $this->classes = [];
        $this->enrolled = [];
        if (! $demographics) {
            unset($this->rows['demographics.csv']);
        }
        $school = app(CurrentSchool::class)->school();
        $year = SessionYear::findOrFail($sessionYearId);
        $this->org = $this->id('org', (int) $school->id);
        $emis = DB::table('school_accounts')->where('school_id', $school->id)->value('emis_number');
        $this->add('orgs.csv', ['sourcedId' => $this->org, 'name' => (string) $school->name, 'type' => 'school', 'identifier' => (string) ($emis ?: $school->slug)]);

        $yearId = $this->id('academicSession', (int) $year->id);
        $yearEnd = substr((string) $year->end_date, 0, 10);
        $this->add('academicSessions.csv', ['sourcedId' => $yearId, 'title' => (string) $year->name, 'type' => 'schoolYear',
            'startDate' => substr((string) $year->start_date, 0, 10), 'endDate' => $yearEnd, 'schoolYear' => substr($yearEnd, 0, 4)]);
        $terms = [];
        foreach (Term::where('session_year_id', $year->id)->orderBy('start_date')->get() as $term) {
            $terms[] = $id = $this->id('academicSession.term', (int) $term->id);
            $this->add('academicSessions.csv', ['sourcedId' => $id, 'title' => (string) $term->name, 'type' => 'term', 'startDate' => $term->start_date?->toDateString() ?? '',
                'endDate' => $term->end_date?->toDateString() ?? '', 'parentSourcedId' => $yearId, 'schoolYear' => substr($yearEnd, 0, 4)]);
        }
        $termIds = $terms === [] ? [$yearId] : $terms;

        $learners = [];
        $teachers = [];
        $courses = [];
        // Homerooms: the learners placed in them today and their current homeroom teachers.
        $places = Enrolment::with(['learner.user', 'homeroom.class.gradeLevel', 'homeroom.section'])->where('session_year_id', $year->id)->onDate($today)->orderBy('id')->get();
        foreach ($places as $place) {
            $user = $place->learner?->user;
            $grade = $place->homeroom?->class?->gradeLevel;
            if ($user === null || $place->homeroom === null) {
                continue;
            }
            $learners[(int) $place->learner_id] ??= ['learner' => $place->learner, 'grade' => $this->grade($grade?->code)];
            $class = $this->id('class.homeroom', (int) $place->class_section_id);
            if (! isset($this->classes[$class])) {
                $course = $this->id('course.homeroom', (int) ($grade->id ?? 0));
                if (! isset($courses[$course])) {
                    $courses[$course] = true;
                    $this->add('courses.csv', ['sourcedId' => $course, 'schoolYearSourcedId' => $yearId, 'title' => trim(__('Homeroom').' '.($grade->name ?? '')),
                        'courseCode' => 'HOMEROOM-'.($grade->code ?? 'NA'), 'grades' => $this->grade($grade?->code), 'orgSourcedId' => $this->org]);
                }
                $this->classes[$class] = true;
                $this->add('classes.csv', ['sourcedId' => $class, 'title' => trim(($place->homeroom->class->name ?? '').' '.($place->homeroom->section->name ?? '')),
                    'grades' => $this->grade($grade?->code), 'courseSourcedId' => $course, 'classCode' => 'HR-'.$place->class_section_id, 'classType' => 'homeroom',
                    'schoolSourcedId' => $this->org, 'termSourcedIds' => implode(',', $termIds)]);
            }
            $this->enrol($class, $this->id('user', (int) $user->id), 'student', false, $place->starts_on?->toDateString(), $place->ends_on?->toDateString(), 'homeroom:'.$place->id);
        }
        $current = fn ($q) => $q->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today);
        foreach (HomeroomAllocation::where('session_year_id', $year->id)->where($current)->orderBy('id')->get() as $allocation) {
            $class = $this->id('class.homeroom', (int) $allocation->class_section_id);
            $teacher = $this->teacher((int) $allocation->teacher_id, $teachers);
            if (isset($this->classes[$class]) && $teacher !== null) {
                $this->enrol($class, $teacher, 'teacher', true, $allocation->starts_on?->toDateString(), $allocation->ends_on?->toDateString(), 'homeroom-teacher:'.$allocation->id);
            }
        }

        // Teaching groups: one class each, under a course per subject and grade.
        foreach (TeachingGroup::with(['subject', 'gradeLevel'])->where('session_year_id', $year->id)->where('active', true)->orderBy('id')->get() as $group) {
            $course = $this->id('course.subject', (int) $group->subject_id.'-'.(int) $group->grade_level_id);
            $subject = (string) ($group->subject->name ?? '');
            $code = (string) ($group->subject->code ?? '');
            if (! isset($courses[$course])) {
                $courses[$course] = true;
                $this->add('courses.csv', ['sourcedId' => $course, 'schoolYearSourcedId' => $yearId, 'title' => trim($subject.' '.($group->gradeLevel->name ?? '')),
                    'courseCode' => trim(($code !== '' ? $code : 'SUBJECT-'.$group->subject_id).'-'.($group->gradeLevel->code ?? ''), '-'), 'grades' => $this->grade($group->gradeLevel?->code),
                    'orgSourcedId' => $this->org, 'subjects' => $subject, 'subjectCodes' => $code !== '' ? $code : '']);
            }
            $class = $this->id('class.group', (int) $group->id);
            $this->classes[$class] = true;
            $this->add('classes.csv', ['sourcedId' => $class, 'title' => (string) $group->name, 'grades' => $this->grade($group->gradeLevel?->code), 'courseSourcedId' => $course,
                'classCode' => 'TG-'.$group->id, 'classType' => 'scheduled', 'schoolSourcedId' => $this->org, 'termSourcedIds' => implode(',', $termIds),
                'subjects' => $subject, 'subjectCodes' => $code !== '' ? $code : '']);
            foreach (TeachingGroupMembership::with('learner.user')->where('teaching_group_id', $group->id)->where($current)->orderBy('id')->get() as $member) {
                $user = $member->learner?->user;
                if ($user === null || ! isset($learners[(int) $member->learner_id])) {
                    // Only learners placed at the school today are in the export.
                    continue;
                }
                $this->enrol($class, $this->id('user', (int) $user->id), 'student', false, $member->starts_on?->toDateString(), $member->ends_on?->toDateString(), 'group:'.$member->id);
            }
            foreach (TeachingAllocation::where('teaching_group_id', $group->id)->where($current)->orderBy('id')->get() as $allocation) {
                $teacher = $this->teacher((int) $allocation->teacher_id, $teachers);
                if ($teacher !== null) {
                    $this->enrol($class, $teacher, 'teacher', true, $allocation->starts_on?->toDateString(), $allocation->ends_on?->toDateString(), 'group-teacher:'.$allocation->id);
                }
            }
        }

        $this->learners($learners, $demographics);
        foreach ($teachers as $userId => $teacher) {
            if ($teacher === null) {
                continue;
            }
            $sourced = $this->id('user', $userId);
            $this->add('users.csv', ['sourcedId' => $sourced, 'enabledUser' => 'true', 'username' => (string) ($teacher->email ?: $sourced),
                'givenName' => (string) $teacher->first_name, 'familyName' => (string) $teacher->last_name, 'email' => (string) $teacher->email, 'primaryOrgSourcedId' => $this->org]);
            $this->add('roles.csv', ['sourcedId' => $this->id('role', $userId.'-teacher'), 'userSourcedId' => $sourced, 'roleType' => 'primary', 'role' => 'teacher', 'orgSourcedId' => $this->org]);
        }

        return $this->rows;
    }

    /**
     * manifest.csv rows for the files this export holds.
     *
     * @param  list<string>  $files
     * @return list<array{propertyName: string, value: string}>
     */
    public static function manifest(array $files): array
    {
        $rows = [['propertyName' => 'manifest.version', 'value' => '1.0'], ['propertyName' => 'oneroster.version', 'value' => '1.2']];
        foreach (self::MANIFEST_FILES as $file) {
            $rows[] = ['propertyName' => 'file.'.$file, 'value' => in_array($file.'.csv', $files, true) ? 'bulk' : 'absent'];
        }
        $rows[] = ['propertyName' => 'source.systemName', 'value' => (string) config('open_standards.oneroster.system_name')];
        $rows[] = ['propertyName' => 'source.systemCode', 'value' => (string) (app(CurrentSchool::class)->school()?->slug ?? '')];

        return $rows;
    }

    /** @param array<int, array{learner: Students, grade: string}> $learners */
    private function learners(array $learners, bool $demographics): void
    {
        if ($learners === []) {
            return;
        }
        $models = array_map(fn (array $entry) => $entry['learner'], $learners);
        // LURITS first (read for this school's own learners only), then the NavigateEd number.
        $lurits = app(LuritsNumbers::class)->forLearners($models);
        $numbers = DB::table('learner_identifiers')->whereIn('user_id', array_map(fn ($l) => (int) $l->user_id, $models))->pluck('student_number', 'user_id');
        foreach ($learners as $learnerId => $entry) {
            $user = $entry['learner']->user;
            $sourced = $this->id('user', (int) $user->id);
            $luritsNumber = $lurits[$learnerId] ?? null;
            $number = $numbers[(int) $user->id] ?? null;
            $ids = array_values(array_filter([$luritsNumber !== null ? '{LURITS:'.$luritsNumber.'}' : null, $number !== null ? '{NavigateEd:'.$number.'}' : null]));
            $identifier = (string) ($luritsNumber ?? $number ?? '');
            $this->add('users.csv', ['sourcedId' => $sourced, 'enabledUser' => 'true', 'username' => $identifier !== '' ? $identifier : $sourced, 'userIds' => implode(',', $ids),
                'givenName' => (string) $user->first_name, 'familyName' => (string) $user->last_name, 'identifier' => $identifier, 'grades' => $entry['grade'],
                'userMasterIdentifier' => (string) ($luritsNumber ?? ''), 'primaryOrgSourcedId' => $this->org]);
            $this->add('roles.csv', ['sourcedId' => $this->id('role', $user->id.'-student'), 'userSourcedId' => $sourced, 'roleType' => 'primary', 'role' => 'student', 'orgSourcedId' => $this->org]);
            if ($demographics) {
                $this->add('demographics.csv', ['sourcedId' => $sourced, 'birthDate' => $this->date($user->dob), 'sex' => $this->sex($user->gender)]);
            }
        }
    }

    /** @param array<int, User|null> $teachers */
    private function teacher(int $teacherId, array &$teachers): ?string
    {
        $userId = Teacher::whereKey($teacherId)->value('user_id');
        if ($userId === null) {
            return null;
        }
        if (! array_key_exists((int) $userId, $teachers)) {
            $teachers[(int) $userId] = User::query()->where('status', 1)->find($userId, ['id', 'first_name', 'last_name', 'email']);
        }

        return $teachers[(int) $userId] === null ? null : $this->id('user', (int) $userId);
    }

    private function enrol(string $class, string $user, string $role, bool $primary, ?string $begins, ?string $ends, string $key): void
    {
        $id = $this->id('enrollment', $key);
        if (isset($this->enrolled[$id])) {
            return;
        }
        $this->enrolled[$id] = true;
        $this->add('enrollments.csv', ['sourcedId' => $id, 'classSourcedId' => $class, 'schoolSourcedId' => $this->org, 'userSourcedId' => $user, 'role' => $role,
            'primary' => $primary ? 'true' : 'false', 'beginDate' => (string) $begins, 'endDate' => (string) $ends]);
    }

    /** @param array<string, string> $values */
    private function add(string $file, array $values): void
    {
        $row = [];
        foreach (self::HEADERS[$file] as $header) {
            $row[$header] = $values[$header] ?? '';
        }
        $this->rows[$file][] = $row;
    }

    /** A stable, opaque sourcedId for one of this school's records. */
    private function id(string $kind, int|string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'navigateed:oneroster:'.rtrim((string) config('app.url'), '/').':school/'.app(CurrentSchool::class)->id().':'.$kind.'/'.$key)->toString();
    }

    /** The CEDS grade code OneRoster expects: Grade R is kindergarten. */
    private function grade(?string $code): string
    {
        return match (true) {
            $code === 'R' => 'KG',
            $code !== null && ctype_digit($code) && (int) $code >= 1 && (int) $code <= 12 => str_pad($code, 2, '0', STR_PAD_LEFT),
            default => '',
        };
    }

    private function sex(?string $gender): string
    {
        return match (strtolower(trim((string) $gender))) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            '' => 'unspecified',
            default => 'other',
        };
    }

    private function date(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return CarbonImmutable::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return '';
        }
    }
}
