import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';

bool teachingContextCurrent(
  MeContext pinned,
  MeContext? current,
  String module,
) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.featureFlags[module] == true &&
    current.isModuleOn(module);

class TeachingSessionBoundary extends StatefulWidget {
  const TeachingSessionBoundary({
    required this.pinned,
    required this.module,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final String module;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<TeachingSessionBoundary> createState() =>
      _TeachingSessionBoundaryState();
}

class _TeachingSessionBoundaryState extends State<TeachingSessionBoundary> {
  int _epoch = 0;
  bool _withdrawn = false;
  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (state is MeContextLoading) _epoch++;
          if (state is! MeContextLoading &&
              !teachingContextCurrent(
                widget.pinned,
                state.context,
                widget.module,
              )) {
            _withdrawn = true;
          }
        },
        builder: (context, state) {
          if (state is MeContextLoading) {
            return ScreenScaffold(
              title: academicText(context, 'Teaching workspace'),
              body: const Center(child: CircularProgressIndicator()),
            );
          }
          if (_withdrawn ||
              !teachingContextCurrent(
                widget.pinned,
                state.context,
                widget.module,
              )) {
            return ScreenScaffold(
              title: academicText(context, 'Teaching workspace'),
              body: const Center(
                child: AcademicText(
                  'Reopen the teaching workspace to check your access.',
                ),
              ),
            );
          }
          final epoch = _epoch;
          final session = context.read<MeContextCubit>();
          return KeyedSubtree(
            key: ValueKey(epoch),
            child: Builder(
              builder: (context) => widget.builder(
                context,
                () =>
                    mounted &&
                    !_withdrawn &&
                    epoch == _epoch &&
                    session.state is! MeContextLoading &&
                    teachingContextCurrent(
                      widget.pinned,
                      session.context,
                      widget.module,
                    ),
              ),
            ),
          );
        },
      );
}
