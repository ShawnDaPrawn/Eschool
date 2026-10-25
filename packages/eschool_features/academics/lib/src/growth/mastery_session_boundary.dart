import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

/// A role/guardian/module refresh can revoke access without switching accounts.
bool masteryAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    current.isFeatureOn('mastery') &&
    allowsSchoolModule(current, 'mastery');

class MasterySessionBoundary extends StatefulWidget {
  const MasterySessionBoundary({
    required this.pinned,
    required this.child,
    super.key,
  });
  final MeContext pinned;
  final Widget child;
  @override
  State<MasterySessionBoundary> createState() => _MasterySessionBoundaryState();
}

class _MasterySessionBoundaryState extends State<MasterySessionBoundary> {
  int _generation = 0;
  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<MeContextCubit, MeContextState>(
    listener: (context, state) {
      // Permissions and dated allocations may change without changing the role
      // list. Even a coalesced loading/loaded frame must recreate the reader so
      // its fresh configuration request reauthorizes the current capability.
      if (state is MeContextLoading) {
        _generation++;
      }
    },
    builder: (context, state) {
      if (state is MeContextLoading) {
        return ScreenScaffold(
          title: academicText(context, 'Mastery map'),
          body: const Center(child: CircularProgressIndicator()),
        );
      }
      if (masteryAccessStillCurrent(widget.pinned, state.context)) {
        return KeyedSubtree(key: ValueKey(_generation), child: widget.child);
      }
      return ScreenScaffold(
        title: academicText(context, 'Mastery map'),
        body: Center(
          child: AcademicText(
            'Mastery is unavailable for this account or school.',
          ),
        ),
      );
    },
  );
}
