import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/data/models/teacher.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

// The screens built once in the shared packages (eschool_notifications,
// eschool_people) that a teacher uses, as this app opens them: their routes,
// and the checks that keep their entry points hidden until the server has
// their endpoints. Approvals and invitations are a guardian's, in the
// learner and parent app.

Route<dynamic> notificationSettingsRoute() => AdaptivePageRoute(
  builder: (_) => NotificationSettingsScreen.withRepository(
    ApiNotificationSettingsRepository(Api.client),
  ),
);

Route<dynamic> mySchoolRoute() => AdaptivePageRoute(
  builder: (context) => MySchoolScreen.withRepository(
    ApiMySchoolRepository(Api.client),
    schools: mySchoolChoices(context),
  ),
);

Route<dynamic> profileDetailsRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
    final subjects = <ProfileSubject>[
      if (me == null || me.schools.isEmpty) const ProfileSubject(),
      if (me != null)
        for (final membership in me.schools)
          ProfileSubject(
            schoolId: membership.school.id,
            label: '${label(profileMyDetailsKey)} — ${membership.school.name}',
          ),
    ];
    // The current school is first for the person's own details.
    final schoolId = me?.currentSchoolId;
    subjects.sort(
      (a, b) => (a.personId == null && a.schoolId == schoolId ? 0 : 1)
          .compareTo(b.personId == null && b.schoolId == schoolId ? 0 : 1),
    );
    return ProfileScreen.withRepository(
      ApiProfileRepository(Api.client),
      subjects: subjects,
      onSaved: () => context.read<MeContextCubit>().load(),
    );
  },
);

Route<dynamic> handlesRoute() => AdaptivePageRoute(
  builder: (context) => HandlesScreen.withRepository(
    ApiHandlesRepository(Api.client),
    guardian: false,
    onChanged: () => context.read<MeContextCubit>().load(),
  ),
);

Route<dynamic> profilePictureRoute() => AdaptivePageRoute(
  builder: (context) {
    final Teacher teacher = context.read<AuthCubit>().getTeacherDetails();
    return AvatarScreen.withRepository(
      ApiAvatarRepository(Api.client),
      name: teacher.getFullName(),
      isLearner: false,
      gender: teacher.gender,
      seed: teacher.userId,
      onSaved: () => context.read<MeContextCubit>().load(),
    );
  },
);

/// The school My school shows: the one the teacher is working in.
List<SchoolSummary> mySchoolChoices(BuildContext context) {
  final CurrentSchool? current = context
      .read<MeContextCubit>()
      .context
      ?.currentSchool;
  return [
    if (current != null && current.id > 0)
      SchoolSummary(
        id: current.id,
        name: current.name,
        shortName: current.shortName,
        crestUrl: current.crestUrl,
      ),
  ];
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
  String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
  void open(String route) => Navigator.of(context).pushNamed(route);
  return [
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_registers',
        ) ==
        true)
      tile(
        title: label('academicRegisters'),
        icon: Icons.fact_check_outlined,
        onTap: () => open(Routes.academicRegisters),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_timetable',
        ) ==
        true)
      tile(
        title: label('academicTimetable'),
        icon: Icons.calendar_view_week_outlined,
        onTap: () => open(Routes.academicTimetable),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_marks',
        ) ==
        true)
      tile(
        title: label('assessmentMarks'),
        icon: Icons.grid_on_outlined,
        onTap: () => open(Routes.academicMarks),
      ),
    AvailableOnly(
      featureKey: profileFieldsFeature,
      probe: () => profilesDeployed(ApiProfileRepository(Api.client)),
      child: tile(
        title: label(profileDetailsKey),
        icon: Icons.person_outline,
        onTap: () => open(Routes.profileDetails),
      ),
    ),
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

Route<dynamic> academicMarksRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final school = me?.currentSchool;
    if (school == null || me?.isFeatureOn('academic_marks') != true) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'assessmentMarks'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(context, 'notAvailableYet'),
          ),
        ),
      );
    }
    return AcademicContextBoundary(
      schoolId: school.id,
      child: MarkGroupsScreen(
        repository: ApiMarksRepository(Api.client, schoolId: school.id),
        schoolName: school.name,
      ),
    );
  },
);

Route<dynamic> academicTimetableRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final school = me?.currentSchool;
    if (school == null || me?.isFeatureOn('academic_timetable') != true) {
      return const ScreenScaffold(
        title: 'Academic timetable',
        body: Center(child: Text('Not available yet')),
      );
    }
    return AcademicContextBoundary(
      schoolId: school.id,
      child: TimetableScreen.withRepository(
        ApiTimetableRepository(Api.client, schoolId: school.id),
        title: school.name,
      ),
    );
  },
);

class AcademicNextPeriod extends StatelessWidget {
  const AcademicNextPeriod({super.key});
  @override
  Widget build(BuildContext context) {
    final me = context.watch<MeContextCubit>().context;
    final school = me?.currentSchool;
    if (school == null || me?.isFeatureOn('academic_timetable') != true)
      return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: NextPeriodCard(
        key: ValueKey('teacher:${school.id}'),
        scope: 'teacher:${school.id}',
        repository: ApiTimetableRepository(Api.client, schoolId: school.id),
        onOpen: () => Navigator.of(context).pushNamed(Routes.academicTimetable),
      ),
    );
  }
}

Route<dynamic> academicRegistersRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final school = me?.currentSchool;
    final owner = context.read<AuthCubit>().getTeacherDetails().userId;
    if (school == null ||
        owner <= 0 ||
        me?.isFeatureOn('academic_registers') != true) {
      return const ScreenScaffold(
        title: 'Academic registers',
        body: Center(child: Text('Not available yet')),
      );
    }
    return AcademicContextBoundary(
      schoolId: school.id,
      child: RegisterHubScreen(
        repository: ApiRegisterRepository(Api.client, schoolId: school.id),
        store: () => SecureRegisterStore.open(owner),
      ),
    );
  },
);
