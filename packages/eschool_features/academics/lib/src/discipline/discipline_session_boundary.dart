import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool disciplineAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    allowsSchoolModule(current, 'discipline');

/// Every context refresh remounts the reader, even if roles stayed identical:
/// assignments and individual permissions may have been revoked on the server.
class DisciplineSessionBoundary extends StatefulWidget {
  const DisciplineSessionBoundary({
    required this.pinned,
    required this.child,
    super.key,
  });
  final MeContext pinned;
  final Widget child;
  @override
  State<DisciplineSessionBoundary> createState() =>
      _DisciplineSessionBoundaryState();
}

class _DisciplineSessionBoundaryState extends State<DisciplineSessionBoundary> {
  int _generation = 0;
  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<MeContextCubit, MeContextState>(
    listener: (context, state) {
      if (state is MeContextLoading) _generation++;
    },
    builder: (context, state) {
      if (state is MeContextLoading) {
        return ScreenScaffold(
          title: academicText(context, 'School discipline'),
          body: const Center(child: CircularProgressIndicator()),
        );
      }
      if (disciplineAccessStillCurrent(widget.pinned, state.context)) {
        return KeyedSubtree(key: ValueKey(_generation), child: widget.child);
      }
      return ScreenScaffold(
        title: academicText(context, 'School discipline'),
        body: Center(
          child: AcademicText('Reopen school discipline to check your access.'),
        ),
      );
    },
  );
}
