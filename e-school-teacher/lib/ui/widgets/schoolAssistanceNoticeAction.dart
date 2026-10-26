import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Recipient/school identity is pinned; the destination reauthorizes current sources.
class SchoolAssistanceNoticeAction extends StatelessWidget {
  const SchoolAssistanceNoticeAction({
    required this.noticeId,
    required this.schoolId,
    required this.readerId,
    super.key,
  });
  final int noticeId;
  final int? schoolId, readerId;
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          final me = state.context;
          if (state is! MeContextLoaded ||
              me?.currentSchoolId == null ||
              me?.user?.id == null ||
              schoolId != me?.currentSchoolId ||
              readerId != me?.user?.id ||
              !me!.roles.contains('Teacher') ||
              !me.isModuleOn('ask_school') ||
              !allowsSchoolModule(me, 'ask_school'))
            return const SizedBox.shrink();
          return Align(
            alignment: AlignmentDirectional.centerStart,
            child: TextButton.icon(
              key: ValueKey('assistance-notice-$noticeId'),
              icon: const Icon(Icons.support_agent_outlined),
              label: Text(
                SharedUiUtils.getTranslatedLabel(
                  context,
                  'School support office',
                ),
              ),
              onPressed: () =>
                  Navigator.of(context).pushNamed(Routes.schoolAssistance),
            ),
          );
        },
      );
}
