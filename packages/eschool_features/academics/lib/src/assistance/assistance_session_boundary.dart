import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool assistanceAccessStillCurrent(MeContext pinned, MeContext? current) =>
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    current.isModuleOn('ask_school') &&
    allowsSchoolModule(current, 'ask_school') &&
    current.isModuleReadOnly('ask_school') ==
        pinned.isModuleReadOnly('ask_school');

/// Every context loading event retires readers even when the payload coalesces.
class AssistanceSessionBoundary extends StatefulWidget {
  const AssistanceSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<AssistanceSessionBoundary> createState() =>
      _AssistanceSessionBoundaryState();
}

class _AssistanceSessionBoundaryState extends State<AssistanceSessionBoundary> {
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
              !assistanceAccessStillCurrent(widget.pinned, state.context)) {
            _generation++;
          }
        },
        builder: (context, state) {
          final generation = _generation;
          bool active() =>
              mounted &&
              generation == _generation &&
              context.read<MeContextCubit>().state is MeContextLoaded &&
              assistanceAccessStillCurrent(
                widget.pinned,
                context.read<MeContextCubit>().context,
              );
          return active()
              ? KeyedSubtree(
                  key: ValueKey(generation),
                  child: widget.builder(context, active),
                )
              : ScreenScaffold(
                  title: academicText(context, 'Ask the school'),
                  body: Center(
                    child: state is MeContextLoading
                        ? const CircularProgressIndicator()
                        : const AcademicText(
                            'Your school access changed. Reopen this screen.',
                          ),
                  ),
                );
        },
      );
}
