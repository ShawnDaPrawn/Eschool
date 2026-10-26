import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool pathwayAccessStillCurrent(MeContext pinned, MeContext? current) =>
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current != null &&
    (current.roles.contains('Parent') || current.roles.contains('Student')) &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    current.isModuleOn('pathway') &&
    allowsSchoolModule(current, 'pathway') &&
    current.isModuleReadOnly('pathway') == pinned.isModuleReadOnly('pathway');

class PathwaySessionBoundary extends StatefulWidget {
  const PathwaySessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<PathwaySessionBoundary> createState() => _PathwaySessionBoundaryState();
}

class _PathwaySessionBoundaryState extends State<PathwaySessionBoundary> {
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
              !pathwayAccessStillCurrent(widget.pinned, state.context)) {
            _generation++;
          }
        },
        builder: (context, state) {
          final generation = _generation;
          bool active() =>
              mounted &&
              generation == _generation &&
              context.read<MeContextCubit>().state is MeContextLoaded &&
              pathwayAccessStillCurrent(
                widget.pinned,
                context.read<MeContextCubit>().context,
              );
          return active()
              ? KeyedSubtree(
                  key: ValueKey(generation),
                  child: widget.builder(context, active),
                )
              : ScreenScaffold(
                  title: academicText(context, 'Subject choice and pathways'),
                  body: Center(
                    child: state is MeContextLoading
                        ? const CircularProgressIndicator()
                        : const AcademicText(
                            'Your pathway access changed. Reopen this screen.',
                          ),
                  ),
                );
        },
      );
}
