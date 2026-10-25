import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';

bool weeklyDigestAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    listEquals(current.children, pinned.children) &&
    current.approval == pinned.approval &&
    current.isModuleOn('digest') &&
    allowsSchoolModule(current, 'digest') &&
    current.isModuleReadOnly('digest') == pinned.isModuleReadOnly('digest');

class WeeklyDigestSessionBoundary extends StatefulWidget {
  const WeeklyDigestSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(bool Function() active) builder;
  @override
  State<WeeklyDigestSessionBoundary> createState() =>
      _WeeklyDigestSessionBoundaryState();
}

class _WeeklyDigestSessionBoundaryState
    extends State<WeeklyDigestSessionBoundary> {
  int _generation = 0;
  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<MeContextCubit, MeContextState>(
    listener: (_, state) {
      if (state is! MeContextLoaded ||
          !weeklyDigestAccessStillCurrent(widget.pinned, state.context))
        _generation++;
    },
    builder: (context, state) {
      final generation = _generation;
      bool active() =>
          mounted &&
          generation == _generation &&
          context.read<MeContextCubit>().state is MeContextLoaded &&
          weeklyDigestAccessStillCurrent(
            widget.pinned,
            context.read<MeContextCubit>().context,
          );
      return active()
          ? KeyedSubtree(
              key: ValueKey(generation),
              child: widget.builder(active),
            )
          : ScreenScaffold(
              title: academicText(context, 'Weekly learning update'),
              body: Center(
                child: state is MeContextLoading
                    ? const CircularProgressIndicator()
                    : const AcademicText(
                        'Your weekly update access changed. Reopen this screen.',
                      ),
              ),
            );
    },
  );
}
