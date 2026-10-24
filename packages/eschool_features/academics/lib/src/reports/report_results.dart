import '../localization/academic_text.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';

String reportPercent(int? basisPoints) => basisPoints == null
    ? '—'
    : '${basisPoints ~/ 100}.${(basisPoints % 100).toString().padLeft(2, '0')}%';
String reportStatus(String status) => switch (status) {
  'grade_signed' => 'Grade head signed',
  'approved' => 'PDFs queued',
  'ready' => 'Ready for release',
  'released' => 'Released',
  'draft' => 'Draft',
  'partial' => 'Partial',
  'ungraded' => 'Ungraded',
  'excused' => 'Excused',
  'absent' => 'Absent',
  'catch_up' => 'Catch-up',
  'complete' => 'Complete',
  _ => 'Unavailable',
};

class ReportResults extends StatelessWidget {
  const ReportResults({required this.payload, super.key});
  final AcademicReportPayload payload;
  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(payload.learnerName, style: Theme.of(context).textTheme.titleLarge),
      Text(
        '${payload.homeroomName} · ${payload.yearName} · ${payload.termName ?? 'Annual report'}',
      ),
      if (payload.provisional) const AcademicText('Provisional running report'),
      for (final subject in payload.subjects)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: Wrap(
            spacing: 12,
            runSpacing: 4,
            children: [
              Text(
                subject.subjectName,
                style: Theme.of(context).textTheme.titleMedium,
              ),
              Text(reportPercent(subject.percentageBp)),
              Text('Rating ${subject.ratingLevel ?? '—'}'),
              Text(
                '${reportStatus(subject.resultStatus)} · Coverage ${reportPercent(subject.coverageBp)}',
              ),
            ],
          ),
        ),
      Text(
        'Captured days: ${payload.attendance.daysRecorded} · Late: ${payload.attendance.lateDays} · Excused: ${payload.attendance.excusedDays}',
      ),
    ],
  );
}
