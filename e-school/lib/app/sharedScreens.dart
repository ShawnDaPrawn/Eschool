import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/repositories/settingsRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

// The screens built once in the shared packages (eschool_notifications,
// eschool_people), as this app opens them: their routes, and the checks that
// keep their entry points hidden until the server has their endpoints.

Route<dynamic> notificationSettingsRoute() => AdaptivePageRoute(
  builder: (_) => NotificationSettingsScreen.withRepository(
    ApiNotificationSettingsRepository(Api.client),
  ),
);

Route<dynamic> approvalsRoute() => AdaptivePageRoute(
  builder: (context) => ApprovalsScreen.withRepository(
    ApiApprovalsRepository(Api.client),
    // the children's `approved` comes with /me/context
    onChanged: () => context.read<MeContextCubit>().load(),
  ),
);

Route<dynamic> mySchoolRoute() => AdaptivePageRoute(
  builder: (context) => MySchoolScreen.withRepository(
    ApiMySchoolRepository(Api.client),
    schools: mySchoolChoices(context),
  ),
);

Route<dynamic> invitationRoute(String token) => AdaptivePageRoute(
  builder: (_) => InvitationScreen.withRepository(
    ApiInvitationRepository(
      Api.client,
      deviceFields: NotificationUtility.signInDeviceFields,
    ),
    token: token,
    onAccepted: _signInFromInvitation,
  ),
);

Route<dynamic> handlesRoute() => AdaptivePageRoute(
  builder: (context) => HandlesScreen.withRepository(
    ApiHandlesRepository(Api.client),
    guardian: context.read<AuthCubit>().isParent(),
    onChanged: () => context.read<MeContextCubit>().load(),
  ),
);

Route<dynamic> profilePictureRoute() => AdaptivePageRoute(
  builder: (context) {
    final AuthCubit auth = context.read<AuthCubit>();
    final bool isParent = auth.isParent();
    final Parent parent = auth.getParentDetails();
    final Student student = auth.getStudentDetails();
    return AvatarScreen.withRepository(
      ApiAvatarRepository(Api.client),
      name: isParent ? parent.getFullName() : student.getFullName(),
      isLearner: !isParent,
      gender: isParent ? parent.gender : null,
      seed: isParent ? parent.userId : student.userId,
      onSaved: () => context.read<MeContextCubit>().load(),
    );
  },
);

/// An accepted invitation answers like the parent sign-in: sign in the
/// same way, then the policies to accept, then home.
void _signInFromInvitation(BuildContext context, Map<String, dynamic> signIn) {
  final Object? data = signIn['data'];
  context.read<AuthCubit>().authenticateUser(
    jwtToken: '${signIn['token'] ?? ''}',
    isStudent: false,
    parent: Parent.fromJson(data is Map ? Map.from(data) : {}),
    student: Student.fromJson({}),
  );
  SettingsRepository().setNotificationCount(0);
  Navigator.of(context)
      .pushNamedAndRemoveUntil(Routes.parentPolicies, (_) => false);
}

/// The schools My school offers: a parent's children's schools (the child
/// on show's first), otherwise the school the user is working in.
List<SchoolSummary> mySchoolChoices(BuildContext context) {
  final List<SchoolSummary> schools = [];
  void add(SchoolSummary school) {
    if (school.id > 0 && !schools.any((s) => s.id == school.id)) {
      schools.add(school);
    }
  }

  if (context.read<AuthCubit>().isParent()) {
    final ChildSelectionState children = context
        .read<ChildSelectionCubit>()
        .state;
    final MeChild? shown = children.selected;
    if (shown != null) {
      add(shown.child.school);
    }
    for (final MeChild child in children.children) {
      add(child.child.school);
    }
  }
  final CurrentSchool? current = context
      .read<MeContextCubit>()
      .context
      ?.currentSchool;
  if (schools.isEmpty && current != null) {
    add(
      SchoolSummary(
        id: current.id,
        name: current.name,
        shortName: current.shortName,
        crestUrl: current.crestUrl,
      ),
    );
  }
  return schools;
}

/// The entry points of the shared screens in Settings, each hidden until
/// the server has its endpoint. [tile] draws a row the way Settings does.
List<Widget> sharedScreenEntries(
  BuildContext context, {
  required Widget Function({
    required String title,
    required IconData icon,
    required VoidCallback onTap,
  })
  tile,
}) {
  final bool isParent = context.read<AuthCubit>().isParent();
  String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
  void open(String route) => Navigator.of(context).pushNamed(route);
  return [
    AvailableOnly(
      featureKey: handlesFeature,
      probe: () => handlesDeployed(ApiHandlesRepository(Api.client)),
      child: tile(
        title: label(handleSettingsKey),
        icon: Icons.alternate_email,
        onTap: () => open(Routes.handles),
      ),
    ),
    AvailableOnly(
      featureKey: notificationSettingsFeature,
      probe: () => notificationSettingsDeployed(
        ApiNotificationSettingsRepository(Api.client),
      ),
      child: tile(
        title: label(notificationSettingsKey),
        icon: Icons.tune,
        onTap: () => open(Routes.notificationSettings),
      ),
    ),
    AvailableOnly(
      featureKey: approvalsFeature,
      enabled: isParent,
      probe: () => approvalsDeployed(ApiApprovalsRepository(Api.client)),
      child: tile(
        title: label(approvalsKey),
        icon: Icons.verified_user_outlined,
        onTap: () => open(Routes.approvals),
      ),
    ),
    AvailableOnly(
      featureKey: mySchoolFeature,
      probe: () async {
        final List<SchoolSummary> schools = mySchoolChoices(context);
        return schools.isNotEmpty &&
            await mySchoolDeployed(
              ApiMySchoolRepository(Api.client),
              schools.first.id,
            );
      },
      child: tile(
        title: label(mySchoolKey),
        icon: Icons.school_outlined,
        onTap: () => open(Routes.mySchool),
      ),
    ),
    AvailableOnly(
      featureKey: avatarFeature,
      probe: () => avatarsDeployed(ApiAvatarRepository(Api.client)),
      child: tile(
        title: label(profilePictureKey),
        icon: Icons.face_outlined,
        onTap: () => open(Routes.profilePicture),
      ),
    ),
  ];
}
