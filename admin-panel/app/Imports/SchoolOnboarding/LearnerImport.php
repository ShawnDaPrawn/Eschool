<?php

declare(strict_types=1);

namespace App\Imports\SchoolOnboarding;

use App\Models\Academics\GradeLevel;
use App\Models\Category;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Identity\GuardianInvitations;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Learners from a sheet (the school's SA-SAMS learner list or its own), each
 * with up to two guardians. A learner is created, placed in a homeroom from
 * the chosen date, and linked to their guardians, who are found by email or
 * created. The LURITS number is recorded; a number that already belongs to a
 * NavigateEd learner elsewhere is not imported (start a transfer instead), and
 * the school is not told where. Learners whose admission number is already in
 * the school are left alone, so a file can be imported again, except that a
 * LURITS number in the file is recorded for one who has none yet (Action Plan
 * 1.0d), as the office's LURITS page does: a number another learner holds is
 * not written but raised as a claim for Navigate Ed support, without saying
 * who holds it.
 */
final class LearnerImport implements OnboardingImport
{
    public const GUARDIANS = ['guardian', 'guardian2'];

    public const RELATIONSHIPS = ['mother', 'father', 'guardian', 'grandmother', 'grandfather', 'aunt', 'uncle', 'sister', 'brother', 'foster parent', 'other'];

    public function key(): string
    {
        return 'learners';
    }

    public function permission(): string
    {
        return 'student-create';
    }

    public function fields(): array
    {
        $fields = [
            'admission_no' => ['label' => 'Admission number', 'required' => true, 'aliases' => ['admission no', 'admission number', 'admission', 'accession no', 'accession number', 'learner number', 'toelatingsnommer']],
            'first_name' => ['label' => 'First name', 'required' => true, 'aliases' => ['first name', 'first names', 'firstname', 'name', 'learner name', 'names', 'voornaam', 'voorname']],
            'last_name' => ['label' => 'Surname', 'required' => true, 'aliases' => ['surname', 'last name', 'lastname', 'family name', 'learner surname', 'van']],
            'dob' => ['label' => 'Date of birth', 'required' => false, 'aliases' => ['date of birth', 'birth date', 'dob', 'birthdate', 'geboortedatum']],
            'gender' => ['label' => 'Gender', 'required' => false, 'aliases' => ['gender', 'sex', 'geslag']],
            'lurits' => ['label' => 'LURITS number', 'required' => false, 'aliases' => ['lurits', 'lurits number', 'lurits no', 'learner id', 'learner unit id']],
            'homeroom' => ['label' => 'Homeroom (for example Grade 8 B)', 'required' => false, 'aliases' => ['homeroom', 'register class', 'class', 'klas', 'registerklas']],
            'grade' => ['label' => 'Grade', 'required' => false, 'aliases' => ['grade', 'gr', 'graad']],
            'letter' => ['label' => 'Homeroom letter', 'required' => false, 'aliases' => ['letter', 'section', 'class letter']],
        ];
        foreach (self::GUARDIANS as $index => $prefix) {
            $n = $index + 1;
            $words = $n === 1 ? ['parent', 'guardian', 'parent 1', 'guardian 1', 'mother'] : ['parent 2', 'guardian 2', 'second parent', 'father'];
            $fields[$prefix.'_first_name'] = ['label' => __('Guardian :n first name', ['n' => $n]), 'required' => false, 'aliases' => [...array_map(fn ($w) => "$w first name", $words), "$words[0] name"]];
            $fields[$prefix.'_last_name'] = ['label' => __('Guardian :n surname', ['n' => $n]), 'required' => false, 'aliases' => array_map(fn ($w) => "$w surname", $words)];
            $fields[$prefix.'_email'] = ['label' => __('Guardian :n email', ['n' => $n]), 'required' => false, 'aliases' => array_map(fn ($w) => "$w email", $words)];
            $fields[$prefix.'_mobile'] = ['label' => __('Guardian :n mobile', ['n' => $n]), 'required' => false, 'aliases' => [...array_map(fn ($w) => "$w mobile", $words), ...array_map(fn ($w) => "$w cell", $words)]];
            $fields[$prefix.'_relationship'] = ['label' => __('Guardian :n relationship', ['n' => $n]), 'required' => false, 'aliases' => array_map(fn ($w) => "$w relationship", $words)];
        }

        return array_map(fn (array $field) => ['label' => $field['label'], 'required' => $field['required'], 'aliases' => array_values($field['aliases'])], $fields);
    }

    public function plan(CsvSheet $sheet, array $mapping, array $options): array
    {
        $rooms = $this->homerooms();
        $grades = GradeLevel::query()->get();
        $numbers = collect($sheet->rows)->map(fn (array $source) => isset($mapping['admission_no']) ? trim((string) ($source[$mapping['admission_no']] ?? '')) : '')->filter()->values();
        $known = Students::query()->withTrashed()->whereIn('admission_no', $numbers->all())->get(['admission_no', 'deleted_at'])
            ->mapWithKeys(fn (Students $row) => [mb_strtolower((string) $row->admission_no) => $row->trashed()]);
        $here = Students::query()->withTrashed()->pluck('user_id')->flip();
        // Learners already here, with their LURITS hash, to fill in missing numbers (1.0d).
        $current = Students::query()->whereIn('admission_no', $numbers->all())->get(['id', 'user_id', 'admission_no'])
            ->keyBy(fn (Students $row) => mb_strtolower((string) $row->admission_no));
        $hashes = DB::table('learner_identifiers')->whereIn('user_id', $current->pluck('user_id'))->pluck('lurits_hash', 'user_id');
        $lurits = app(LuritsNumbers::class);
        $seenNumbers = [];
        $seenLurits = [];
        $rows = [];
        foreach ($sheet->rows as $source) {
            $value = fn (string $field) => isset($mapping[$field]) ? trim((string) ($source[$mapping[$field]] ?? '')) : '';
            $line = (int) $source[CsvSheet::LINE];
            $notes = [];
            $warnings = [];
            $number = $value('admission_no');
            $first = Values::name($value('first_name'));
            $last = Values::name($value('last_name'));
            $label = trim($value('first_name').' '.$value('last_name')).($number !== '' ? ' ('.$number.')' : '');
            if ($number === '' || mb_strlen($number) > 64) {
                $notes[] = __('The admission number is missing or too long.');
            } elseif (isset($seenNumbers[mb_strtolower($number)])) {
                $notes[] = __('Admission number :number is already on line :line.', ['number' => $number, 'line' => $seenNumbers[mb_strtolower($number)]]);
            }
            $seenNumbers[mb_strtolower($number)] ??= $line;
            if ($first === null || $last === null) {
                $notes[] = __('The first name and surname must be letters.');
            }
            $dob = Values::date($value('dob'));
            if ($value('dob') !== '' && ($dob === null || $dob >= now()->toDateString())) {
                $notes[] = __('":date" is not a date of birth (use 2014-03-09 or 09/03/2014).', ['date' => $value('dob')]);
            }
            $gender = Values::gender($value('gender'));
            $room = $this->room($rooms, $grades, $value('homeroom'), $value('grade'), $value('letter'), $notes);

            $luritsNumber = $value('lurits') !== '' ? LuritsNumbers::normalise($value('lurits')) : null;
            if ($luritsNumber !== null && ! LuritsNumbers::valid($luritsNumber)) {
                $notes[] = __('":number" is not a valid LURITS number.', ['number' => $value('lurits')]);
                $luritsNumber = null;
            } elseif ($luritsNumber !== null && isset($seenLurits[$luritsNumber])) {
                $notes[] = __('This LURITS number is already on line :line.', ['line' => $seenLurits[$luritsNumber]]);
            }
            if ($luritsNumber !== null) {
                $seenLurits[$luritsNumber] ??= $line;
            }

            $guardians = [];
            foreach (self::GUARDIANS as $index => $prefix) {
                $guardian = $this->guardian($value, $prefix, $index + 1, $notes, $warnings);
                if ($guardian !== null) {
                    if (in_array($guardian['email'], array_column($guardians, 'email'), true)) {
                        $notes[] = __('Both guardians have the same email address.');
                    } else {
                        $guardians[] = $guardian;
                    }
                }
            }

            $row = ['line' => $line, 'label' => $label, 'notes' => $notes, 'status' => 'error', 'homeroom' => $room['label'] ?? '', 'guardians' => count($guardians)];
            if ($notes === []) {
                $holder = $luritsNumber !== null ? $lurits->holderUserId($luritsNumber) : null;
                if ($known->has(mb_strtolower($number)) && $luritsNumber !== null && $current->has(mb_strtolower($number))) {
                    $this->existing($row, $current[mb_strtolower($number)], $hashes, $luritsNumber, $holder);
                } elseif ($known->has(mb_strtolower($number))) {
                    $row['status'] = 'exists';
                    $row['notes'][] = $known[mb_strtolower($number)] ? __('An archived learner has this admission number.') : __('Already at the school.');
                } elseif ($holder !== null && $here->has($holder)) {
                    $row['status'] = 'exists';
                    $row['notes'][] = __('A learner at the school already has this LURITS number.');
                } elseif ($holder !== null) {
                    // 1.0c: the learner already has a NavigateEd record elsewhere.
                    // Never say where; the consented transfer moves the records.
                    $row['status'] = 'transfer';
                    $row['notes'][] = __('This learner already has a NavigateEd record. Start a transfer request instead of importing them.');
                } else {
                    $problem = collect($guardians)->map(fn (array $guardian) => $this->guardianProblem($guardian['email']))->filter()->first();
                    if ($problem !== null) {
                        $row['notes'][] = $problem;
                    } else {
                        $row['status'] = 'create';
                        if ($guardians === []) {
                            $row['notes'][] = __('No guardian with an email address: the learner cannot be approved until one is linked.');
                        }
                        $row['data'] = [
                            'admission_no' => $number, 'first_name' => $first, 'last_name' => $last, 'dob' => $dob, 'gender' => $gender,
                            'lurits' => $luritsNumber, 'homeroom_id' => $room['id'], 'guardians' => $guardians,
                        ];
                    }
                }
            }
            $row['notes'] = [...$row['notes'], ...$warnings];
            $rows[] = $row;
        }

        return $rows;
    }

    public function commit(User $actor, array $rows, array $options): array
    {
        $year = SessionYear::query()->findOrFail((int) $options['session_year_id']);
        $category = Category::query()->findOrFail((int) $options['category_id']);
        $startsOn = (string) $options['starts_on'];
        $school = app(CurrentSchool::class)->id();
        $invite = [];
        $source = ! empty($options['from_sasams']) ? 'sasams_import' : 'onboarding_import';
        $counts = ['created' => 0, 'guardians_linked' => 0, 'lurits_recorded' => 0, 'lurits_claimed' => 0, 'lurits_kept' => 0];
        foreach ($rows as $row) {
            if ($row['status'] === 'lurits') {
                // A learner already here gets the file's number, or the number
                // is raised as a claim when another learner holds it (1.0d).
                // A number recorded since the check (by the office) is kept.
                $learner = Students::query()->findOrFail($row['data']['learner_id']);
                $result = app(LuritsNumbers::class)->recordIfUnset($learner, $row['data']['lurits'], $source, $actor);
                $counts['lurits_recorded'] += $result === LuritsNumbers::RECORDED ? 1 : 0;
                $counts['lurits_claimed'] += $result === LuritsNumbers::CONFLICT ? 1 : 0;
                $counts['lurits_kept'] += $result === LuritsNumbers::KEPT ? 1 : 0;

                continue;
            }
            if ($row['status'] !== 'create') {
                continue;
            }
            $data = $row['data'];
            $room = ClassSection::query()->findOrFail($data['homeroom_id']);
            $parents = [];
            foreach ($data['guardians'] as $guardian) {
                $parents[] = [$this->guardianUser($guardian), $guardian['relationship']];
            }
            $person = User::query()->create([
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'dob' => $data['dob'], 'gender' => $data['gender'] ?? 'Not specified',
                'email' => 'import-'.Str::uuid()->toString().'@learners.invalid', 'password' => bin2hex(random_bytes(32)), 'status' => 1,
            ]);
            $person->forceFill(['password_change_required' => true])->save();
            $person->assignRole('Student');
            SchoolMembership::query()->where('school_id', $school)->where('user_id', $person->id)->where('role', 'Student')->update(['starts_on' => $startsOn, 'ends_on' => null]);
            app(MembershipService::class)->forget($person);
            $first = $parents[0][0] ?? null;
            $learner = Students::query()->create([
                'user_id' => $person->id, 'class_id' => $room->class_id, 'class_section_id' => $room->id, 'category_id' => $category->id,
                'guardian_id' => $first !== null ? Parents::query()->where('user_id', $first->id)->value('id') : null,
                'admission_no' => $data['admission_no'], 'admission_date' => $startsOn, 'is_new_admission' => false, 'application_type' => 'offline',
            ]);
            foreach ($parents as [$guardian, $relationship]) {
                GuardianLink::query()->updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id],
                    ['relationship' => $relationship, 'status' => 'active', 'can_approve' => true, 'can_see_fees' => true]);
                $invite[$guardian->id] = $guardian;
                $counts['guardians_linked']++;
            }
            app(EnrolmentService::class)->place((int) $learner->id, (int) $room->id, (int) $year->id, $startsOn, 'School onboarding import');
            if ($data['lurits'] !== null) {
                $claimed = app(LuritsNumbers::class)->record($learner, $data['lurits'], $source, $actor);
                // Claimed elsewhere since the check: the learner already has a
                // record, so nothing of this import may stand (1.0c).
                if ($claimed === LuritsNumbers::CONFLICT) {
                    throw ValidationException::withMessages(['fingerprint' => __('The school\'s records changed since this check. Check the file again, then import.')]);
                }
                $counts['lurits_recorded'] += $claimed === LuritsNumbers::RECORDED ? 1 : 0;
            }
            $counts['created']++;
        }
        $counts['guardians_invited'] = 0;
        if (! empty($options['invite'])) {
            foreach ($invite as $guardian) {
                app(GuardianInvitations::class)->issue($guardian, $actor);
                $counts['guardians_invited']++;
            }
        }

        return $counts;
    }

    /**
     * A row for a learner already at the school whose file row gives a LURITS
     * number: recorded when they have none (status lurits), otherwise left
     * alone. A number another learner holds is still recorded as a claim on
     * import, as the office page does, and the school is told only that it is
     * registered to another learner.
     *
     * @param  array<string, mixed>  $row
     * @param  Collection<int|string, string|null>  $hashes  LURITS hash by user id
     */
    private function existing(array &$row, Students $learner, Collection $hashes, string $number, ?int $holder): void
    {
        $hash = LuritsNumbers::hash($number);
        $recorded = $hashes->get($learner->user_id);
        $row['status'] = 'exists';
        if ($recorded === $hash) {
            $row['notes'][] = __('Already at the school.');
        } elseif ($recorded !== null) {
            $row['notes'][] = __('Already at the school with a different LURITS number, which is kept. If the file is right, correct it on the LURITS numbers page.');
        } elseif (SchoolTable::query('lurits_conflicts')->where('learner_id', $learner->id)->where('lurits_hash', $hash)->where('status', 'open')->exists()) {
            $row['notes'][] = __('Already at the school. This LURITS number is waiting for Navigate Ed support to check it.');
        } else {
            $row['status'] = 'lurits';
            $row['notes'][] = $holder !== null
                ? __('Already at the school. This LURITS number is already registered to another learner, so it will not be recorded: Navigate Ed support will check it. If the learner is joining from another school, start a transfer request.')
                : __('Already at the school. Their LURITS number will be recorded.');
            $row['data'] = ['learner_id' => (int) $learner->id, 'lurits' => $number];
        }
    }

    /**
     * The homeroom a row names, by its full name ("Grade 8 B") or by grade and
     * letter; null with a note when it names none, or several.
     *
     * @param  Collection<int, array{id: int, label: string, grade_level_id: int|null, letter: string}>  $rooms
     * @param  Collection<int, GradeLevel>  $grades
     * @param  list<string>  $notes
     * @return array{id: int, label: string}|null
     */
    private function room(Collection $rooms, Collection $grades, string $homeroom, string $grade, string $letter, array &$notes): ?array
    {
        if ($homeroom !== '') {
            $wanted = mb_strtolower(preg_replace('/\s+/', ' ', $homeroom) ?? $homeroom);
            $found = $rooms->filter(fn (array $room) => mb_strtolower($room['label']) === $wanted);
            if ($found->isEmpty() && $grade === '' && preg_match('/^(.*?)[\s\-]*([\p{L}])$/u', $homeroom, $m)) {
                // "8B", "Gr 8B", "Grade 8-B".
                [$grade, $letter] = [$m[1], $m[2]];
            }
        }
        if (($found ?? collect())->isEmpty() && $grade !== '' && $letter !== '') {
            $level = Values::grade($grade, $grades);
            $found = $level === null ? collect() : $rooms->filter(fn (array $room) => $room['grade_level_id'] === (int) $level->id && mb_strtoupper($room['letter']) === mb_strtoupper($letter));
        }
        $found ??= collect();
        if ($homeroom === '' && ($grade === '' || $letter === '')) {
            $notes[] = __('Give the homeroom, or the grade and the homeroom letter.');
        } elseif ($found->count() !== 1) {
            $named = $homeroom !== '' ? $homeroom : trim($grade.' '.$letter);
            $notes[] = $found->isEmpty()
                ? __('No homeroom ":room". Import the homerooms first.', ['room' => $named])
                : __('Several homerooms match ":room". Use the full homeroom name.', ['room' => $named]);
        } else {
            $room = $found->first();

            return ['id' => $room['id'], 'label' => $room['label']];
        }

        return null;
    }

    /**
     * One guardian of a row, or null when the row names none. A guardian
     * without an email address cannot be given an account and is noted.
     *
     * @param  callable(string): string  $value
     * @param  list<string>  $notes  problems that stop the row
     * @param  list<string>  $warnings  things to know that do not
     * @return array{first_name: string, last_name: string, email: string, mobile: string|null, relationship: string}|null
     */
    private function guardian(callable $value, string $prefix, int $n, array &$notes, array &$warnings): ?array
    {
        $given = array_filter([$value($prefix.'_first_name'), $value($prefix.'_last_name'), $value($prefix.'_email'), $value($prefix.'_mobile')], fn ($part) => $part !== '');
        if ($given === []) {
            return null;
        }
        if ($value($prefix.'_email') === '') {
            $warnings[] = __('Guardian :n has no email address and is not added.', ['n' => $n]);

            return null;
        }
        $email = Values::email($value($prefix.'_email'));
        $first = Values::name($value($prefix.'_first_name'));
        $last = Values::name($value($prefix.'_last_name'));
        $mobile = $value($prefix.'_mobile') !== '' ? Values::mobile($value($prefix.'_mobile')) : null;
        $relationship = mb_strtolower($value($prefix.'_relationship')) ?: 'guardian';
        if ($email === null) {
            $notes[] = __('Guardian :n\'s email address is not valid.', ['n' => $n]);
        }
        if ($first === null || $last === null) {
            $notes[] = __('Guardian :n needs a first name and surname.', ['n' => $n]);
        }
        if ($value($prefix.'_mobile') !== '' && $mobile === null) {
            $notes[] = __('Guardian :n\'s mobile number is not valid.', ['n' => $n]);
        }
        if (! in_array($relationship, self::RELATIONSHIPS, true)) {
            $relationship = 'other';
        }

        return $email === null || $first === null || $last === null ? null
            : ['first_name' => $first, 'last_name' => $last, 'email' => $email, 'mobile' => $mobile, 'relationship' => $relationship];
    }

    /** Why an existing account with this email cannot be linked as a guardian, or null. */
    private function guardianProblem(string $email): ?string
    {
        $user = User::acrossSchools()->withTrashed()->where('email', $email)->first();
        if ($user === null) {
            return null;
        }
        $suspended = SchoolMembership::query()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $user->id)
            ->where('role', 'Parent')->where('status', SchoolMembership::SUSPENDED)->exists();

        return $user->trashed() || (int) $user->status !== 1 || $suspended
            ? __('The account for :email cannot be linked. Check it with the guardian before importing this learner.', ['email' => $email])
            : null;
    }

    /** The guardian's account, found by email or created, with the Parent role here. */
    private function guardianUser(array $guardian): User
    {
        $user = User::acrossSchools()->withTrashed()->firstOrCreate(['email' => $guardian['email']], [
            'first_name' => $guardian['first_name'], 'last_name' => $guardian['last_name'], 'mobile' => $guardian['mobile'],
            'gender' => 'Not specified', 'password' => bin2hex(random_bytes(32)), 'status' => 1,
        ]);
        $created = $user->wasRecentlyCreated;
        $user = User::acrossSchools()->withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
        abort_if($user->trashed() || (int) $user->status !== 1, 409, __('The account for :email cannot be linked.', ['email' => $guardian['email']]));
        if ($created) {
            $user->forceFill(['password_change_required' => true])->save();
        }
        $user->unsetRelation('roles')->unsetRelation('permissions')->assignRole('Parent');
        app(MembershipService::class)->forget($user);
        Parents::query()->firstOrCreate(['user_id' => $user->id], [
            'first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => $user->email,
            'mobile' => $user->mobile ?? '', 'gender' => $user->gender ?? 'Not specified',
        ]);

        return $user;
    }

    /** @return Collection<int, array{id: int, label: string, grade_level_id: int|null, letter: string}> */
    private function homerooms(): Collection
    {
        $classes = ClassSchool::query()->get()->keyBy('id');

        return ClassSection::query()->with('section')->get()
            ->filter(fn (ClassSection $room) => $classes->has($room->class_id))
            ->map(fn (ClassSection $room) => [
                'id' => (int) $room->id,
                'label' => trim(preg_replace('/\s+/', ' ', $classes[$room->class_id]->name.' '.($room->section?->name ?? '')) ?? ''),
                'grade_level_id' => $classes[$room->class_id]->grade_level_id !== null ? (int) $classes[$room->class_id]->grade_level_id : null,
                'letter' => (string) ($room->section?->name ?? ''),
            ])->values();
    }
}
