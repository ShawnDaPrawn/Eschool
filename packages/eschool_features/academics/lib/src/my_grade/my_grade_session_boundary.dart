import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';

bool myGradeContextCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.featureFlags['my_grade'] == true &&
    current.isModuleOn('my_grade');

class MyGradeSessionBoundary extends StatefulWidget {
  const MyGradeSessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(BuildContext, bool Function()) builder;
  @override
  State<MyGradeSessionBoundary> createState() => _MyGradeSessionBoundaryState();
}

class _MyGradeSessionBoundaryState extends State<MyGradeSessionBoundary> {
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
              title: academicText(context, 'My grade'),
              body: const Center(child: CircularProgressIndicator()),
            );
          }
          if (!myGradeContextCurrent(widget.pinned, state.context)) {
            return ScreenScaffold(
              title: academicText(context, 'My grade'),
              body: const Center(
                child: AcademicText('Reopen My grade to check your access.'),
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
                    myGradeContextCurrent(widget.pinned, session.context),
              ),
            ),
          );
        },
      );
}
