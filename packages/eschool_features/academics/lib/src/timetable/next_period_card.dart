import '../localization/academic_text.dart';
import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import 'timetable_repository.dart';

/// The home card is keyed by school/profile and never reuses another child's data.
class NextPeriodCard extends StatefulWidget {
  const NextPeriodCard({
    required this.repository,
    required this.scope,
    this.learner,
    this.learnerName,
    this.onOpen,
    super.key,
  });
  final TimetableRepository repository;
  final String scope;
  final int? learner;
  final String? learnerName;
  final VoidCallback? onOpen;
  @override
  State<NextPeriodCard> createState() => _NextPeriodCardState();
}

class _NextPeriodCardState extends State<NextPeriodCard>
    with WidgetsBindingObserver {
  Timer? _refresh;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _refresh = Timer.periodic(const Duration(minutes: 1), (_) => _reload());
  }

  void _reload() {
    if (mounted) {
      setState(() => _next = widget.repository.next(learner: widget.learner));
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _reload();
  }

  @override
  void dispose() {
    _refresh?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  late Future<AcademicTimetableNextData> _next = widget.repository.next(
    learner: widget.learner,
  );
  @override
  void didUpdateWidget(covariant NextPeriodCard old) {
    super.didUpdateWidget(old);
    if (old.scope != widget.scope ||
        old.repository.schoolId != widget.repository.schoolId ||
        old.learner != widget.learner) {
      _next = widget.repository.next(learner: widget.learner);
    }
  }

  @override
  Widget build(
    BuildContext context,
  ) => FutureBuilder<AcademicTimetableNextData>(
    future: _next,
    builder: (context, snapshot) {
      final title = widget.learnerName == null
          ? 'Next period'
          : '${widget.learnerName} · Next period';
      final lesson = snapshot.connectionState == ConnectionState.done
          ? snapshot.data?.lesson
          : null;
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              if (snapshot.connectionState != ConnectionState.done)
                const LinearProgressIndicator()
              else if (snapshot.hasError)
                TextButton(
                  onPressed: () => setState(
                    () =>
                        _next = widget.repository.next(learner: widget.learner),
                  ),
                  child: const AcademicText('Reload timetable'),
                )
              else if (lesson == null)
                const AcademicText('No upcoming period is scheduled.')
              else ...[
                Text('${lesson.date} · ${lesson.startsAt} · ${lesson.title}'),
                const SizedBox(height: 4),
                Text(
                  '${lesson.roomName}${lesson.building == null ? '' : ' · ${lesson.building}'}',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                Text(lesson.teacherName),
              ],
              if (widget.onOpen != null)
                TextButton(
                  onPressed: widget.onOpen,
                  child: const AcademicText('View week'),
                ),
            ],
          ),
        ),
      );
    },
  );
}
