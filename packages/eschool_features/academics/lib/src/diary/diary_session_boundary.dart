import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';

/// The diary belongs to one approved learner at one school. Anything else
/// (another account, school or role, a withdrawn approval, the module off)
/// closes it.
bool diaryContextCurrent(MeContext pinned, MeContext? current) =>
    (pinned.user?.id ?? 0) > 0 &&
    (pinned.currentSchoolId ?? 0) > 0 &&
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.roles.contains('Student') &&
    current.approval?.isApproved == true &&
    current.isModuleOn('diary');

class DiarySessionBoundary extends StatefulWidget {
  const DiarySessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<DiarySessionBoundary> createState() => _DiarySessionBoundaryState();
}

class _DiarySessionBoundaryState extends State<DiarySessionBoundary> {
  int _epoch = 0;
  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (state is MeContextLoading) _epoch++;
        },
        builder: (context, state) {
          if (state is MeContextLoading) {
            return ScreenScaffold(
              title: academicText(context, 'My diary'),
              body: const Center(child: CircularProgressIndicator()),
            );
          }
          if (!diaryContextCurrent(widget.pinned, state.context)) {
            return ScreenScaffold(
              title: academicText(context, 'My diary'),
              body: const Center(
                child: AcademicText('Reopen your diary to check your access.'),
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
                    epoch == _epoch &&
                    session.state is! MeContextLoading &&
                    diaryContextCurrent(widget.pinned, session.context),
              ),
            ),
          );
        },
      );
}
