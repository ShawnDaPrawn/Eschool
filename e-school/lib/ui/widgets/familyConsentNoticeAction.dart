import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// The private reader reauthorizes; this entry reflects only fresh portal scope.
class FamilyConsentNoticeAction extends StatelessWidget {
  const FamilyConsentNoticeAction({
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
              !me!.roles.contains('Parent') ||
              !context.read<AuthCubit>().isParent() ||
              me.isModuleOn('family_forms') != true ||
              !allowsSchoolModule(me, 'family_forms')) {
            return const SizedBox.shrink();
          }
          return Align(
            alignment: AlignmentDirectional.centerStart,
            child: TextButton.icon(
              key: ValueKey('consent-notice-$noticeId'),
              icon: const Icon(Icons.fact_check_outlined),
              label: Text(
                SharedUiUtils.getTranslatedLabel(
                  context,
                  'Family consent and health',
                ),
              ),
              onPressed: () =>
                  Navigator.of(context).pushNamed(Routes.familyForms),
            ),
          );
        },
      );
}
