import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';

bool examContextCurrent(MeContext pinned, MeContext? current, bool staff) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.roles.contains(staff ? 'Teacher' : 'Student') &&
    current.isModuleOn('exam_coach');

class ExamSessionBoundary extends StatefulWidget {
  const ExamSessionBoundary({
    required this.pinned,
    required this.staff,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final bool staff;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<ExamSessionBoundary> createState() => _ExamSessionBoundaryState();
}

class _ExamSessionBoundaryState extends State<ExamSessionBoundary> {
  int _epoch = 0;
  bool _withdrawn = false;
  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (state is MeContextLoading) _epoch++;
          if (state is! MeContextLoading &&
              !examContextCurrent(widget.pinned, state.context, widget.staff)) {
            _withdrawn = true;
          }
        },
        builder: (context, state) {
          if (state is MeContextLoading) {
            return ScreenScaffold(
              title: academicText(context, 'Exam Coach'),
              body: const Center(child: CircularProgressIndicator()),
            );
          }
          if (_withdrawn ||
              !examContextCurrent(widget.pinned, state.context, widget.staff)) {
            return ScreenScaffold(
              title: academicText(context, 'Exam Coach'),
              body: const Center(
                child: AcademicText(
                  'Reopen the Exam Coach to check your access.',
                ),
              ),
            );
          }
          final epoch = _epoch;
          final session = context.read<MeContextCubit>();
          return KeyedSubtree(
            key: ValueKey(epoch),
            child: Builder(
              builder: (context) => widget.builder(
                context,
                () =>
                    mounted &&
                    !_withdrawn &&
                    epoch == _epoch &&
                    session.state is! MeContextLoading &&
                    examContextCurrent(
                      widget.pinned,
                      session.context,
                      widget.staff,
                    ),
              ),
            ),
          );
        },
      );
}
