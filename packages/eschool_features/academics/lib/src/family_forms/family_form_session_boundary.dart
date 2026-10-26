import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool familyFormAccessStillCurrent(MeContext pinned, MeContext? current) =>
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    current.isModuleOn('family_forms') &&
    allowsSchoolModule(current, 'family_forms') &&
    current.isModuleReadOnly('family_forms') ==
        pinned.isModuleReadOnly('family_forms');

class FamilyFormSessionBoundary extends StatefulWidget {
  const FamilyFormSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<FamilyFormSessionBoundary> createState() =>
      _FamilyFormSessionBoundaryState();
}

class _FamilyFormSessionBoundaryState extends State<FamilyFormSessionBoundary> {
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
              !familyFormAccessStillCurrent(widget.pinned, state.context)) {
            _generation++;
          }
        },
        builder: (context, state) {
          final generation = _generation;
          bool active() =>
              mounted &&
              generation == _generation &&
              context.read<MeContextCubit>().state is MeContextLoaded &&
              familyFormAccessStillCurrent(
                widget.pinned,
                context.read<MeContextCubit>().context,
              );
          return active()
              ? KeyedSubtree(
                  key: ValueKey(generation),
                  child: widget.builder(context, active),
                )
              : ScreenScaffold(
                  title: academicText(context, 'Family consent and health'),
                  body: Center(
                    child: state is MeContextLoading
                        ? const CircularProgressIndicator()
                        : const AcademicText(
                            'Your family access changed. Reopen this screen.',
                          ),
                  ),
                );
        },
      );
}
