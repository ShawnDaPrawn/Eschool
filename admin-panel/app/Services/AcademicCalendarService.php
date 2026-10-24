<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ExamTimetable;
use App\Models\Holiday;
use App\Models\SessionYear;
use App\Services\Academics\TermBreaks;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AcademicCalendarService
{
    public function generate(array $classIds): string
    {
        $sessionYear = $this->getSessionYear();

        $termBreaks = $this->getTermBreaks($sessionYear);
        $holidays = $this->getHolidays($sessionYear->id);
        $events = $this->getEvents($sessionYear->id);
        $exams = $this->getExams($sessionYear, $classIds);

        $allItems = $holidays
            ->merge($termBreaks)
            ->merge($events)
            ->merge($exams)
            ->sortBy('date')
            ->values();

        $calendarItemsByMonth = $allItems->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('F Y');
        });

        return $this->generatePdf($sessionYear->name, $calendarItemsByMonth);
    }

    private function getSessionYear()
    {
        $sessionYearId = getSettings('session_year')['session_year'];

        $sessionYear = SessionYear::query()
            ->select('id', 'name', 'start_date', 'end_date')
            ->where('id', $sessionYearId)
            ->first();

        if (! $sessionYear) {
            throw new \Exception('Session year not found.');
        }

        return $sessionYear;
    }

    private function getTermBreaks($sessionYear): Collection
    {
        return collect(app(TermBreaks::class)->forYear($sessionYear->id))
            ->map(fn ($break) => (object) ['date' => $break['start'], 'type' => 'holiday',
                'title' => 'Term Break: '.$break['start'].' - '.$break['end']]);
    }

    private function getHolidays(int $sessionYearId): Collection
    {
        return Holiday::where('session_year_id', $sessionYearId)
            ->orderBy('date')
            ->get()
            ->map(fn ($item) => (object) [
                'date' => $item->date,
                'type' => 'holiday',
                'title' => $item->title,
            ])->toBase();
    }

    private function getEvents(int $sessionYearId): Collection
    {
        return Event::where('session_year_id', $sessionYearId)
            ->orderBy('start_date')
            ->get()
            ->map(fn ($item) => (object) [
                'date' => $item->start_date,
                'type' => 'event',
                'title' => $item->title,
            ])->toBase();
    }

    private function getExams($sessionYear, array $classIds): Collection
    {
        return ExamTimetable::whereBetween('date', [
            $sessionYear->start_date,
            $sessionYear->end_date,
        ])
            ->whereIn('class_id', $classIds)
            ->orderBy('date')
            ->get()
            ->map(fn ($item) => (object) [
                'date' => $item->date,
                'type' => 'exam',
                'title' => $item->exam_name ?? 'Exam',
            ])->toBase();
    }

    private function generatePdf(string $reportYear, $calendarItemsByMonth): string
    {
        $data = [
            'report_year' => $reportYear,
            'calendar_items_by_month' => $calendarItemsByMonth,
            'school_name' => config('app.name'),
            'school_address' => getSettings('school_address')['school_address'] ?? null,
            'logo' => public_path('/storage/'.schoolSetting('logo2')),
        ];

        $pdf = Pdf::loadView('academic_calendar.academic_calendar_pdf', $data);

        return base64_encode($pdf->output());
    }
}
