import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';

bool tutorAccessStillCurrent(MeContext pinned, MeContext? current) =>
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    (current.roles.contains('Parent') ||
        (current.roles.contains('Student') &&
            current.approval?.isApproved == true)) &&
    current.isModuleOn('tutor') &&
    allowsSchoolModule(current, 'tutor') &&
    current.isModuleReadOnly('tutor') == pinned.isModuleReadOnly('tutor');

/// Refresh always destroys the prior reader, including equal coalesced profiles.
class TutorSessionBoundary extends StatefulWidget {
  const TutorSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<TutorSessionBoundary> createState() => _TutorSessionBoundaryState();
}

class _TutorSessionBoundaryState extends State<TutorSessionBoundary> {
  int _generation = 0;
  @override
  void dispose() {
    _generation++;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (state is! MeContextLoaded ||
              !tutorAccessStillCurrent(widget.pinned, state.context))
            _generation++;
        },
        builder: (context, state) {
          final epoch = _generation;
          bool active() =>
              mounted &&
              epoch == _generation &&
              context.read<MeContextCubit>().state is MeContextLoaded &&
              tutorAccessStillCurrent(
                widget.pinned,
                context.read<MeContextCubit>().context,
              );
          if (active())
            return KeyedSubtree(
              key: ValueKey(epoch),
              child: widget.builder(context, active),
            );
          return ScreenScaffold(
            title: academicText(context, 'Ask your tutor'),
            body: Center(
              child: state is MeContextLoading
                  ? const CircularProgressIndicator()
                  : const AcademicText(
                      'Reopen the tutor to check your access.',
                    ),
            ),
          );
        },
      );
}
