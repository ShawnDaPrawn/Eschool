<?php

declare(strict_types=1);

namespace App\Imports\SchoolOnboarding;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\Mediums;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\AcademicReferences;

/**
 * Homerooms from a sheet: one row per homeroom, by grade and letter
 * ("8", "B"), with an optional class name and medium. Classes and letters are
 * reused when they exist; a homeroom that exists is left alone.
 */
final class HomeroomImport implements OnboardingImport
{
    public function key(): string
    {
        return 'homerooms';
    }

    public function permission(): string
    {
        return 'class-create';
    }

    public function fields(): array
    {
        return [
            'grade' => ['label' => 'Grade', 'required' => true, 'aliases' => ['grade', 'gr', 'grade level', 'graad', 'ibanga']],
            'letter' => ['label' => 'Homeroom letter', 'required' => true, 'aliases' => ['letter', 'section', 'class', 'register class', 'homeroom', 'klas', 'registerklas']],
            'name' => ['label' => 'Class name', 'required' => false, 'aliases' => ['class name', 'name']],
            'medium' => ['label' => 'Medium', 'required' => false, 'aliases' => ['medium', 'language', 'medium of instruction', 'taal']],
        ];
    }

    public function plan(CsvSheet $sheet, array $mapping, array $options): array
    {
        $grades = GradeLevel::query()->orderBy('sort_order')->get();
        $mediums = Mediums::query()->get();
        $defaultMedium = isset($options['medium_id']) ? $mediums->firstWhere('id', (int) $options['medium_id']) : ($mediums->count() === 1 ? $mediums->first() : null);
        $classes = ClassSchool::query()->whereNull('stream_id')->get();
        $sections = Section::query()->get();
        $rooms = ClassSection::query()->get(['class_id', 'section_id']);
        $seen = [];
        $rows = [];
        foreach ($sheet->rows as $source) {
            $value = fn (string $field) => isset($mapping[$field]) ? trim((string) ($source[$mapping[$field]] ?? '')) : '';
            $notes = [];
            $grade = Values::grade($value('grade'), $grades);
            $letter = mb_strtoupper($value('letter'));
            if ($grade === null) {
                $notes[] = __('Unknown grade ":grade" (use R or 1 to 12).', ['grade' => $value('grade')]);
            }
            if ($letter === '' || ! preg_match('/^[\p{L}\p{N}]{1,20}$/u', $letter)) {
                $notes[] = __('The homeroom letter must be letters or numbers, for example A.');
            }
            $medium = $value('medium') !== '' ? $mediums->first(fn ($row) => mb_strtolower((string) $row->name) === mb_strtolower($value('medium'))) : $defaultMedium;
            if ($medium === null) {
                $notes[] = $value('medium') !== ''
                    ? __('No medium called ":medium". Add it under Mediums first.', ['medium' => $value('medium')])
                    : __('Choose the medium for homerooms that do not name one.');
            }
            $name = $value('name') !== '' ? preg_replace('/\s+/', ' ', $value('name')) : ($grade !== null ? $grade->name : '');
            if (mb_strlen((string) $name) > 128) {
                $notes[] = __('The class name is too long.');
            }
            $label = trim($name.' '.$letter);
            $key = mb_strtolower($label.'|'.($medium->id ?? ''));
            if ($notes === [] && isset($seen[$key])) {
                $notes[] = __('This homeroom is already on line :line.', ['line' => $seen[$key]]);
            }
            $seen[$key] ??= $source[CsvSheet::LINE];
            $row = ['line' => (int) $source[CsvSheet::LINE], 'label' => $label, 'notes' => $notes, 'status' => $notes === [] ? 'create' : 'error'];
            if ($notes === []) {
                $class = $classes->first(fn ($row) => mb_strtolower((string) $row->name) === mb_strtolower((string) $name) && (int) $row->medium_id === (int) $medium->id);
                $section = $sections->first(fn ($row) => mb_strtoupper((string) $row->name) === $letter);
                if ($class !== null && $class->grade_level_id !== null && (int) $class->grade_level_id !== (int) $grade->id) {
                    $row['status'] = 'error';
                    $row['notes'][] = __(':class is already set up as a different grade. Use another class name.', ['class' => $name]);
                } elseif ($class !== null && $section !== null && $rooms->contains(fn ($room) => (int) $room->class_id === (int) $class->id && (int) $room->section_id === (int) $section->id)) {
                    $row['status'] = 'exists';
                } else {
                    if ($class !== null && $class->grade_level_id === null) {
                        $row['notes'][] = __(':class will be set to :grade.', ['class' => $name, 'grade' => $grade->name]);
                    }
                    $row['data'] = ['name' => $name, 'letter' => $letter, 'grade_level_id' => (int) $grade->id, 'medium_id' => (int) $medium->id, 'class_id' => $class?->id];
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function commit(User $actor, array $rows, array $options): array
    {
        $years = SessionYear::query()->pluck('id');
        $created = 0;
        foreach ($rows as $row) {
            if ($row['status'] !== 'create') {
                continue;
            }
            $data = $row['data'];
            $class = $data['class_id'] !== null ? ClassSchool::query()->findOrFail($data['class_id']) : null;
            if ($class === null) {
                // An earlier row of this file may have made the class.
                $class = ClassSchool::query()->whereNull('stream_id')->where('name', $data['name'])->where('medium_id', $data['medium_id'])->first()
                    ?? ClassSchool::query()->forceCreate(['name' => $data['name'], 'medium_id' => $data['medium_id'], 'grade_level_id' => $data['grade_level_id']]);
            }
            if ($class->grade_level_id === null) {
                app(AcademicReferences::class)->map($actor, (int) $class->id, ['grade_level_id' => $data['grade_level_id']]);
            }
            $section = Section::query()->whereRaw('UPPER(name) = ?', [$data['letter']])->first() ?? Section::query()->forceCreate(['name' => $data['letter']]);
            ClassSection::query()->firstOrCreate(['class_id' => $class->id, 'section_id' => $section->id]);
            foreach ($years as $year) {
                ClassSessionConfig::query()->firstOrCreate(['class_id' => $class->id, 'session_year_id' => $year], ['include_terms' => false]);
            }
            $created++;
        }

        return ['created' => $created];
    }
}
