import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

/// A role/guardian/module refresh can revoke access without switching accounts.
bool masteryAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    current.approval == pinned.approval &&
    listEquals(current.children, pinned.children) &&
    current.isFeatureOn('mastery') &&
    allowsSchoolModule(current, 'mastery');

class MasterySessionBoundary extends StatelessWidget {
  const MasterySessionBoundary({
    required this.pinned,
    required this.child,
    super.key,
  });
  final MeContext pinned;
  final Widget child;
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) =>
            masteryAccessStillCurrent(pinned, state.context)
            ? child
            : ScreenScaffold(
                title: academicText(context, 'Mastery map'),
                body: Center(
                  child: AcademicText(
                    'Mastery is unavailable for this account or school.',
                  ),
                ),
              ),
      );
}
