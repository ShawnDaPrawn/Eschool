import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Opens the current authorized list; notice payloads never carry chat bodies.
class FamilyGroupChatNoticeAction extends StatelessWidget {
  const FamilyGroupChatNoticeAction({
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
          final parent = context.watch<AuthCubit>().isParent();
          if (state is! MeContextLoaded ||
              me?.currentSchoolId == null ||
              me?.user?.id == null ||
              schoolId != me?.currentSchoolId ||
              readerId != me?.user?.id ||
              !me!.roles.contains(parent ? 'Parent' : 'Student') ||
              me.isModuleOn('groups') != true ||
              me.isModuleOn('chat') != true ||
              !allowsSchoolModule(me, 'groups') ||
              !allowsSchoolModule(me, 'chat')) {
            return const SizedBox.shrink();
          }
          return Align(
            alignment: AlignmentDirectional.centerStart,
            child: TextButton.icon(
              key: ValueKey('group-chat-notice-$noticeId'),
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
