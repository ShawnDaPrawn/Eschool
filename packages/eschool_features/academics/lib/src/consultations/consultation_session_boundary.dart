import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool consultationAccessStillCurrent(MeContext pinned, MeContext? current) =>
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    current.isModuleOn('consultations') &&
    allowsSchoolModule(current, 'consultations') &&
    current.isModuleReadOnly('consultations') ==
        pinned.isModuleReadOnly('consultations');

class ConsultationSessionBoundary extends StatefulWidget {
  const ConsultationSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext context, bool Function() active) builder;
  @override
  State<ConsultationSessionBoundary> createState() =>
      _ConsultationSessionBoundaryState();
}

class _ConsultationSessionBoundaryState
    extends State<ConsultationSessionBoundary> {
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
              !consultationAccessStillCurrent(widget.pinned, state.context)) {
            _generation++;
          }
        },
        builder: (context, state) {
          final generation = _generation;
          bool active() =>
              mounted &&
              generation == _generation &&
              context.read<MeContextCubit>().state is MeContextLoaded &&
              consultationAccessStillCurrent(
                widget.pinned,
                context.read<MeContextCubit>().context,
              );
          if (active()) {
            return KeyedSubtree(
              key: ValueKey(generation),
              child: widget.builder(context, active),
            );
          }
          return ScreenScaffold(
            title: academicText(context, 'Polls, surveys and elections'),
            body: Center(
              child: state is MeContextLoading
                  ? const CircularProgressIndicator()
                  : const AcademicText(
                      'Your consultation access changed. Reopen this screen.',
                    ),
            ),
          );
        },
      );
}
