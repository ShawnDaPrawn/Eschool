import '../localization/academic_text.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'register_repository.dart';

String attendanceStateLabel(String value) => switch (value) {
  'present' => 'Present',
  'absent' => 'Absent',
  'late' => 'Late',
  'excused' => 'Excused',
  'first_half' => 'First half absent',
  'second_half' => 'Second half absent',
  'holiday' => 'Holiday',
  'unmarked' => 'Unmarked',
  _ => value,
};

class FamilyAttendanceScreen extends StatefulWidget {
  const FamilyAttendanceScreen({
    required this.repository,
    required this.learner,
    required this.learnerName,
    super.key,
  });
  final RegisterRepository repository;
  final int learner;
  final String learnerName;
  @override
  State<FamilyAttendanceScreen> createState() => _FamilyAttendanceScreenState();
}

class _FamilyAttendanceData {
  const _FamilyAttendanceData(this.today, this.qr);
  final AcademicAttendanceTodayData today;
  final AcademicRegisterQrData qr;
}

class _FamilyAttendanceScreenState extends State<FamilyAttendanceScreen> {
  Future<_FamilyAttendanceData> _load() async {
    final values = await Future.wait<Object>([
      widget.repository.today(widget.learner),
      widget.repository.qr(widget.learner),
    ]);
    return _FamilyAttendanceData(
      values[0] as AcademicAttendanceTodayData,
      values[1] as AcademicRegisterQrData,
    );
  }

  late Future<_FamilyAttendanceData> _future = _load();
  int _generation = 0;
  bool _rotating = false;
  String? _error;
  @override
  void didUpdateWidget(covariant FamilyAttendanceScreen old) {
    super.didUpdateWidget(old);
    if (old.learner != widget.learner ||
        old.repository.schoolId != widget.repository.schoolId) {
      _generation++;
      _rotating = false;
      _error = null;
      _future = _load();
    }
  }

  Future<void> _rotate() async {
    final generation = _generation;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const AcademicText('Replace learner QR code?'),
        content: const AcademicText(
          'Previous copies will stop working. Give the new code to the school before the next scan.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const AcademicText('Keep current code'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const AcademicText('Replace code'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted || generation != _generation) return;
    setState(() {
      _rotating = true;
      _error = null;
    });
    try {
      await widget.repository.rotate(widget.learner);
    } catch (_) {
      if (mounted && generation == _generation) {
        _error =
            'Could not replace the code. Check your connection and try again.';
      }
    }
    if (mounted && generation == _generation) {
      setState(() {
        _rotating = false;
        _future = _load();
      });
    }
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: '${widget.learnerName} · Attendance',
    body: _rotating
        ? const Center(child: CircularProgressIndicator())
        : FutureBuilder<_FamilyAttendanceData>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Center(child: CircularProgressIndicator());
              }
              if (snapshot.hasError || !snapshot.hasData) {
                return Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const AcademicText(
                        'Attendance is unavailable. Check your access and try again.',
                      ),
                      TextButton(
                        onPressed: () => setState(() => _future = _load()),
                        child: const AcademicText('Try again'),
                      ),
                    ],
                  ),
                );
              }
              final data = snapshot.data!;
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Text(
                    data.today.onDate,
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  if (_error != null)
                    Text(
                      _error!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  if (data.today.entries.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 16),
                      child: AcademicText(
                        'No attendance has been captured for today.',
                      ),
                    ),
                  for (final entry in data.today.entries)
                    Card(
                      child: ListTile(
                        leading: Icon(
                          entry.kind == 'homeroom'
                              ? Icons.groups_outlined
                              : Icons.schedule,
                        ),
                        title: Text(entry.label),
                        subtitle: Text(attendanceStateLabel(entry.state)),
                        trailing: entry.amendedAt == null
                            ? null
                            : const Tooltip(
                                message: 'Attendance amended',
                                child: Icon(Icons.edit_outlined),
                              ),
                      ),
                    ),
                  const SizedBox(height: 24),
                  AcademicText(
                    'Learner attendance QR',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 8),
                    child: AcademicText(
                      'Show this code to the teacher for a register scan. Only authorized staff can record attendance.',
                    ),
                  ),
                  Center(
                    child: Semantics(
                      label: 'Attendance QR for ${widget.learnerName}',
                      image: true,
                      child: QrImageView(
                        data: data.qr.qrCode,
                        version: QrVersions.auto,
                        size: 240,
                        backgroundColor: Colors.white,
                      ),
                    ),
                  ),
                  Center(
                    child: TextButton(
                      onPressed: _rotate,
                      child: const AcademicText('Replace QR code'),
                    ),
                  ),
                ],
              );
            },
          ),
  );
}
