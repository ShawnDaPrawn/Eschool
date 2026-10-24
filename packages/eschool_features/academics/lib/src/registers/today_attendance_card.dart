import '../localization/academic_text.dart';
import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import 'register_repository.dart';
import 'family_attendance_screen.dart';

class TodayAttendanceCard extends StatefulWidget {
  const TodayAttendanceCard({
    required this.repository,
    required this.learner,
    required this.scope,
    this.learnerName,
    this.onOpen,
    super.key,
  });
  final RegisterRepository repository;
  final int learner;
  final String scope;
  final String? learnerName;
  final VoidCallback? onOpen;
  @override
  State<TodayAttendanceCard> createState() => _TodayAttendanceCardState();
}

class _TodayAttendanceCardState extends State<TodayAttendanceCard>
    with WidgetsBindingObserver {
  late Future<AcademicAttendanceTodayData> _future = widget.repository.today(
    widget.learner,
  );
  Timer? _timer;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _timer = Timer.periodic(const Duration(minutes: 1), (_) => _reload());
  }

  void _reload() {
    if (mounted) {
      setState(() => _future = widget.repository.today(widget.learner));
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _reload();
  }

  @override
  void didUpdateWidget(covariant TodayAttendanceCard old) {
    super.didUpdateWidget(old);
    if (old.scope != widget.scope ||
        old.learner != widget.learner ||
        old.repository.schoolId != widget.repository.schoolId) {
      _future = widget.repository.today(widget.learner);
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: FutureBuilder<AcademicAttendanceTodayData>(
        future: _future,
        builder: (context, snapshot) {
          final data = snapshot.connectionState == ConnectionState.done
              ? snapshot.data
              : null;
          final homeroom = data?.entries
              .where((row) => row.kind == 'homeroom')
              .toList();
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.learnerName == null
                    ? "Today's attendance"
                    : "${widget.learnerName} · Today's attendance",
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 8),
              if (snapshot.connectionState != ConnectionState.done)
                const LinearProgressIndicator()
              else if (snapshot.hasError)
                TextButton(
                  onPressed: _reload,
                  child: const AcademicText('Reload attendance'),
                )
              else if (homeroom == null || homeroom.isEmpty)
                const AcademicText('No morning register has been captured yet.')
              else
                Text(
                  attendanceStateLabel(homeroom.first.state),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
              if (widget.onOpen != null)
                TextButton(
                  onPressed: widget.onOpen,
                  child: const AcademicText('Attendance and QR'),
                ),
            ],
          );
        },
      ),
    ),
  );
}
