<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class AcademicAssessmentPublished extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $subject, private readonly int $grade, private readonly int $year, private readonly int $term)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.assessment_published';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['subject_id' => $this->subject, 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'term_id' => $this->term];
    }
}
