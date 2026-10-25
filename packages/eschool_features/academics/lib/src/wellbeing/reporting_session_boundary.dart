import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool reportingAccessStillCurrent(MeContext pinned, MeContext? current) =>
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    allowsSchoolModule(current, 'wellbeing') &&
    current.isModuleReadOnly('wellbeing') ==
        pinned.isModuleReadOnly('wellbeing');

/// Guardian and own-report grants can change while account, school and roles stay identical.
/// The reader and its request guard belong to one authorization generation.
class ReportingSessionBoundary extends StatefulWidget {
  const ReportingSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<ReportingSessionBoundary> createState() =>
      _ReportingSessionBoundaryState();
}

class _ReportingSessionBoundaryState extends State<ReportingSessionBoundary> {
  int _generation = 0;
  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<MeContextCubit, MeContextState>(
    listener: (_, state) {
      // Observe every event even when Loading/Loaded coalesce before a frame.
      if (state is! MeContextLoaded ||
          !reportingAccessStillCurrent(widget.pinned, state.context)) {
        _generation++;
      }
    },
    builder: (context, state) {
      final generation = _generation;
      final pinned = widget.pinned;
      bool active() =>
          mounted &&
          generation == _generation &&
          context.read<MeContextCubit>().state is MeContextLoaded &&
          reportingAccessStillCurrent(
            pinned,
            context.read<MeContextCubit>().context,
          );
      if (active()) {
        return KeyedSubtree(
          key: ValueKey(generation),
          child: widget.builder(context, active),
        );
      }
      return ScreenScaffold(
        title: academicText(context, 'Report a concern'),
        body: Center(
          child: state is MeContextLoading
              ? const CircularProgressIndicator()
              : const AcademicText(
                  'Your reporting access changed. Reopen this area after refreshing your account.',
                ),
        ),
      );
    },
  );
}
