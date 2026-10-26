import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class GroupChatNoticeAction extends StatelessWidget {
  const GroupChatNoticeAction({
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
              me.isModuleOn('groups') != true ||
              me.isModuleOn('chat') != true ||
              !allowsSchoolModule(me, 'groups') ||
              !allowsSchoolModule(me, 'chat')) {
            return const SizedBox.shrink();
          }
          return Align(
            alignment: AlignmentDirectional.centerStart,
            child: TextButton.icon(
              key: ValueKey('staff-group-chat-notice-$noticeId'),
              icon: const Icon(Icons.forum_outlined),
              label: Text(
                SharedUiUtils.getTranslatedLabel(context, 'Activity groups'),
              ),
              onPressed: () =>
                  Navigator.of(context).pushNamed(Routes.activityGroups),
            ),
          );
        },
      );
}
