import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool riskAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    allowsSchoolModule(current, 'early_warning');

/// Every context refresh remounts the reader, even if roles stayed identical:
/// assignments and individual permissions may have been revoked on the server.
class RiskSessionBoundary extends StatefulWidget {
  const RiskSessionBoundary({
    required this.pinned,
    required this.child,
    super.key,
  });
  final MeContext pinned;
  final Widget child;
  @override
  State<RiskSessionBoundary> createState() => _RiskSessionBoundaryState();
}

class _RiskSessionBoundaryState extends State<RiskSessionBoundary> {
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
          title: academicText(context, 'Staff follow-up'),
          body: const Center(child: CircularProgressIndicator()),
        );
      }
      if (riskAccessStillCurrent(widget.pinned, state.context)) {
        return KeyedSubtree(key: ValueKey(_generation), child: widget.child);
      }
      return ScreenScaffold(
        title: academicText(context, 'Staff follow-up'),
        body: Center(
          child: AcademicText('Reopen staff follow-up to check your access.'),
        ),
      );
    },
  );
}
